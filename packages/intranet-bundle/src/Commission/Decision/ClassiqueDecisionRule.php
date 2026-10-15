<?php

namespace IntranetBundle\Commission\Decision;

use IntranetBundle\Commission\Contract\CommissionDecisionRuleInterface;
use IntranetBundle\Commission\Enum\CommissionModeEnum;

/**
 * Diplômes classiques : le semestre est validé si la moyenne générale est >= 10.
 * Les UE sont comptabilisées à titre informatif.
 */
final class ClassiqueDecisionRule implements CommissionDecisionRuleInterface
{
    public const SEUIL_SEMESTRE = 10.0;
    public const SEUIL_UE = 10.0;

    public function getMode(): CommissionModeEnum
    {
        return CommissionModeEnum::CLASSIQUE;
    }

    public function decide(array $moyennesUe, array $coefficients, ?float $moyenneGenerale): array
    {
        $validees = count(array_filter($moyennesUe, static fn ($m) => null !== $m && $m >= self::SEUIL_UE));

        if (null === $moyenneGenerale) {
            return ['decision' => null, 'libelle' => 'Incomplet', 'uesValidees' => $validees];
        }

        $ok = $moyenneGenerale >= self::SEUIL_SEMESTRE;

        return [
            'decision' => $ok,
            'libelle' => $ok ? 'Semestre validé' : 'Semestre non validé',
            'uesValidees' => $validees,
        ];
    }
}
