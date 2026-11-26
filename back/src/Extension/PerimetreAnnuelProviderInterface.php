<?php

declare(strict_types=1);

namespace App\Extension;

use Symfony\Component\HttpFoundation\Request;

/** Règles de contexte annuel apportées par un bundle pour ses propres classes. */
interface PerimetreAnnuelProviderInterface
{
    /** @return array<class-string|string, string> classe => chemin ORM vers l'année universitaire */
    public function chemins(): array;

    /** @return list<class-string|string> classes sans année universitaire */
    public function independants(): array;

    /** @return array<string, string> DTO => mode de contexte */
    public function modesDto(): array;

    /** @return list<class-string|string> classes dont la consultation « toutes les années » est autorisée */
    public function consultablesSurToutesLesAnnees(): array;

    /** Les contrôleurs de cet espace de noms doivent déclarer #[ContexteAnnuel]. */
    public function espaceDeNoms(): ?string;

    /** La collection ou l'écriture demandée sort du contexte annuel (modèle, gabarit...). */
    public function requeteHorsAnnee(string $classe, Request $request, array $corps): bool;

    /** Restriction à appliquer à la place du filtre annuel lorsque requeteHorsAnnee() vaut pour une collection. */
    public function conditionHorsAnnee(string $classe, string $alias): ?string;

    /** Entité qui porte l'année d'une opération sur un DTO. */
    public function entiteDeContexte(string $classe, Request $request): ?object;
}
