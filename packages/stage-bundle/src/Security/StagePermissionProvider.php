<?php

namespace StageBundle\Security;

use App\Security\PermissionDefinition;
use App\Security\PermissionProviderInterface;

class StagePermissionProvider implements PermissionProviderInterface
{
    public function getPermissions(): array
    {
        return [
            new PermissionDefinition('stages.view', 'ROLE_STAGE_VIEW', 'Voir les stages', 'stages', [], true),
            new PermissionDefinition('stages.manager', 'ROLE_STAGE_MANAGER', 'Responsable des stages', 'stages', ['ROLE_STAGE_VIEW']),
        ];
    }
}
