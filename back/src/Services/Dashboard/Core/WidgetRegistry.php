<?php

namespace App\Services\Dashboard\Core;

use App\Domain\Dashboard\WidgetDefinition;
use App\Domain\Dashboard\WidgetProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class WidgetRegistry
{
    /**
     * @var array<string, WidgetDefinition>
     */
    private array $widgets = [];

    /**
     * @var array<int, array{code: string, label: string}>
     */
    private array $bundles = [];

    public function __construct(
        #[TaggedIterator('app.dashboard.widget_provider')]
        iterable $providers,
    ) {
        /** @var WidgetProviderInterface $provider */
        foreach ($providers as $provider) {
            $this->bundles[] = [
                'code' => $provider->getBundleCode(),
                'label' => $provider->getBundleLabel(),
            ];

            foreach ($provider->getWidgets() as $widget) {
                $this->widgets[$widget->getCode()] = $widget;
            }
        }
    }

    /**
     * @return WidgetDefinition[]
     */
    public function all(): array
    {
        return array_values($this->widgets);
    }

    public function get(string $code): ?WidgetDefinition
    {
        return $this->widgets[$code] ?? null;
    }

    /**
     * @return array<int, array{code: string, label: string}>
     */
    public function getBundles(): array
    {
        return $this->bundles;
    }
}
