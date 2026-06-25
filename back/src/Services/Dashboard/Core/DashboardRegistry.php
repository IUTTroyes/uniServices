<?php

namespace App\Services\Dashboard\Core;

use App\Domain\Dashboard\DashboardDefinitionInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class DashboardRegistry
{
    private array $dashboards = [];

    public function __construct(
        #[TaggedIterator('app.dashboard.definition')]
        iterable $definitions
    ) {
        foreach ($definitions as $definition) {
            $this->dashboards[$definition->getCode()] = $definition;
        }
    }

    public function get(string $code): ?DashboardDefinitionInterface
    {
        return $this->dashboards[$code] ?? null;
    }

    public function all(): array
    {
        return $this->dashboards;
    }
}
