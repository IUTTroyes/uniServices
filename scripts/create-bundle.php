#!/usr/bin/env php
<?php

if ($argc < 2) {
    echo "Usage: php scripts/create-bundle.php <bundle-name> [--display-name=Name] [--description=Text] [--url-slug=slug] [--url=http://...]\n";
    echo "Example: php scripts/create-bundle.php sample-bundle --display-name=Sample --description=\"Mon appli\" --url-slug=sample --url=http://localhost:3005/sample/\n";
    exit(1);
}

$bundleKebab = strtolower($argv[1]);
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

// Interactive prompt for missing info
if (empty($displayName)) {
    $defaultName = $bundlePascal;
    echo "Nom de l'outil (par défaut: $defaultName): ";
    $displayName = trim(fgets(STDIN)) ?: $defaultName;
}
if (empty($description)) {
    $defaultDesc = "Application $displayName";
    echo "Description de l'outil (par défaut: $defaultDesc): ";
    $description = trim(fgets(STDIN)) ?: $defaultDesc;
}
if (empty($urlSlug)) {
    $defaultSlug = strtolower(str_replace('bundle', '', $bundleKebab));
    echo "URL Slug (ex: $defaultSlug): ";
    $urlSlug = trim(fgets(STDIN)) ?: $defaultSlug;
}
if (empty($fullUrl)) {
    $defaultUrl = "/$urlSlug";
    echo "URL complète (ex: $defaultUrl ou http://localhost:3000/$urlSlug/): ";
    $fullUrl = trim(fgets(STDIN)) ?: $defaultUrl;
}

$projectRoot = dirname(__DIR__);
$bundlePath = $projectRoot . '/packages/' . $bundleKebab;

if (is_dir($bundlePath)) {
    echo "Error: Bundle directory already exists at $bundlePath.\n";
    exit(1);
}

// Defaults for meta
$metaName = $displayName;
$metaDescription = $description;
$metaUrlSlug = $urlSlug;
$metaUrl = $fullUrl;

echo "Creating bundle $bundlePascal in $bundlePath...\n";

// 1. Create directory structure
mkdir($bundlePath . '/src', 0755, true);
mkdir($bundlePath . '/assets', 0755, true);
mkdir($bundlePath . '/assets/views', 0755, true);
mkdir($bundlePath . '/assets/router', 0755, true);

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
    "type" => "module",
    "scripts" => [
        "dev" => "vite",
        "build" => "vite build",
        "preview" => "vite preview"
    ]
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

// 5. Create vite.config.js (template from auth-bundle)
$viteConfigTemplate = <<<JS
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'
import Components from 'unplugin-vue-components/vite'
import { PrimeVueResolver } from '@primevue/auto-import-resolver'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
  plugins: [
    vue(),
    tailwindcss(),
    Components({
      resolvers: [
        PrimeVueResolver()
      ],
      dts: path.resolve(__dirname, 'assets/components.d.ts'),
    })
  ],
  root: path.resolve(__dirname, 'assets'),
  base: './',
  build: {
    outDir: path.resolve(__dirname, 'src/Resources/public'),
    emptyOutDir: true,
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'assets'),
      '@types': path.resolve(__dirname, '../../shared/types'),
      '@components': path.resolve(__dirname, '../../shared/components'),
      '@config': path.resolve(__dirname, '../../shared/global-data'),
      '@styles': path.resolve(__dirname, '../../shared/styles'),
      '@images': path.resolve(__dirname, '../../shared/images'),
      '@helpers': path.resolve(__dirname, '../../shared/helpers'),
      '@requests': path.resolve(__dirname, '../../shared/requests'),
      '@stores': path.resolve(__dirname, '../../shared/stores'),
      '@utils': path.resolve(__dirname, '../../shared/utils'),
    },
  },
})
JS;
file_put_contents($bundlePath . '/vite.config.js', $viteConfigTemplate);

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

echo "\nBundle $bundlePascal created successfully!\n";
echo "Next steps:\n";
echo "1. Run 'pnpm install' to link the new workspace.\n";
echo "2. Run 'composer dump-autoload' at root and in 'back/' to update PHP autoloading.\n";
echo "3. Run 'cd back && bin/console cache:clear' to refresh Symfony bundle list.\n";
echo "4. Update 'Makefile' if you want to add dev/build commands for this bundle.\n";
echo "5. Front: la liste des outils a été mise à jour (shared/global-data/tools.generated.json).\n";
