<?php

namespace App\Security;

use App\Security\Permission\PermissionDefinition;
use App\Security\Permission\PermissionProviderInterface;

final class CorePermissionProvider implements PermissionProviderInterface
{
    public function getPermissions(): array
    {
        return [
            new PermissionDefinition(
                code: 'core.teacher',
                role: 'ROLE_TEACHER',
                label: 'Enseignant',
                package: 'core',
            ),

            new PermissionDefinition(
                code: 'core.notes.view',
                role: 'ROLE_NOTES_VIEW',
                label: 'Voir les notes',
                package: 'core',
            ),

            new PermissionDefinition(
                code: 'core.notes.manage',
                role: 'ROLE_NOTES_MANAGER',
                label: 'Responsable des notes',
                package: 'core',
                inherits: [
                    'ROLE_NOTES_VIEW',
                ],
            ),
        ];
    }
}
