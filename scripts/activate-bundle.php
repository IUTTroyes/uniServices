#!/usr/bin/env php
<?php

if ($argc < 2) {
    echo "Usage: php scripts/activate-bundle.php <bundle-name>\n";
    echo "Example: php scripts/activate-bundle.php my-bundle\n";
    exit(1);
}

$bundleKebab = strtolower($argv[1]);
$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/scripts/utils/regenerate-tools-registry.php';
$bundlePath = $projectRoot . '/packages/' . $bundleKebab;

if (!is_dir($bundlePath)) {
    echo "Error: Bundle directory not found at $bundlePath.\n";
    exit(1);
}

// Convert kebab-case to PascalCase (e.g., auth-bundle -> AuthBundle)
$bundlePascal = str_replace(' ', '', ucwords(str_replace('-', ' ', $bundleKebab)));
if (!str_ends_with($bundlePascal, 'Bundle')) {
    $bundlePascal .= 'Bundle';
}

echo "Activating bundle $bundlePascal...\n";

// 1. Update back/config/bundles.php
$bundlesConfigPath = $projectRoot . '/back/config/bundles.php';
$bundlesConfigRaw = file_get_contents($bundlesConfigPath);

if (!str_contains($bundlesConfigRaw, "$bundlePascal\\$bundlePascal::class")) {
    $newBundleEntry = "    $bundlePascal\\$bundlePascal::class => ['all' => true],\n];";
    $bundlesConfigRaw = preg_replace('/];\s*$/', $newBundleEntry, $bundlesConfigRaw);
    file_put_contents($bundlesConfigPath, $bundlesConfigRaw);
    echo "- Added to back/config/bundles.php\n";
} else {
    echo "- Already present in back/config/bundles.php\n";
}

// 1b. Update root composer.json
$rootComposerPath = $projectRoot . '/composer.json';
if (file_exists($rootComposerPath)) {
    $rootComposer = json_decode(file_get_contents($rootComposerPath), true);
    $changed = false;

    if (!isset($rootComposer['autoload']['psr-4']["$bundlePascal\\"])) {
        $rootComposer['autoload']['psr-4']["$bundlePascal\\"] = "packages/$bundleKebab/src/";
        $changed = true;
    }
    if (!isset($rootComposer['replace']["iuttroyes/$bundleKebab"])) {
        $rootComposer['replace']["iuttroyes/$bundleKebab"] = "self.version";
        $changed = true;
    }

    if ($changed) {
        file_put_contents($rootComposerPath, json_encode($rootComposer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        echo "- Added to root composer.json\n";
    }
}

// 1c. Update back/composer.json
$backComposerPath = $projectRoot . '/back/composer.json';
if (file_exists($backComposerPath)) {
    $backComposer = json_decode(file_get_contents($backComposerPath), true);
    if (!isset($backComposer['autoload']['psr-4']["$bundlePascal\\"])) {
        $backComposer['autoload']['psr-4']["$bundlePascal\\"] = "../packages/$bundleKebab/src/";
        file_put_contents($backComposerPath, json_encode($backComposer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        echo "- Added to back/composer.json\n";
    }
}

// 2. Enable bundle.meta.json if disabled
$metaPath = $bundlePath . '/bundle.meta.json';
$metaDisabledPath = $bundlePath . '/bundle.meta.json.disabled';

if (is_file($metaDisabledPath)) {
    rename($metaDisabledPath, $metaPath);
    echo "- Enabled bundle.meta.json\n";
}

// 3. Regenerate tools registry from all bundle.meta.json (local + external)
regenerate_tools_registry($projectRoot);

echo "- Updating PHP autoloading...\n";
passthru("composer dump-autoload");
if (is_dir($projectRoot . '/back')) {
    passthru("cd back && composer dump-autoload && bin/console cache:clear");
}

echo "\nBundle $bundlePascal activated successfully!\n";

