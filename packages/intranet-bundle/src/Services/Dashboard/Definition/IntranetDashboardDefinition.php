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
            new DashboardWidgetLayout('intranet.emploi_du_temps', 0, 'medium'),
            new DashboardWidgetLayout('intranet.documents_recents', 1, 'small'),
            new DashboardWidgetLayout('intranet.actions_urgentes', 2, 'small'),
            new DashboardWidgetLayout('intranet.notes', 3, 'medium'),
        ];
    }

    public function getDefaultLayout(): array
    {
        return [
            new DashboardWidgetLayout('intranet.emploi_du_temps', 0, 'medium'),
            new DashboardWidgetLayout('intranet.actions_urgentes', 1, 'small'),
            new DashboardWidgetLayout('intranet.notes', 3, 'medium'),
        ];
    }
}
