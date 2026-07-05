<?php

namespace App\Security;

interface PermissionProviderInterface
{
    /**
     * @return PermissionDefinition[]
     */
    public function getPermissions(): array;
}
