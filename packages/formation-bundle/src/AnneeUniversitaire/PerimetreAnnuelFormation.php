<?php

declare(strict_types=1);

namespace FormationBundle\AnneeUniversitaire;

use App\Extension\AbstractPerimetreAnnuelProvider;

/**
 * Chaque ressource du package doit être déclarée ici : chemins() si elle dépend d'une année universitaire,
 * independants() sinon. Les contrôleurs du package portent l'attribut #[ContexteAnnuel].
 */
final class PerimetreAnnuelFormation extends AbstractPerimetreAnnuelProvider
{
    public function espaceDeNoms(): string
    {
        return 'FormationBundle';
    }
}
