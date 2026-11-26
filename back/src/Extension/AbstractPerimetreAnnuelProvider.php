<?php

declare(strict_types=1);

namespace App\Extension;

use Symfony\Component\HttpFoundation\Request;

abstract class AbstractPerimetreAnnuelProvider implements PerimetreAnnuelProviderInterface
{
    public function chemins(): array
    {
        return [];
    }

    public function independants(): array
    {
        return [];
    }

    public function modesDto(): array
    {
        return [];
    }

    public function consultablesSurToutesLesAnnees(): array
    {
        return [];
    }

    public function espaceDeNoms(): ?string
    {
        return null;
    }

    public function requeteHorsAnnee(string $classe, Request $request, array $corps): bool
    {
        return false;
    }

    public function conditionHorsAnnee(string $classe, string $alias): ?string
    {
        return null;
    }

    public function entiteDeContexte(string $classe, Request $request): ?object
    {
        return null;
    }
}
