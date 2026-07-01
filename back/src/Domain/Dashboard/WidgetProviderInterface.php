<?php

namespace App\Domain\Dashboard;

interface WidgetProviderInterface
{
    /**
     * @return WidgetDefinition[]
     */
    public function getWidgets(): array;

    public function getBundleCode(): string;

    public function getBundleLabel(): string;
}
