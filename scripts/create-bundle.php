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

function demande(string $invitation, string $defaut): string
{
    echo "$invitation [$defaut] : ";
    $reponse = fgets(STDIN);
    return $reponse === false || trim($reponse) === '' ? $defaut : trim($reponse);
}

function choixMetadonnee(?string $argument, string $invitation, string $defaut, callable $valide): string
{
    $valeur = $argument ?? demande($invitation, $defaut);
    while (!$valide($valeur)) {
        fwrite(STDERR, "Valeur invalide pour $invitation.
");
        if ($argument !== null || feof(STDIN)) {
            exit(1);
        }
        $valeur = demande($invitation, $defaut);
    }

    return $valeur;
}

if ($argc < 2) {
    echo "Usage: php scripts/create-bundle.php <bundle-name> [--display-name=Name] [--description=Text] [--url-slug=slug] [--url=/app/slug] [--short-label=Label] [--color=violet] [--icon=\"pi pi-car\"]\n";
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
$shortLabel = null;
$primaryColor = null;
$icon = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--display-name=')) {
        $displayName = substr($arg, strlen('--display-name='));
    } elseif (str_starts_with($arg, '--description=')) {
        $description = substr($arg, strlen('--description='));
    } elseif (str_starts_with($arg, '--url-slug=')) {
        $urlSlug = substr($arg, strlen('--url-slug='));
    } elseif (str_starts_with($arg, '--url=')) {
        $fullUrl = substr($arg, strlen('--url='));
    } elseif (str_starts_with($arg, '--short-label=')) {
        $shortLabel = substr($arg, strlen('--short-label='));
    } elseif (str_starts_with($arg, '--color=')) {
        $primaryColor = substr($arg, strlen('--color='));
    } elseif (str_starts_with($arg, '--icon=')) {
        $icon = substr($arg, strlen('--icon='));
    }
}

// Convert kebab-case to PascalCase (e.g., auth-bundle -> AuthBundle)
$bundlePascal = str_replace(' ', '', ucwords(str_replace('-', ' ', $bundleKebab)));
if (!str_ends_with($bundlePascal, 'Bundle')) {
    $bundlePascal .= 'Bundle';
}

$bundleShortName = preg_replace('/-bundle$/', '', $bundleKebab);

$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/scripts/utils/regenerate-tools-registry.php';
$palette = json_decode(file_get_contents($projectRoot . '/shared/global-data/application-colors.json'), true, flags: JSON_THROW_ON_ERROR);

$defaultName = ucwords(str_replace('-', ' ', $bundleShortName));
$displayName = tool_display_name($displayName ?: demande("Nom de l’application", $defaultName), $defaultName);
$shortLabel = $shortLabel ?: demande('Libellé court', $displayName);
$description = $description ?: demande("Description de l’application", "Application $displayName");
$urlSlug = choixMetadonnee($urlSlug, 'Identifiant dans l’URL', $bundleShortName,
    static fn (string $slug): bool => (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $slug)
);
$metaUrl = choixMetadonnee($fullUrl, 'Lien du portail', "/app/$urlSlug",
    static fn (string $url): bool => (bool) preg_match('~^(?:/(?!/)|https?://)[^\s]*$~D', $url)
);
if ($primaryColor === null) {
    echo 'Couleurs Tailwind disponibles : ' . implode(', ', $palette) . "\n";
}
$primaryColor = choixMetadonnee($primaryColor, 'Couleur principale', 'violet',
    static fn (string $couleur): bool => in_array($couleur, $palette, true)
);
$icon = choixMetadonnee($icon, 'Icône PrimeIcons (ex. pi pi-car)', 'pi pi-box',
    static fn (string $classe): bool => (bool) preg_match('/^pi pi-[a-z0-9]+(?:-[a-z0-9]+)*$/D', $classe)
);

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

// 2b. Le package déclare lui-même sa configuration (services, mapping, migrations, gabarits).
$bundleBase = preg_replace('/Bundle$/', '', $bundlePascal);
foreach (['/src/DependencyInjection', '/src/Resources/config', '/src/AnneeUniversitaire'] as $dossier) {
    mkdir($bundlePath . $dossier, 0755, true);
}

$extension = <<<PHP
<?php

namespace $bundlePascal\DependencyInjection;

use App\Extension\AbstractPackageExtension;

class {$bundleBase}Extension extends AbstractPackageExtension
{
}

PHP;
file_put_contents($bundlePath . "/src/DependencyInjection/{$bundleBase}Extension.php", $extension);

$services = <<<YAML
services:
  _defaults:
    autowire: true
    autoconfigure: true

  $bundlePascal\:
    resource: '../../'
    exclude:
      - '../../Entity/'
      - '../../DependencyInjection/'
      - '../../$bundlePascal.php'

YAML;
file_put_contents($bundlePath . '/src/Resources/config/services.yaml', $services);

$perimetre = <<<PHP
<?php

declare(strict_types=1);

namespace $bundlePascal\AnneeUniversitaire;

use App\Extension\AbstractPerimetreAnnuelProvider;

/**
 * Chaque ressource du package doit être déclarée ici : chemins() si elle dépend d'une année universitaire,
 * independants() sinon. Les contrôleurs du package portent l'attribut #[ContexteAnnuel].
 */
final class PerimetreAnnuel$bundleBase extends AbstractPerimetreAnnuelProvider
{
    public function espaceDeNoms(): string
    {
        return '$bundlePascal';
    }
}

PHP;
file_put_contents($bundlePath . "/src/AnneeUniversitaire/PerimetreAnnuel{$bundleBase}.php", $perimetre);

// 3. Create composer.json
$composerContent = [
    "name" => "iuttroyes/$bundleKebab",
    "type" => "symfony-bundle",
    "description" => "$bundlePascal for uniServices",
    "require" => [
        "php" => "^8.4",
        "symfony/framework-bundle" => "^8.1",
        "symfony/http-kernel" => "^8.1"
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
    'shortLabel' => $shortLabel,
    'primaryColor' => $primaryColor,
    'icon' => $icon,
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
echo "Il fait partie de la distribution « intranet » seulement (voir docs/DISTRIBUTIONS.md).\n";
echo "Lancez 'npm run dev' puis ouvrez http://localhost:3000/app/$metaUrlSlug pour vérifier la vue d'accueil.\n";
