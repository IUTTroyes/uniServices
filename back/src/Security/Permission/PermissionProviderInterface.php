<?php

namespace App\Security\Permission;

interface PermissionProviderInterface
{
    /**
     * @return PermissionDefinition[]
     */
    public function getPermissions(): array;
}