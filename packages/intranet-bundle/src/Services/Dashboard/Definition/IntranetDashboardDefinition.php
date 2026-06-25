<?php

namespace App\Packages\IntranetBundle\Services\Dashboard\Definition;

use App\Domain\Dashboard\DashboardDefinitionInterface;
use App\Domain\Dashboard\DashboardWidgetLayout;


class IntranetDashboardDefinition implements DashboardDefinitionInterface
{
    public function getCode(): string
    {
        return 'intranet';
    }

    public function getLabel(): string
    {
        return 'Intranet';
    }

    public function getWidgets(): array
    {
        return [
            new DashboardWidgetLayout(
                'intranet.emploi_du_temps',
                0,
                'large'
            ),

            new DashboardWidgetLayout(
                'intranet.actions_urgentes',
                1,
                'medium'
            ),

            new DashboardWidgetLayout(
                'intranet.documents_recents',
                2,
                'medium'
            ),
            new DashboardWidgetLayout(
                'intranet.notes',
                3,
                'medium'
            ),
        ];
    }
}
