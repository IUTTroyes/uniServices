#!/usr/bin/env php
<?php

function runCommand(string $command, ?string $workingDir = null): int
{
    if ($workingDir) {
        $command = 'cd ' . escapeshellarg($workingDir) . ' && ' . $command;
    }

    passthru($command, $exitCode);

    return $exitCode;
}

function normalizeBundleKebab(string $rawName): string
{
    $normalized = strtolower(trim($rawName));
    if (!str_ends_with($normalized, '-bundle')) {
        $normalized .= '-bundle';
    }

    return $normalized;
}

function removeDirectoryRecursive(string $path): void
{
    if (!is_dir($path)) {
        return;
    }

    $items = scandir($path);
    if ($items === false) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $itemPath = $path . DIRECTORY_SEPARATOR . $item;
        if (is_dir($itemPath) && !is_link($itemPath)) {
            removeDirectoryRecursive($itemPath);
        } else {
            @unlink($itemPath);
        }
    }

    @rmdir($path);
}

function extractUsedDevPorts(array $scripts): array
{
    $usedPorts = [];

    foreach ($scripts as $scriptName => $scriptCommand) {
        if (!str_starts_with((string) $scriptName, 'dev:')) {
            continue;
        }

        if (preg_match('/--port\s+(\d+)/', (string) $scriptCommand, $matches)) {
            $usedPorts[] = (int) $matches[1];
        }
    }

    return array_values(array_unique($usedPorts));
}

function escapeSqlLiteral(string $value): string
{
    return str_replace("'", "''", $value);
}

function cleanupPackagesReferences(string $projectRoot, array $packageNames): void
{
    if (!is_dir($projectRoot . '/back')) {
        return;
    }

    $cleanedNames = array_values(array_unique(array_filter(array_map(static fn ($name) => trim((string) $name), $packageNames))));
    if ($cleanedNames === []) {
        return;
    }

    foreach ($cleanedNames as $packageName) {
        $escapedName = escapeSqlLiteral($packageName);

        $removeFromEtudiantScolarite = "UPDATE etudiant_scolarite SET packages = COALESCE((SELECT jsonb_agg(value) FROM jsonb_array_elements_text(packages::jsonb) AS value WHERE value <> '{$escapedName}'), '[]'::jsonb)::json WHERE packages::jsonb ? '{$escapedName}'";
        runCommand('bin/console doctrine:query:sql ' . escapeshellarg($removeFromEtudiantScolarite), $projectRoot . '/back');

        $removeFromStructureDepartementPersonnel = "UPDATE structure_departement_personnel SET packages = COALESCE((SELECT jsonb_agg(value) FROM jsonb_array_elements_text(packages::jsonb) AS value WHERE value <> '{$escapedName}'), '[]'::jsonb)::json WHERE packages::jsonb ? '{$escapedName}'";
        runCommand('bin/console doctrine:query:sql ' . escapeshellarg($removeFromStructureDepartementPersonnel), $projectRoot . '/back');
    }
}

if ($argc < 2) {
    echo "Usage: php scripts/remove-bundle.php <bundle-name>\n";
    echo "Example: php scripts/remove-bundle.php sample-bundle\n";
    exit(1);
}

$rawBundleName = $argv[1];
$bundleKebab = normalizeBundleKebab($rawBundleName);
if (!preg_match('/^[a-z0-9-]+$/', $bundleKebab)) {
    echo "Error: Bundle name must be in kebab-case (e.g., my-new-bundle).\n";
    exit(1);
}

// Convert kebab-case to PascalCase
$bundlePascal = str_replace(' ', '', ucwords(str_replace('-', ' ', $bundleKebab)));
if (!str_ends_with($bundlePascal, 'Bundle')) {
    $bundlePascal .= 'Bundle';
}
$bundleShortName = preg_replace('/-bundle$/', '', $bundleKebab);
$bundleBasePascal = preg_replace('/Bundle$/', '', $bundlePascal);

$projectRoot = dirname(__DIR__);
$bundlesConfigPath = $projectRoot . '/back/config/bundles.php';
$doctrineConfigPath = $projectRoot . '/back/config/packages/doctrine.yaml';
$frontBundlesRegistryPath = $projectRoot . '/packages/shell/assets/bundles-registry.js';
$rootPackagePath = $projectRoot . '/package.json';
$rootComposerPath = $projectRoot . '/composer.json';
$backComposerPath = $projectRoot . '/back/composer.json';
$publicAssetsPath = $projectRoot . '/back/public/' . $bundleShortName;

