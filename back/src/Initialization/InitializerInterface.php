<?php

namespace App\Initialization;

use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;

interface InitializerInterface
{
    public function getName(): string;

    public function initialize(MigrationContext $context): MigrationResult;
}
