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
        //ici on recupere tout les providers taggés avec app.dashboard.widget_provider
        /** @var WidgetProviderInterface $provider */
        //ici on parcoure les providers
        foreach ($providers as $provider) {
            $this->bundles[] = [
                'code' => $provider->getBundleCode(),
                'label' => $provider->getBundleLabel(),
            ];

            //ici on recupere tout les widgets du provider
            foreach ($provider->getWidgets() as $widget) {
                $this->widgets[$widget->getCode()] = $widget;
            }
        }
    }

    //ici on retourne tout les widgets
    /**
     * @return WidgetDefinition[]
     */
    public function all(): array
    {
        return array_values($this->widgets);
    }

    //ici on retourne un widget par son code
    public function get(string $code): ?WidgetDefinition
    {
        return $this->widgets[$code] ?? null;
    }

    //ici on retourne tout les bundles
    /**
     * @return array<int, array{code: string, label: string}>
     */
    public function getBundles(): array
    {
        return $this->bundles;
    }
}