require_once $projectRoot . '/scripts/utils/regenerate-tools-registry.php';

$bundlePath = $projectRoot . '/packages/' . $bundleKebab;

echo "Removing bundle $bundlePascal...\n";

if ($bundleKebab !== strtolower(trim($rawBundleName))) {
    echo "- Nom normalisé automatiquement: $bundleKebab\n";
}

// Try to derive slug from bundle.meta.json before deleting package dir
$metaPath = $bundlePath . '/bundle.meta.json';
if (is_file($metaPath)) {
    $metaData = json_decode(file_get_contents($metaPath), true);
    if (is_array($metaData) && !empty($metaData['urlSlug']) && is_string($metaData['urlSlug'])) {
        $bundleShortName = trim($metaData['urlSlug']);
        $publicAssetsPath = $projectRoot . '/back/public/' . $bundleShortName;
    }
}

$packagesNamesToCleanup = [$bundleShortName, $bundleKebab, $bundlePascal, $bundleBasePascal, lcfirst($bundleBasePascal)];

// 1. Remove directory
if (is_dir($bundlePath)) {
    echo "Deleting $bundlePath...\n";
    removeDirectoryRecursive($bundlePath);
} else {
    echo "Warning: Bundle directory $bundlePath not found.\n";
}

// 1b. Remove compiled assets directory (if present)
if (is_dir($publicAssetsPath)) {
    echo "Deleting compiled assets $publicAssetsPath...\n";
    removeDirectoryRecursive($publicAssetsPath);
}

