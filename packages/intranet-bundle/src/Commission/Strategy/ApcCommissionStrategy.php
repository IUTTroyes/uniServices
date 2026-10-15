<?php

namespace IntranetBundle\Commission\Strategy;

use App\Entity\Structure\StructureSemestre;
use App\Entity\Structure\StructureUe;
use IntranetBundle\Commission\Decision\ApcDecisionRule;
use IntranetBundle\Commission\Enum\CommissionModeEnum;

/** Commission BUT (APC) : colonnes UE + ressources/SAÉ avec ECTS, pondération par ECTS. */
final class ApcCommissionStrategy extends AbstractCommissionStrategy
{
    public function __construct(ApcDecisionRule $decisionRule)
    {
        parent::__construct($decisionRule);
    }

    public function getMode(): CommissionModeEnum
    {
        return CommissionModeEnum::APC;
    }

    public function supports(StructureSemestre $semestre): bool
    {
        return true === $semestre->getAnnee()?->getTypeDiplome()?->isApc();
    }

    protected function buildColumns(StructureSemestre $semestre): array
    {
        return $this->buildColumnsForUes($semestre, true);
    }

    protected function getUeWeight(StructureUe $ue): float
    {
        return (float) $ue->getNbEcts();
    }
}
