<?php

declare(strict_types=1);

namespace App\Extension;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Un package apporte lui-même sa configuration : services, mapping Doctrine, migrations,
 * ressources API et gabarits.
 * L'application n'a qu'à l'enregistrer. Les contrôleurs sont importés par le noyau.
 */
abstract class AbstractPackageExtension extends Extension implements PrependExtensionInterface
{
    /** Espace de noms Twig des gabarits (null : espace global). */
    protected function espaceTwig(): ?string
    {
        return null;
    }

    /** Vrai si les entités du package doivent être ajoutées aux chemins d'API Platform. */
    protected function declareRessourcesApi(): bool
    {
        return false;
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $dossier = $this->source().'/Resources/config';
        if (is_file($dossier.'/services.yaml')) {
            (new YamlFileLoader($container, new FileLocator($dossier)))->load('services.yaml');
        }
    }

    public function prepend(ContainerBuilder $container): void
    {
        $source = $this->source();
        $espace = substr(static::class, 0, (int) strpos(static::class, '\\'));

        // Deux motifs plutôt que GLOB_BRACE, absent d'Alpine (image de production).
        if (glob($source.'/Entity/*.php') || glob($source.'/Entity/*/*.php')) {
            $container->prependExtensionConfig('doctrine', ['orm' => ['entity_managers' => ['default' => ['mappings' => [
                $espace => [
                    'type' => 'attribute',
                    'is_bundle' => false,
                    'dir' => $source.'/Entity',
                    'prefix' => $espace.'\\Entity',
                    'alias' => str_replace('Bundle', '', $espace),
                ],
            ]]]]]);
        }
        if ($this->declareRessourcesApi()) {
            $container->prependExtensionConfig('api_platform', ['mapping' => ['paths' => [$source.'/Entity']]]);
        }
        $migrations = \dirname($source).'/migrations';
        if (is_dir($migrations)) {
            $container->prependExtensionConfig('doctrine_migrations', ['migrations_paths' => [$espace.'\\Migrations' => $migrations]]);
        }
        $gabarits = \dirname($source).'/templates';
        if (is_dir($gabarits)) {
            $container->prependExtensionConfig('twig', ['paths' => [$gabarits => $this->espaceTwig()]]);
        }
    }

    private function source(): string
    {
        return \dirname((string) (new \ReflectionClass(static::class))->getFileName(), 2);
    }
}
