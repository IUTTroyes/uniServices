<?php

namespace App\Services\Dashboard\Provider;

use App\Domain\Dashboard\WidgetDataProviderInterface;
use App\Entity\Users\Personnel;

class PortfolioWidgetDataProvider implements WidgetDataProviderInterface
{
    public function supports(string $code): bool
    {
        return str_starts_with($code, 'portfolio.');
    }

    public function getData(string $code, Personnel $user): array
    {
        return match ($code) {
            'portfolio.to_correct' => [
                'items' => ['SAÉ 3.01 - 5 dossiers', 'PPP - 2 dossiers'],
            ],
            'portfolio.progress' => [
                'validated' => 62,
                'target' => 100,
            ],
            'portfolio.alerts' => [
                'items' => ['1 échéance cette semaine', '2 évaluations en retard'],
            ],
            default => [],
        };
    }
}
