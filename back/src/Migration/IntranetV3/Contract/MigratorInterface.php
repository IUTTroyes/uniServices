<?php

namespace App\Migration\IntranetV3\Contract;

use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;

interface MigratorInterface
{
    public function getName(): string;

    /**
     * @return list<class-string<MigratorInterface>>
     */
    public function getDependencies(): array;

    public function migrate(MigrationContext $context): MigrationResult;
}