// 2. Update root package.json scripts
if (file_exists($rootPackagePath)) {
    $rootPackage = json_decode(file_get_contents($rootPackagePath), true);
    if (is_array($rootPackage)) {
        $scripts = $rootPackage['scripts'] ?? [];
        if (!is_array($scripts)) {
            $scripts = [];
        }

        $devScriptName = 'dev:' . $bundleShortName;
        $buildScriptName = 'build:' . $bundleShortName;
        $changed = false;

        if (isset($scripts[$devScriptName])) {
            unset($scripts[$devScriptName]);
            $changed = true;
        }

        if (isset($scripts[$buildScriptName])) {
            unset($scripts[$buildScriptName]);
            $changed = true;
        }

        $devCommands = [];
        foreach ($scripts as $scriptName => $scriptCommand) {
            if (str_starts_with((string) $scriptName, 'dev:')) {
                $devCommands[] = '"npm run ' . $scriptName . '"';
            }
        }

        sort($devCommands);
        if (!empty($devCommands)) {
            $scripts['dev'] = 'concurrently ' . implode(' ', $devCommands);
        } else {
            unset($scripts['dev']);
        }

        if ($changed) {
            $rootPackage['scripts'] = $scripts;
            file_put_contents($rootPackagePath, json_encode($rootPackage, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            echo "Updated root package.json\n";
        }
    }
}

// 3. Update root composer.json
if (file_exists($rootComposerPath)) {
    $rootComposer = json_decode(file_get_contents($rootComposerPath), true);
    if (is_array($rootComposer)) {
        $changed = false;

        if (isset($rootComposer['autoload']['psr-4']["$bundlePascal\\"])) {
            unset($rootComposer['autoload']['psr-4']["$bundlePascal\\"]);
            $changed = true;
        }
        if (isset($rootComposer['replace']["iuttroyes/$bundleKebab"])) {
            unset($rootComposer['replace']["iuttroyes/$bundleKebab"]);
            $changed = true;
        }
        if (isset($rootComposer['autoload-dev']['psr-4']["$bundlePascal\\Tests\\"])) {
            unset($rootComposer['autoload-dev']['psr-4']["$bundlePascal\\Tests\\"]);
            $changed = true;
        }

        if ($changed) {
            file_put_contents($rootComposerPath, json_encode($rootComposer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            echo "Updated root composer.json\n";
        }
    }
}

// 4. Update back/composer.json
if (file_exists($backComposerPath)) {
    $backComposer = json_decode(file_get_contents($backComposerPath), true);
    if (is_array($backComposer) && isset($backComposer['autoload']['psr-4']["$bundlePascal\\"])) {
        unset($backComposer['autoload']['psr-4']["$bundlePascal\\"]);
        file_put_contents($backComposerPath, json_encode($backComposer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        echo "Updated back/composer.json\n";
    }
}

// 5. Update back/config/bundles.php
if (file_exists($bundlesConfigPath)) {
    $bundlesConfigRaw = file_get_contents($bundlesConfigPath);
    $pattern = '/\s*' . preg_quote($bundlePascal . '\\' . $bundlePascal . '::class', '/') . ' => \[.*?],/m';
    if (preg_match($pattern, $bundlesConfigRaw)) {
        $bundlesConfigRaw = preg_replace($pattern, '', $bundlesConfigRaw);
        file_put_contents($bundlesConfigPath, $bundlesConfigRaw);
        echo "Updated back/config/bundles.php\n";
    }
}

// 5b. Update Doctrine mappings to avoid references to disabled/removed bundles
if (file_exists($doctrineConfigPath)) {
    $doctrineConfigRaw = file_get_contents($doctrineConfigPath);
    $mappingPattern = '/^\s{20}' . preg_quote($bundlePascal, '/') . ':\R(?:^\s{24}[^\r\n]*\R)*/m';
    if (preg_match($mappingPattern, $doctrineConfigRaw)) {
        $doctrineConfigRaw = preg_replace($mappingPattern, '', $doctrineConfigRaw);
        file_put_contents($doctrineConfigPath, $doctrineConfigRaw);
        echo "Updated back/config/packages/doctrine.yaml\n";
    }
}

// 5c. Update front bundles registry (shell/widgets)
if (file_exists($frontBundlesRegistryPath)) {
    $frontRegistryRaw = file_get_contents($frontBundlesRegistryPath);
    $changed = false;

    $importPattern = '/^\s*import\s+' . preg_quote($bundleShortName, '/') . '\s+from\s+["\'][^"\']*["\'];\s*\R?/m';
    if (preg_match($importPattern, $frontRegistryRaw)) {
        $frontRegistryRaw = preg_replace($importPattern, '', $frontRegistryRaw);
        $changed = true;
    }

    $bundleEntryPattern = '/^\s*' . preg_quote($bundleShortName, '/') . '\s*,?\s*\R?/m';
    if (preg_match($bundleEntryPattern, $frontRegistryRaw)) {
        $frontRegistryRaw = preg_replace($bundleEntryPattern, '', $frontRegistryRaw);
        $changed = true;
    }

    if ($changed) {
        file_put_contents($frontBundlesRegistryPath, $frontRegistryRaw);
        echo "Updated packages/shell/assets/bundles-registry.js\n";
    }
}

// 6. Regenerate tools registry from all bundle.meta.json (local + external)
regenerate_tools_registry($projectRoot);

echo "- Updating PHP autoloading...\n";
runCommand('composer dump-autoload', $projectRoot);
if (is_dir($projectRoot . '/back')) {
    runCommand('composer dump-autoload', $projectRoot . '/back');
    echo "- Cleaning packages references in DB...\n";
    cleanupPackagesReferences($projectRoot, $packagesNamesToCleanup);
    $backCachePath = $projectRoot . '/back/var/cache';
    removeDirectoryRecursive($backCachePath . '/dev');
    removeDirectoryRecursive($backCachePath . '/prod');
    runCommand('bin/console cache:clear --env=dev --no-warmup', $projectRoot . '/back');
    runCommand('bin/console cache:clear --env=prod --no-warmup', $projectRoot . '/back');
}

echo "- Updating Node workspaces...\n";
$pnpmVersionExitCode = runCommand('pnpm --version', $projectRoot);
if ($pnpmVersionExitCode === 0) {
    runCommand('pnpm install --prefer-offline --ignore-scripts', $projectRoot);
} else {
    echo "Warning: pnpm is not available. Skipping workspace install.\n";
}

echo "\nBundle $bundlePascal removed successfully!\n";
echo "Next steps:\n";
echo "1. Vérifiez que la navigation n'affiche plus l'outil supprimé (tools.generated.json régénéré).\n";
echo "2. Redémarrez les serveurs dev si nécessaire pour recharger les scripts package.json.\n";
