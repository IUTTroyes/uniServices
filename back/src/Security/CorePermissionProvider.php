<?php

namespace App\Security;

class CorePermissionProvider implements PermissionProviderInterface
{
    public function getPermissions(): array
    {
        return [
            new PermissionDefinition('intranet.super_admin', 'SUPER_ADMIN', 'Super Administrateur', 'intranet', []),
            new PermissionDefinition('intranet.admin', 'ROLE_ADMIN', 'Administrateur', 'intranet', [
                'ROLE_DIRECTION',
                'ROLE_CHEF_DEPARTEMENT',
                'ROLE_CHEF_DEPT',
                'ROLE_DIRECTEUR_ETUDES',
                'ROLE_SCOLARITE',
                'ROLE_ASSISTANT',
                'ROLE_NOTES_MANAGER',
                'ROLE_EMAIL_ADMIN',
                'ROLE_PERMANENT',
            ]),
            new PermissionDefinition('intranet.permanent', 'ROLE_PERMANENT', 'Personnel permanent', 'intranet', []),
            new PermissionDefinition('intranet.teacher', 'ROLE_TEACHER', 'Enseignant', 'intranet', [], true),
            new PermissionDefinition('intranet.assistant', 'ROLE_ASSISTANT', 'Assistant de formation', 'intranet', []),
            new PermissionDefinition('intranet.scolarite', 'ROLE_SCOLARITE', 'Scolarité', 'intranet', []),
            new PermissionDefinition('intranet.direction', 'ROLE_DIRECTION', 'Direction', 'intranet', []),
            new PermissionDefinition('intranet.chef_departement', 'ROLE_CHEF_DEPARTEMENT', 'Chef Département', 'intranet', ['ROLE_CHEF_DEPT']),
            new PermissionDefinition('intranet.chef_dept', 'ROLE_CHEF_DEPT', 'Chef Département', 'intranet', []),
            new PermissionDefinition('intranet.directeur_etudes', 'ROLE_DIRECTEUR_ETUDES', 'Directeur Études', 'intranet', []),
            new PermissionDefinition('intranet.resp_parcours', 'ROLE_RESP_PARCOURS', 'Responsable de parcours', 'intranet', []),
            new PermissionDefinition('intranet.referent', 'ROLE_REFERENT', 'Référent UniServices', 'intranet', []),
            new PermissionDefinition('intranet.notes_manager', 'ROLE_NOTES_MANAGER', 'Responsable des notes', 'intranet', []),
            new PermissionDefinition('intranet.absences', 'ROLE_ABSENCE', 'Responsable des absences', 'intranet', []),
            new PermissionDefinition('intranet.edt', 'ROLE_EDT', 'Responsable des emplois du temps', 'intranet', []),
            new PermissionDefinition('intranet.edusign', 'ROLE_EDUSIGN', 'Référent Edusign', 'intranet', []),
            new PermissionDefinition('intranet.communication', 'ROLE_RELAI_COMM', 'Référent communication', 'intranet', []),
            new PermissionDefinition('intranet.referent_intranet', 'ROLE_REFERENT_INTRANET', 'Référent Intranet', 'intranet', []),
            new PermissionDefinition('intranet.referent_projet', 'ROLE_REFERENT_PROJET', 'Référent Projet', 'intranet', []),
            new PermissionDefinition('intranet.informatique', 'ROLE_INFORMATIQUE', 'Service informatique', 'intranet', []),
            new PermissionDefinition('intranet.compta', 'ROLE_COMPTA', 'Service comptabilité', 'intranet', ['ROLE_COMPTABILITE']),
            new PermissionDefinition('intranet.comptabilite', 'ROLE_COMPTABILITE', 'Service comptabilité (compatibilité)', 'intranet', []),
            new PermissionDefinition('intranet.qualite', 'ROLE_QUALITE', 'Service qualité', 'intranet', []),
            new PermissionDefinition('intranet.email_admin', 'ROLE_EMAIL_ADMIN', 'Gestionnaire des emails', 'intranet', []),
        ];
    }
}
