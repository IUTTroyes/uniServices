<?php

namespace App\Domain\Dashboard;

class DashboardWidgetLayout
{
    public function __construct(
        public readonly string $widgetCode,
        public readonly int $position,
        public readonly int $colSpan = 1,
        public readonly int $rowSpan = 1,
        public readonly bool $enabled = true,
    ) {}
}
