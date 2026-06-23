<?php

namespace App\Domain\Dashboard;

class WidgetDefinition
{
    public function __construct(
        private readonly string $code,
        private readonly string $bundle,
        private readonly string $label,
        private readonly string $icon,
        private readonly string $component,
        private readonly string $size = 'medium',
        private readonly bool $enabled = true,
    ) {}

    public function getCode(): string
    {
        return $this->code;
    }

    public function getBundle(): string
    {
        return $this->bundle;
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'bundle' => $this->bundle,
            'label' => $this->label,
            'icon' => $this->icon,
            'component' => $this->component,
            'size' => $this->size,
            'enabled' => $this->enabled,
        ];
    }
}
