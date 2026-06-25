<?php

namespace App\Security\Permission;

final readonly class PermissionDefinition
{
    public function __construct(
        public string $code,
        public string $role,
        public string $label,
        public string $package,
        public array $inherits = [],
        public bool $defaultWhenPackageEnabled = false,
    ) {}
}
