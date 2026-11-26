<?php

namespace App\Application;

/**
 * Identité des applications, lue dans le « bundle.meta.json » de chaque package : source unique, partagée avec le
 * front (voir docs/IDENTITE_APPLICATIONS.md). Accès statique pour servir aussi les déclarations sans injection
 * (énumérations de notifications, modèles d'e-mails, rôles).
 */
final class ApplicationCatalog
{
    /** @var array<string, string>|null identifiant ou alias => nom affiché */
    private static ?array $noms = null;

    /** Nom affiché d'une application à partir de son identifiant d'URL ou d'un alias (« portfolio » => « UniFolio »). */
    public static function nom(string $code): string
    {
        self::$noms ??= self::charger(\dirname(__DIR__, 3) . '/packages');

        return self::$noms[strtolower(trim($code))] ?? ucfirst($code);
    }

    /** @return array<string, string> */
    private static function charger(string $dossier): array
    {
        $noms = [];
        $alias = [];
        foreach (glob($dossier . '/*/bundle.meta.json') ?: [] as $fichier) {
            $meta = json_decode((string) file_get_contents($fichier), true, flags: \JSON_THROW_ON_ERROR);
            if (!isset($meta['urlSlug'], $meta['name'])) {
                continue;
            }
            $noms[$meta['urlSlug']] = $meta['name'];
            foreach ($meta['aliases'] ?? [] as $autre) {
                $alias[$autre] = $meta['name'];
            }
        }

        // Un identifiant l'emporte toujours sur un alias.
        return $noms + $alias;
    }
}
