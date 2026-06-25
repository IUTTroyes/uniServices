<?php

final class DashboardWidgetLayout
{
    public function __construct(
        public readonly string $widgetCode,
        public readonly int $position,
        public readonly string $size = 'medium',
        public readonly bool $enabled = true,
    ) {}
}
