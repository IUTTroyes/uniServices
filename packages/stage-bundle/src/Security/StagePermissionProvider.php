<?php

namespace StageBundle\Security;

use App\Security\Permission\PermissionDefinition;
use App\Security\Permission\PermissionProviderInterface;

final class StagePermissionProvider implements PermissionProviderInterface
{
    public function getPermissions(): array
    {
        return [
            new PermissionDefinition(
                code: 'stage.view',
                role: 'ROLE_STAGE_VIEW',
                label: 'Voir les stages',
                package: 'stages',
                defaultWhenPackageEnabled: true,
            ),

            new PermissionDefinition(
                code: 'stage.manage',
                role: 'ROLE_STAGE_MANAGER',
                label: 'Responsable des stages',
                package: 'stages',
                inherits: [
                    'ROLE_STAGE_VIEW',
                ],
            ),

            new PermissionDefinition(
                code: 'stage.admin',
                role: 'ROLE_STAGE_ADMIN',
                label: 'Administrer les stages',
                package: 'stages',
                inherits: [
                    'ROLE_STAGE_VIEW',
                    'ROLE_STAGE_MANAGER',
                ],
            ),
        ];
    }
}