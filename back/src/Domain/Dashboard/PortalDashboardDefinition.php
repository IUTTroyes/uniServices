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

    public function getWidgets(): array
    {
        return [
            new DashboardWidgetLayout(
                'intranet.emploi_du_temps',
                0,
                'medium'
            ),

            new DashboardWidgetLayout(
                'intranet.actions_urgentes',
                1,
                'small'
            ),

            new DashboardWidgetLayout(
                'portfolio.progress',
                2,
                'small'
            ),

            new DashboardWidgetLayout(
                'questionnaire.pending',
                3,
                'medium'
            ),
        ];
    }
}
