<?php

namespace App\Services\Dashboard\Core;

use App\Domain\Dashboard\WidgetDataProviderInterface;
use App\Entity\Users\Personnel;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class WidgetDataRegistry
{
    /**
     * @param iterable<WidgetDataProviderInterface> $providers
     */
    public function __construct(
        #[TaggedIterator('app.dashboard.widget_data_provider')]
        private readonly iterable $providers,
    ) {}

    public function get(string $code, Personnel $user): ?array
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($code)) {
                return $provider->getData($code, $user);
            }
        }

        return null;
    }
}
