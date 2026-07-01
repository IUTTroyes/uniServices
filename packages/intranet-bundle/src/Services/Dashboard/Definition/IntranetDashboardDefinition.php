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
            new DashboardWidgetLayout('intranet.emploi_du_temps', 0, colSpan: 2, rowSpan: 1),
            new DashboardWidgetLayout('intranet.documents_recents', 1, colSpan: 1, rowSpan: 1),
            new DashboardWidgetLayout('intranet.actions_urgentes', 2, colSpan: 1, rowSpan: 2),
            new DashboardWidgetLayout('intranet.notes', 3, colSpan: 2, rowSpan: 1),
        ];
    }

    public function getDefaultLayout(): array
    {
        return [
            new DashboardWidgetLayout('intranet.emploi_du_temps', 0, colSpan: 2, rowSpan: 1),
            new DashboardWidgetLayout('intranet.actions_urgentes', 1, colSpan: 1, rowSpan: 2),
            new DashboardWidgetLayout('intranet.notes', 3, colSpan: 2, rowSpan: 1),
        ];
    }
}
