<?php

interface DashboardDefinitionInterface
{
    public function getCode(): string;

    public function getLabel(): string;

    /**
     * @return DashboardWidgetLayout[]
     */
    public function getWidgets(): array;
}
