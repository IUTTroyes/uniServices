#!/usr/bin/env php
<?php

require_once __DIR__ . '/utils/tool-display-name.php';

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

if ($argc < 2) {
    echo "Usage: php scripts/create-bundle.php <bundle-name> [--display-name=Name] [--description=Text] [--url-slug=slug] [--url=/app/slug]\n";
    echo "Example: php scripts/create-bundle.php sample-bundle --display-name=Sample --description=\"Mon appli\" --url-slug=sample --url=/app/sample\n";
    exit(1);
}

$rawBundleName = $argv[1];
$bundleKebab = normalizeBundleKebab($rawBundleName);
if (!preg_match('/^[a-z0-9-]+$/', $bundleKebab)) {
    echo "Error: Bundle name must be in kebab-case (e.g., my-new-bundle).\n";
    exit(1);
}

// Parse optional flags
$displayName = null;
$description = null;
$urlSlug = null;
$fullUrl = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--display-name=')) {
        $displayName = substr($arg, strlen('--display-name='));
    } elseif (str_starts_with($arg, '--description=')) {
        $description = substr($arg, strlen('--description='));
    } elseif (str_starts_with($arg, '--url-slug=')) {
        $urlSlug = substr($arg, strlen('--url-slug='));
    } elseif (str_starts_with($arg, '--url=')) {
        $fullUrl = substr($arg, strlen('--url='));
    }
}

// Convert kebab-case to PascalCase (e.g., auth-bundle -> AuthBundle)
$bundlePascal = str_replace(' ', '', ucwords(str_replace('-', ' ', $bundleKebab)));
if (!str_ends_with($bundlePascal, 'Bundle')) {
    $bundlePascal .= 'Bundle';
}

$bundleShortName = preg_replace('/-bundle$/', '', $bundleKebab);

// Interactive prompt for missing info
if (empty($displayName)) {
    $defaultName = ucwords(str_replace('-', ' ', $bundleShortName));
    echo "Nom de l'outil (par défaut: $defaultName): ";
    $displayName = trim(fgets(STDIN)) ?: $defaultName;
}
$displayName = tool_display_name($displayName, ucwords(str_replace('-', ' ', $bundleShortName)));
if (empty($description)) {
    $defaultDesc = "Application $displayName";
    echo "Description de l'outil (par défaut: $defaultDesc): ";
    $description = trim(fgets(STDIN)) ?: $defaultDesc;
}
if (empty($urlSlug)) {
    $defaultSlug = $bundleShortName;
    echo "URL Slug (ex: $defaultSlug): ";
    $urlSlug = trim(fgets(STDIN)) ?: $defaultSlug;
}

$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/scripts/utils/regenerate-tools-registry.php';

if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $urlSlug)) {
    echo "Error: URL slug must be in kebab-case (e.g., my-new-bundle).\n";
    exit(1);
}

$metaUrl = $fullUrl ?: "/app/$urlSlug";

$bundlePath = $projectRoot . '/packages/' . $bundleKebab;

if (is_dir($bundlePath)) {
    echo "Error: Bundle directory already exists at $bundlePath.\n";
    exit(1);
}

// Defaults for meta
$metaName = $displayName;
$metaDescription = $description;
$metaUrlSlug = $urlSlug;

echo "Creating bundle $bundlePascal in $bundlePath...\n";

if ($bundleKebab !== strtolower(trim($rawBundleName))) {
    echo "- Nom normalisé automatiquement: $bundleKebab\n";
}

// 1. Create directory structure
if (!mkdir($bundlePath . '/src', 0755, true) && !is_dir($bundlePath . '/src')) {
    echo "Error: Unable to create directory $bundlePath/src.\n";
    exit(1);
}
if (!mkdir($bundlePath . '/assets', 0755, true) && !is_dir($bundlePath . '/assets')) {
    echo "Error: Unable to create directory $bundlePath/assets.\n";
    exit(1);
}
if (!mkdir($bundlePath . '/assets/views', 0755, true) && !is_dir($bundlePath . '/assets/views')) {
    echo "Error: Unable to create directory $bundlePath/assets/views.\n";
    exit(1);
}
if (!mkdir($bundlePath . '/assets/router', 0755, true) && !is_dir($bundlePath . '/assets/router')) {
    echo "Error: Unable to create directory $bundlePath/assets/router.\n";
    exit(1);
}

// 2. Create src/Bundle.php
$bundleFileContent = <<<PHP
<?php

namespace $bundlePascal;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class $bundlePascal extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
PHP;
file_put_contents($bundlePath . '/src/' . $bundlePascal . '.php', $bundleFileContent);

