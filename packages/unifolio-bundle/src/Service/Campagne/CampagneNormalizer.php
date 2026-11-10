<?php

namespace UnifolioBundle\Service\Campagne;

use UnifolioBundle\Entity\Campagne;

class CampagneNormalizer
{
    /** @return array<string, mixed> */
    public function resume(Campagne $c): array
    {
        $phase = $c->getPhase();
        $f = static fn (?\DateTimeImmutable $d) => $d?->format(\DATE_ATOM);

        return [
            'uuid' => $c->getUuidString(),
            'libelle' => $c->getLibelle(),
            'diplome' => [
                'id' => $c->getDiplome()->getId(),
                'libelle' => $c->getDiplome()->getLibelle(),
                'parcours' => $c->getDiplome()->getParcours()?->getLibelle(),
                'departement' => $c->getDiplome()->getDepartement()?->getLibelle(),
            ],
            'anneeUniversitaire' => ['id' => $c->getAnneeUniversitaire()->getId(), 'libelle' => $c->getAnneeUniversitaire()->getLibelle()],
            'anneeFormation' => $c->getAnneeFormation() !== null ? ['id' => $c->getAnneeFormation()->getId(), 'libelle' => $c->getAnneeFormation()->getLibelle()] : null,
            'ouvertureLe' => $f($c->getOuvertureLe()),
            'depotLimite' => $f($c->getDepotLimite()),
            'evaluationLimite' => $f($c->getEvaluationLimite()),
            'clotureLe' => $f($c->getClotureLe()),
            'autoEvaluationRequise' => $c->isAutoEvaluationRequise(),
            'modele' => $c->getModele() !== null ? ['uuid' => $c->getModele()->getUuidString(), 'libelle' => $c->getModele()->getLibelle()] : null,
            'phase' => ['value' => $phase->value, 'libelle' => $phase->getLibelle(), 'badge' => $phase->getBadge()],
            'enRetard' => $c->isEvaluationEnRetard(),
        ];
    }
}
