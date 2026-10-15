<?php

namespace IntranetBundle\Commission\Strategy;

use App\Entity\Structure\StructureSemestre;
use App\Entity\Structure\StructureUe;
use IntranetBundle\Commission\Decision\ClassiqueDecisionRule;
use IntranetBundle\Commission\Enum\CommissionModeEnum;

/** Commission des diplômes classiques : UE / matières pondérées par coefficients. */
final class ClassiqueCommissionStrategy extends AbstractCommissionStrategy
{
    public function __construct(ClassiqueDecisionRule $decisionRule)
    {
        parent::__construct($decisionRule);
    }

    public function getMode(): CommissionModeEnum
    {
        return CommissionModeEnum::CLASSIQUE;
    }

    public function supports(StructureSemestre $semestre): bool
    {
        return true !== $semestre->getAnnee()?->getTypeDiplome()?->isApc();
    }

    protected function buildColumns(StructureSemestre $semestre): array
    {
        return $this->buildColumnsForUes($semestre, false);
    }

    protected function getUeWeight(StructureUe $ue): float
    {
        return (float) $ue->getCoefficient();
    }
}
