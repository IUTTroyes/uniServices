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

function portIsAvailable(int $port): bool
{
    if ($port < 1 || $port > 65535) {
        return false;
    }

    $socket = @stream_socket_server("tcp://127.0.0.1:$port", $errno, $errstr);
    if ($socket === false) {
        return false;
    }

    fclose($socket);
    return true;
}

function findFirstAvailablePort(array $usedScriptPorts, int $startPort = 3000): int
{
    $port = $startPort;
    while (in_array($port, $usedScriptPorts, true) || !portIsAvailable($port)) {
        $port++;
    }

    return $port;
}

function readRootScripts(string $projectRoot): array
{
    $rootPackagePath = $projectRoot . '/package.json';
    if (!file_exists($rootPackagePath)) {
        return [];
    }

    $rootPackage = json_decode(file_get_contents($rootPackagePath), true);
    if (!is_array($rootPackage)) {
        return [];
    }

    return is_array($rootPackage['scripts'] ?? null) ? $rootPackage['scripts'] : [];
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

if ($argc < 2) {
    echo "Usage: php scripts/create-bundle.php <bundle-name> [--display-name=Name] [--description=Text] [--url-slug=slug] [--url=http://...]\n";
    echo "Example: php scripts/create-bundle.php sample-bundle --display-name=Sample --description=\"Mon appli\" --url-slug=sample --url=http://localhost:3005/sample/\n";
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
    $defaultSlug = $bundleShortName;
    echo "URL Slug (ex: $defaultSlug): ";
    $urlSlug = trim(fgets(STDIN)) ?: $defaultSlug;
}

$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/scripts/utils/regenerate-tools-registry.php';

$rootScripts = readRootScripts($projectRoot);
$usedScriptPorts = extractUsedDevPorts($rootScripts);

$preferredPort = null;
if (!empty($fullUrl) && preg_match('#^https?://[^:/]+:(\d+)#', $fullUrl, $portMatches)) {
    $preferredPort = (int) $portMatches[1];
}

$suggestedPort = findFirstAvailablePort($usedScriptPorts, $preferredPort ?? 3000);

if (empty($fullUrl)) {
    $defaultUrl = "http://localhost:$suggestedPort/$urlSlug/";
    echo "URL complète (ex: /$urlSlug ou $defaultUrl): ";
    $fullUrl = trim(fgets(STDIN)) ?: $defaultUrl;
}

$metaUrl = $fullUrl;
if (!empty($fullUrl) && preg_match('#^https?://localhost:(\d+)(/.*)?$#', $fullUrl, $urlMatches)) {
    $currentUrlPort = (int) $urlMatches[1];
    $urlPath = $urlMatches[2] ?? '/';
    $shouldAdjustPort = in_array($currentUrlPort, $usedScriptPorts, true) || !portIsAvailable($currentUrlPort);

    if ($shouldAdjustPort) {
        $metaUrl = "http://localhost:$suggestedPort$urlPath";
        echo "- Port $currentUrlPort indisponible pour l'URL, proposition auto: $metaUrl\n";
    }
}

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
  base: '/$metaUrlSlug/',
  build: {
    outDir: path.resolve(__dirname, '../../back/public/$metaUrlSlug'),
    emptyOutDir: true,
  },
  server: {
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        secure: false,
      },
    },
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

// 5b. Create minimal front scaffold
$indexHtmlContent = <<<HTML
<!doctype html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>$metaName</title>
  </head>
  <body>
    <div id="app"></div>
    <script type="module" src="./main.js"></script>
  </body>
</html>
HTML;
file_put_contents($bundlePath . '/assets/index.html', $indexHtmlContent);

$mainJsContent = <<<JS
import { createApp } from 'vue';
import App from './App.vue';

const app = createApp(App);

app.mount('#app');
JS;
file_put_contents($bundlePath . '/assets/main.js', $mainJsContent);

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

// 5c. Ensure root package.json scripts contain dev/build entries for the new bundle
$rootPackagePath = $projectRoot . '/package.json';
if (file_exists($rootPackagePath)) {
    $rootPackage = json_decode(file_get_contents($rootPackagePath), true);

    if (is_array($rootPackage)) {
        $scripts = $rootPackage['scripts'] ?? [];

        if (!is_array($scripts)) {
            $scripts = [];
        }

        $usedPorts = extractUsedDevPorts($scripts);

        $devPort = findFirstAvailablePort($usedPorts, $suggestedPort);

        $devScriptName = 'dev:' . $bundleShortName;
        $buildScriptName = 'build:' . $bundleShortName;

        $scripts[$devScriptName] = "cd packages/$bundleKebab && vite --port $devPort";
        $scripts[$buildScriptName] = "cd packages/$bundleKebab && vite build";

        $devCommands = [];
        foreach ($scripts as $scriptName => $scriptCommand) {
            if (str_starts_with($scriptName, 'dev:')) {
                $devCommands[] = '"npm run ' . $scriptName . '"';
            }
        }

        sort($devCommands);
        $scripts['dev'] = 'concurrently ' . implode(' ', $devCommands);

        $rootPackage['scripts'] = $scripts;
        file_put_contents($rootPackagePath, json_encode($rootPackage, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}

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
echo "Next steps:\n";
echo "1. Le script dev/build du bundle a été ajouté au package.json racine.\n";
echo "2. Front: la liste des outils a été mise à jour (shared/global-data/tools.generated.json).\n";
echo "3. Lancez 'npm run dev:$bundleShortName' puis ouvrez $metaUrl pour vérifier la vue d'accueil.\n";
