<?php

namespace App\Security;

class CorePermissionProvider implements PermissionProviderInterface
{
    public function getPermissions(): array
    {
        return [
            new PermissionDefinition('core.teacher', 'ROLE_TEACHER', 'Enseignant', 'core', [], true),
            new PermissionDefinition('core.assistant', 'ROLE_ASSISTANT', 'Assistant', 'core', []),
            new PermissionDefinition('core.scolarite', 'ROLE_SCOLARITE', 'Scolarité', 'core', []),
            new PermissionDefinition('core.direction', 'ROLE_DIRECTION', 'Direction', 'core', []),
            new PermissionDefinition('core.chef_departement', 'ROLE_CHEF_DEPARTEMENT', 'Chef Département', 'core', []),
            new PermissionDefinition('core.directeur_etudes', 'ROLE_DIRECTEUR_ETUDES', 'Directeur Études', 'core', []),
            new PermissionDefinition('core.resp_parcours', 'ROLE_RESP_PARCOURS', 'Responsable Parcours', 'core', []),
            new PermissionDefinition('core.referent', 'ROLE_REFERENT', 'Référent', 'core', []),
            new PermissionDefinition('core.notes_manager', 'ROLE_NOTES_MANAGER', 'Responsable des notes', 'core', []),
        ];
    }
}
