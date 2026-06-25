<?php

namespace App\Domain\Dashboard;

interface DashboardDefinitionInterface
{
    public function getCode(): string;

    /**
     * @return DashboardWidgetLayout[]
     */
    public function getWidgets(): array;
}
