<?php

namespace EdtBundle\Security;

use App\Security\PermissionDefinition;
use App\Security\PermissionProviderInterface;

class EdtPermissionProvider implements PermissionProviderInterface
{
    public function getPermissions(): array
    {
        return [
            new PermissionDefinition('edt.manage', 'ROLE_EDT', 'Planificateur d\'EDT', 'edt', []),
        ];
    }
}
