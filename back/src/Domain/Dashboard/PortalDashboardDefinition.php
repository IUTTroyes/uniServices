<?php

namespace App\Domain\Dashboard;

use App\Domain\Dashboard\DashboardDefinitionInterface;
use App\Domain\Dashboard\DashboardWidgetLayout;


class PortalDashboardDefinition implements DashboardDefinitionInterface
{
    public function getCode(): string
    {
        return 'portail';
    }

    public function getAvailableWidgets(): array
    {
        return [
            new DashboardWidgetLayout(
                'intranet.emploi_du_temps',
                0,
                colSpan: 2,
                rowSpan: 1,
            ),

            new DashboardWidgetLayout(
                'intranet.actions_urgentes',
                1,
                colSpan: 1,
                rowSpan: 2,
            ),

            new DashboardWidgetLayout(
                'portfolio.progress',
                2,
                colSpan: 1,
                rowSpan: 1,
            ),

            new DashboardWidgetLayout(
                'questionnaire.pending',
                3,
                colSpan: 2,
                rowSpan: 1,
            ),
        ];
    }

    public function getDefaultLayout(): array
    {
        return [
            new DashboardWidgetLayout(
                'intranet.emploi_du_temps',
                0,
                colSpan: 2,
                rowSpan: 1,
            ),

            new DashboardWidgetLayout(
                'portfolio.progress',
                2,
                colSpan: 1,
                rowSpan: 1,
            ),

            new DashboardWidgetLayout(
                'questionnaire.pending',
                3,
                colSpan: 2,
                rowSpan: 1,
            ),
        ];
    }
}
