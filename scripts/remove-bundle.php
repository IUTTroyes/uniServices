#!/usr/bin/env php
<?php

if ($argc < 2) {
    echo "Usage: php scripts/remove-bundle.php <bundle-name>\n";
    echo "Example: php scripts/remove-bundle.php sample-bundle\n";
    exit(1);
}

$bundleKebab = strtolower($argv[1]);
if (!preg_match('/^[a-z0-9-]+$/', $bundleKebab)) {
    echo "Error: Bundle name must be in kebab-case (e.g., my-new-bundle).\n";
    exit(1);
}

// Convert kebab-case to PascalCase
$bundlePascal = str_replace(' ', '', ucwords(str_replace('-', ' ', $bundleKebab)));
if (!str_ends_with($bundlePascal, 'Bundle')) {
    $bundlePascal .= 'Bundle';
}

$projectRoot = dirname(__DIR__);
$bundlePath = $projectRoot . '/packages/' . $bundleKebab;

echo "Removing bundle $bundlePascal...\n";

// 1. Remove directory
if (is_dir($bundlePath)) {
    echo "Deleting $bundlePath...\n";
    exec("rm -rf " . escapeshellarg($bundlePath));
} else {
    echo "Warning: Bundle directory $bundlePath not found.\n";
}

// 2. Update root composer.json
$rootComposerPath = $projectRoot . '/composer.json';
if (file_exists($rootComposerPath)) {
    $rootComposer = json_decode(file_get_contents($rootComposerPath), true);
    unset($rootComposer['autoload']['psr-4']["$bundlePascal\\"]);
    unset($rootComposer['replace']["iuttroyes/$bundleKebab"]);
    file_put_contents($rootComposerPath, json_encode($rootComposer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo "Updated root composer.json\n";
}

// 3. Update back/composer.json
$backComposerPath = $projectRoot . '/back/composer.json';
if (file_exists($backComposerPath)) {
    $backComposer = json_decode(file_get_contents($backComposerPath), true);
    unset($backComposer['autoload']['psr-4']["$bundlePascal\\"]);
    file_put_contents($backComposerPath, json_encode($backComposer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo "Updated back/composer.json\n";
}

// 4. Update back/config/bundles.php
$bundlesConfigPath = $projectRoot . '/back/config/bundles.php';
if (file_exists($bundlesConfigPath)) {
    $bundlesConfigRaw = file_get_contents($bundlesConfigPath);
    $pattern = "/\s*" . preg_quote($bundlePascal . '\\' . $bundlePascal, '/') . "::class => \[.*?\],\n/";
    $bundlesConfigRaw = preg_replace($pattern, "\n", $bundlesConfigRaw);
    file_put_contents($bundlesConfigPath, $bundlesConfigRaw);
    echo "Updated back/config/bundles.php\n";
}

// 5. Regenerate tools registry from all bundle.meta.json (local + external)
$toolsOut = $projectRoot . '/shared/global-data/tools.generated.json';
$packagesDir = $projectRoot . '/packages';
$externalDir = $projectRoot . '/shared/global-data/external-tools';
$entries = [];

// Helper to scan a directory for meta files
$scanMeta = function($dir) use (&$entries) {
    if (is_dir($dir)) {
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $path = $dir . '/' . $item;
            $metaPath = is_dir($path) ? $path . '/bundle.meta.json' : $path;
            
            if (is_file($metaPath) && str_ends_with($metaPath, '.meta.json')) {
                $data = json_decode(file_get_contents($metaPath), true);
                if (isset($data['name'], $data['description'], $data['urlSlug'])) {
                    $entries[] = [
                        'name' => $data['name'],
                        'description' => $data['description'],
                        'urlSlug' => $data['urlSlug'],
                        'url' => $data['url'] ?? "/{$data['urlSlug']}",
                    ];
                }
            }
        }
    }
};

$scanMeta($packagesDir);
$scanMeta($externalDir);

file_put_contents($toolsOut, json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo "\nBundle $bundlePascal removed successfully!\n";
echo "Next steps:\n";
echo "1. Run 'pnpm install' to update workspaces.\n";
echo "2. Run 'composer dump-autoload' at root and in 'back/' to update PHP autoloading.\n";
echo "3. Run 'cd back && bin/console cache:clear' to clear Symfony cache.\n";
