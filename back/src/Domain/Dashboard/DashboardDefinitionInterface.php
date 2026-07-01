<?php

namespace App\Domain\Dashboard;

interface DashboardDefinitionInterface
{
    public function getCode(): string;

    /**
     * Widgets affichés au premier affichage.
     *
     * @return DashboardWidgetLayout[]
     */
    public function getDefaultLayout(): array;

    /**
     * Widgets configurables pour ce dashboard.
     *
     * @return DashboardWidgetLayout[]
     */
    public function getAvailableWidgets(): array;
}
