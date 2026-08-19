<?php

namespace App\Security;

class CorePermissionProvider implements PermissionProviderInterface
{
    public function getPermissions(): array
    {
        return [
            new PermissionDefinition('intranet.super_admin', 'SUPER_ADMIN', 'Super administrateur', 'intranet', ['ROLE_ADMIN']),
            new PermissionDefinition('intranet.admin', 'ROLE_ADMIN', 'Administrateur', 'intranet', [
                'ROLE_DIRECTION',
                'ROLE_CHEF_DEPARTEMENT',
                'ROLE_CHEF_DEPT',
                'ROLE_DIRECTEUR_ETUDES',
                'ROLE_RESP_PARCOURS',
                'ROLE_SCOLARITE',
                'ROLE_ASSISTANT',
                'ROLE_NOTES_MANAGER',
                'ROLE_EMAIL_ADMIN',
                'ROLE_PERMANENT',
            ]),
            new PermissionDefinition('intranet.permanent', 'ROLE_PERMANENT', 'Personnel permanent', 'intranet', []),
            new PermissionDefinition('intranet.teacher', 'ROLE_TEACHER', 'Enseignant', 'intranet', [], true),
            new PermissionDefinition('intranet.assistant', 'ROLE_ASSISTANT', 'Assistant', 'intranet', []),
            new PermissionDefinition('intranet.scolarite', 'ROLE_SCOLARITE', 'Scolarité', 'intranet', []),
            new PermissionDefinition('intranet.direction', 'ROLE_DIRECTION', 'Direction', 'intranet', []),
            new PermissionDefinition('intranet.chef_departement', 'ROLE_CHEF_DEPARTEMENT', 'Chef Département', 'intranet', ['ROLE_CHEF_DEPT']),
            new PermissionDefinition('intranet.chef_dept', 'ROLE_CHEF_DEPT', 'Chef Département', 'intranet', []),
            new PermissionDefinition('intranet.directeur_etudes', 'ROLE_DIRECTEUR_ETUDES', 'Directeur Études', 'intranet', []),
            new PermissionDefinition('intranet.resp_parcours', 'ROLE_RESP_PARCOURS', 'Responsable Parcours', 'intranet', []),
            new PermissionDefinition('intranet.referent', 'ROLE_REFERENT', 'Référent', 'intranet', []),
            new PermissionDefinition('intranet.notes_manager', 'ROLE_NOTES_MANAGER', 'Responsable des notes', 'intranet', []),
            new PermissionDefinition('intranet.email_admin', 'ROLE_EMAIL_ADMIN', 'Gestionnaire des emails', 'intranet', []),
        ];
    }
}
