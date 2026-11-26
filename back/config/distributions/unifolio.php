<?php

// UniFolio autonome : tout le socle de config/bundles.php, mais seulement les packages listés ici.
$packages = ['AuthBundle', 'FormationBundle', 'UnifolioBundle'];

// Espaces de noms des packages du dépôt : « mon-outil-bundle » => « MonOutilBundle ».
$duDepot = array_map(
    static fn (string $dossier): string => str_replace(' ', '', ucwords(str_replace('-', ' ', basename($dossier)))),
    glob(dirname(__DIR__, 3).'/packages/*-bundle') ?: [],
);

return array_filter(
    require dirname(__DIR__).'/bundles.php',
    static function (string $classe) use ($packages, $duDepot): bool {
        $racine = explode('\\', $classe)[0];

        return !in_array($racine, $duDepot, true) || in_array($racine, $packages, true);
    },
    \ARRAY_FILTER_USE_KEY,
);
