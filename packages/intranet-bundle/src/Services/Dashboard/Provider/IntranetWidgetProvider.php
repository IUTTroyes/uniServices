<?php

namespace IntranetBundle\Services\Dashboard\Provider;

use App\Domain\Dashboard\WidgetDefinition;
use App\Domain\Dashboard\WidgetProviderInterface;

class IntranetWidgetProvider implements WidgetProviderInterface
{
    public function getBundleCode(): string
    {
        return 'intranet';
    }

    public function getBundleLabel(): string
    {
        return 'Intranet';
    }

    public function getWidgets(): array
    {
        return [
            new WidgetDefinition('intranet.emploi_du_temps', 'intranet', 'Aujourd\'hui', 'pi pi-calendar', 'EmploiDuTempsWidget', 'large', true, defaultConfig: ['position' => 1]),
            new WidgetDefinition('intranet.listings_etudiants', 'intranet', 'Listings & Émargements étudiants', 'pi pi-file-export', 'ListingsEtudiantsWidget', 'large', true, defaultConfig: ['position' => 2, 'colSpan' => 2, 'rowSpan' => 2], allowedProfiles: [WidgetDefinition::PROFILE_PERSONNEL], requiredPermissions: ['ROLE_TEACHER', 'ROLE_ASSISTANT', 'ROLE_SCOLARITE', 'ROLE_CHEF_DEPARTEMENT', 'ROLE_DIRECTEUR_ETUDES']),
            new WidgetDefinition('intranet.actions_urgentes', 'intranet', 'Actions urgentes', 'pi pi-sparkles', 'ActionsUrgentesWidget', 'medium', true, defaultConfig: ['position' => 3], allowedProfiles: [WidgetDefinition::PROFILE_PERSONNEL], requiredPermissions: ['ROLE_RESP_PARCOURS', 'ROLE_DIRECTION', 'ROLE_CHEF_DEPARTEMENT', 'ROLE_CHEF_DEPT', 'ROLE_DIRECTEUR_ETUDES', 'ROLE_ASSISTANT', 'ROLE_SCOLARITE', 'ROLE_STAGE_MANAGER', 'ROLE_ABSENCE', 'ROLE_NOTES_MANAGER']),
            new WidgetDefinition('intranet.notes', 'intranet', 'Notes', 'pi pi-pencil', 'NotesWidget', 'small', true, defaultConfig: ['position' => 5], allowedProfiles: [WidgetDefinition::PROFILE_ETUDIANT]),
        ];
    }
}
