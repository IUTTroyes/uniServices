<?php

namespace IntranetBundle\Services\Dashboard\Definition;

use App\Domain\Dashboard\DashboardDefinitionInterface;
use App\Domain\Dashboard\DashboardWidgetLayout;


class IntranetDashboardDefinition implements DashboardDefinitionInterface
{
    public function getCode(): string
    {
        return 'intranet';
    }

    public function getAvailableWidgets(): array
    {
        return [
            'intranet.emploi_du_temps',
            'intranet.documents_recents',
            'users.actions_urgentes',
            'etudiant.notes',
        ];
    }

    public function getDefaultLayout(): array
    {
        return [
            new DashboardWidgetLayout('intranet.emploi_du_temps', 0, 'medium'),
            new DashboardWidgetLayout('users.actions_urgentes', 1, 'small'),
            new DashboardWidgetLayout('intranet.documents_recents', 2, 'small'),
            new DashboardWidgetLayout('etudiant.notes', 3, 'medium'),
        ];
    }
}