// 3. Create composer.json
$composerContent = [
    "name" => "iuttroyes/$bundleKebab",
    "type" => "symfony-bundle",
    "description" => "$bundlePascal for uniServices",
    "require" => [
        "php" => "^8.2",
        "symfony/framework-bundle" => "^7.0",
        "symfony/http-kernel" => "^7.0"
    ],
    "autoload" => [
        "psr-4" => [
            "$bundlePascal\\" => "src/"
        ]
    ]
];
file_put_contents($bundlePath . '/composer.json', json_encode($composerContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

// 4. Create package.json
$packageContent = [
    "name" => "@uni/$bundleKebab",
    "private" => true,
    "version" => "1.0.0",
    "type" => "module"
];
file_put_contents($bundlePath . '/package.json', json_encode($packageContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

// 4b. Create bundle meta for tools registry
$bundleMeta = [
    'name' => $metaName,
    'description' => $metaDescription,
    'urlSlug' => $metaUrlSlug,
    'url' => $metaUrl,
];
file_put_contents($bundlePath . '/bundle.meta.json', json_encode($bundleMeta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

// 5. Create the view and register its route in the Shell.
$appVueContent = <<<VUE
<template>
  <main class="bundle-home">
    <h1>$metaName</h1>
    <p>$metaDescription</p>
    <p>Bienvenue sur la vue d'accueil du bundle <strong>$bundlePascal</strong>.</p>
  </main>
</template>

<style scoped>
.bundle-home {
  min-height: 100vh;
  display: grid;
  place-content: center;
  gap: 0.75rem;
  text-align: center;
  padding: 2rem;
}
</style>
VUE;
file_put_contents($bundlePath . '/assets/App.vue', $appVueContent);

$manifestTitle = json_encode($metaName, JSON_THROW_ON_ERROR);
$manifest = <<<JS
export default {
  name: '$metaUrlSlug',
  routes: [
    {
      path: '/$metaUrlSlug',
      name: '$metaUrlSlug-home',
      component: () => import('./App.vue'),
      meta: { title: $manifestTitle },
    },
  ],
};
JS;
file_put_contents($bundlePath . '/assets/manifest.ts', $manifest . "\n");

$frontRegistryPath = $projectRoot . '/packages/shell/assets/bundles-registry.js';
$frontRegistry = file_get_contents($frontRegistryPath);
$frontRegistry = str_replace(
    "export const bundles = [",
    "import $bundlePascal from '../../$bundleKebab/assets/manifest.ts';\n\nexport const bundles = [\n  $bundlePascal,",
    $frontRegistry
);
file_put_contents($frontRegistryPath, $frontRegistry);

// 6. Update root composer.json
$rootComposerPath = $projectRoot . '/composer.json';
$rootComposer = json_decode(file_get_contents($rootComposerPath), true);

$rootComposer['autoload']['psr-4']["$bundlePascal\\"] = "packages/$bundleKebab/src/";
$rootComposer['replace']["iuttroyes/$bundleKebab"] = "self.version";

file_put_contents($rootComposerPath, json_encode($rootComposer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

// 6b. Update back/composer.json
$backComposerPath = $projectRoot . '/back/composer.json';
if (file_exists($backComposerPath)) {
    $backComposer = json_decode(file_get_contents($backComposerPath), true);
    $backComposer['autoload']['psr-4']["$bundlePascal\\"] = "../packages/$bundleKebab/src/";
    file_put_contents($backComposerPath, json_encode($backComposer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

// 7. Update back/config/bundles.php
$bundlesConfigPath = $projectRoot . '/back/config/bundles.php';
$bundlesConfigRaw = file_get_contents($bundlesConfigPath);

$newBundleEntry = "    $bundlePascal\\$bundlePascal::class => ['all' => true],\n];";
if (!str_contains($bundlesConfigRaw, "$bundlePascal\\$bundlePascal::class")) {
    $bundlesConfigRaw = preg_replace('/];\s*$/', $newBundleEntry, $bundlesConfigRaw);
    file_put_contents($bundlesConfigPath, $bundlesConfigRaw);
}

// 8. Regenerate tools registry from all bundle.meta.json (local + external)
regenerate_tools_registry($projectRoot);

echo "- Updating PHP autoloading...\n";
runCommand('composer dump-autoload', $projectRoot);
if (is_dir($projectRoot . '/back')) {
    runCommand('composer dump-autoload', $projectRoot . '/back');
    runCommand('bin/console cache:clear', $projectRoot . '/back');
}

echo "- Updating Node workspaces...\n";
$pnpmVersionExitCode = runCommand('pnpm --version', $projectRoot);
if ($pnpmVersionExitCode === 0) {
    runCommand('pnpm install --prefer-offline --ignore-scripts', $projectRoot);
} else {
    echo "Warning: pnpm is not available. Skipping workspace install.\n";
}

echo "\nBundle $bundlePascal created successfully!\n";
echo "Lancez 'npm run dev' puis ouvrez http://localhost:3000/app/$metaUrlSlug pour vérifier la vue d'accueil.\n";
