#!/usr/bin/env php
<?php

if ($argc < 2) {
    echo "Usage: php scripts/deactivate-bundle.php <bundle-name>\n";
    echo "Example: php scripts/deactivate-bundle.php my-bundle\n";
    exit(1);
}

$bundleKebab = strtolower($argv[1]);
$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/scripts/utils/regenerate-tools-registry.php';
$bundlePath = $projectRoot . '/packages/' . $bundleKebab;
$isDir = is_dir($bundlePath);

// Convert kebab-case to PascalCase (e.g., auth-bundle -> AuthBundle)
$bundlePascal = str_replace(' ', '', ucwords(str_replace('-', ' ', $bundleKebab)));
if (!str_ends_with($bundlePascal, 'Bundle')) {
    $bundlePascal .= 'Bundle';
}

echo "Deactivating bundle $bundlePascal...\n";

// 1. Remove from back/config/bundles.php
$bundlesConfigPath = $projectRoot . '/back/config/bundles.php';
$bundlesConfigRaw = file_get_contents($bundlesConfigPath);

$pattern = '/\s*' . preg_quote($bundlePascal . '\\' . $bundlePascal . '::class', '/') . ' => \[.*?\],/m';
if (preg_match($pattern, $bundlesConfigRaw)) {
    $bundlesConfigRaw = preg_replace($pattern, '', $bundlesConfigRaw);
    file_put_contents($bundlesConfigPath, $bundlesConfigRaw);
    echo "- Removed from back/config/bundles.php\n";
} else {
    echo "- Not found in back/config/bundles.php\n";
}

// 1b. Update composer.json (root)
$rootComposerPath = $projectRoot . '/composer.json';
if (file_exists($rootComposerPath)) {
    $rootComposer = json_decode(file_get_contents($rootComposerPath), true);
    $changed = false;

    if (isset($rootComposer['autoload']['psr-4']["$bundlePascal\\"])) {
        unset($rootComposer['autoload']['psr-4']["$bundlePascal\\"]);
        $changed = true;
    }
    if (isset($rootComposer['replace']["iuttroyes/$bundleKebab"])) {
        unset($rootComposer['replace']["iuttroyes/$bundleKebab"]);
        $changed = true;
    }

    if ($changed) {
        file_put_contents($rootComposerPath, json_encode($rootComposer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        echo "- Removed from root composer.json\n";
    }
}

// 1c. Update back/composer.json
$backComposerPath = $projectRoot . '/back/composer.json';
if (file_exists($backComposerPath)) {
    $backComposer = json_decode(file_get_contents($backComposerPath), true);
    if (isset($backComposer['autoload']['psr-4']["$bundlePascal\\"])) {
        unset($backComposer['autoload']['psr-4']["$bundlePascal\\"]);
        file_put_contents($backComposerPath, json_encode($backComposer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        echo "- Removed from back/composer.json\n";
    }
}

// 2. Temporarily move/rename bundle.meta.json to exclude it from registry
if ($isDir) {
    $metaPath = $bundlePath . '/bundle.meta.json';
    $metaDisabledPath = $bundlePath . '/bundle.meta.json.disabled';

    if (is_file($metaPath)) {
        rename($metaPath, $metaDisabledPath);
        echo "- Disabled bundle.meta.json\n";
    } else {
        echo "- bundle.meta.json already disabled or not found\n";
    }
}

// 3. Regenerate tools registry
regenerate_tools_registry($projectRoot);

echo "- Updating PHP autoloading...\n";
passthru("composer dump-autoload");
if (is_dir($projectRoot . '/back')) {
    passthru("cd back && composer dump-autoload && bin/console cache:clear");
}

echo "\nBundle $bundlePascal deactivated successfully!\n";
