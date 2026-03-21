<?php

use Symplify\MonorepoBuilder\Config\MBConfig;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\AddTagToChangelogReleaseWorker;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\PushTagReleaseWorker;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\TagVersionReleaseWorker;

return static function (MBConfig $mbConfig): void {
    $mbConfig->packageDirectories([
        __DIR__ . '/packages',
        __DIR__ . '/shared',
    ]);

    // Exclure les dossiers de dev
    $mbConfig->packageDirectoriesExcludes([
        __DIR__ . '/shared/components',
    ]);

    // Workers pour les releases
    $mbConfig->workers([
        TagVersionReleaseWorker::class,
        PushTagReleaseWorker::class,
        AddTagToChangelogReleaseWorker::class,
    ]);
};
