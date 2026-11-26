<?php

declare(strict_types=1);

namespace App\Service\Enseignement;

use App\Entity\Scolarite\ScolEnseignement;
use App\Entity\Scolarite\ScolEvaluation;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Users\Personnel;
use App\Enum\EtatEvaluationEnum;
use App\Extension\AffectationsEnseignementInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/** Accès aux affectations, quelle que soit la source installée. Sans source, personne n'est affecté. */
class AffectationsEnseignement
{
    /** @param iterable<AffectationsEnseignementInterface> $sources */
    public function __construct(
        #[AutowireIterator('app.affectations_enseignement')]
        private readonly iterable $sources = [],
    ) {
    }

    public function source(): ?AffectationsEnseignementInterface
    {
        foreach ($this->sources as $source) {
            return $source;
        }

        return null;
    }

    /** @return list<Personnel> */
    public function personnels(ScolEnseignement $enseignement, ?StructureAnneeUniversitaire $annee): array
    {
        return $this->source()?->personnels($enseignement, $annee) ?? [];
    }

    /** @return list<int> personnels proposés tant que l'évaluation n'est pas initialisée */
    public function intervenantsPourInitialisation(ScolEvaluation $evaluation): array
    {
        $annee = $evaluation->getAnneeUniversitaire();
        $enseignement = $evaluation->getEnseignement();
        if ($evaluation->getEtat() !== EtatEvaluationEnum::ETAT_NON_INITIALISEE || $annee?->getId() === null || $enseignement === null) {
            return [];
        }

        $ids = [];
        foreach ($this->personnels($enseignement, $annee) as $personnel) {
            if ($personnel->getId() !== null) {
                $ids[$personnel->getId()] = $personnel->getId();
            }
        }

        return array_values($ids);
    }
}
