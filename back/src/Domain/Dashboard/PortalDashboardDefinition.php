<?php

namespace App\Domain\Dashboard;

use App\Domain\Dashboard\DashboardDefinitionInterface;
use App\Domain\Dashboard\DashboardWidgetLayout;
use App\Services\Dashboard\Core\WidgetRegistry;

class PortalDashboardDefinition implements DashboardDefinitionInterface
{
    public function __construct(
        private readonly WidgetRegistry $widgetRegistry
    ) {}

    public function getCode(): string
    {
        return 'portail';
    }

    public function getAvailableWidgets(): array
    {
        $widgets = [];
        $position = 0;
        foreach ($this->widgetRegistry->all() as $widgetDefinition) {
            $size = $widgetDefinition->toArray()['size'] ?? 'medium';
            $colSpan = match($size) {
                'small' => 1,
                'medium' => 2,
                'large' => 3,
                default => 1
            };
            $rowSpan = match($size) {
                'large' => 2,
                default => 1
            };

            $widgets[] = new DashboardWidgetLayout(
                $widgetDefinition->getCode(),
                $position++,
                colSpan: $colSpan,
                rowSpan: $rowSpan,
                enabled: $widgetDefinition->toArray()['enabled'] ?? true
            );
        }

        return $widgets;
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
