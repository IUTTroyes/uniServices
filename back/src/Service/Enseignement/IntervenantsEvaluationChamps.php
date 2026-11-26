<?php

declare(strict_types=1);

namespace App\Service\Enseignement;

use App\Entity\Scolarite\ScolEvaluation;
use App\Extension\ChampsSerialisesInterface;

final class IntervenantsEvaluationChamps implements ChampsSerialisesInterface
{
    public function __construct(private readonly AffectationsEnseignement $affectations)
    {
    }

    public function supporte(object $objet): bool
    {
        return $objet instanceof ScolEvaluation;
    }

    public function champs(object $objet, array $groupes): array
    {
        if (!$objet instanceof ScolEvaluation || array_intersect($groupes, ['evaluation:detail', 'evaluation:init']) === []) {
            return [];
        }

        return ['intervenantIdsPourInitialisation' => $this->affectations->intervenantsPourInitialisation($objet)];
    }
}
