<?php

namespace IntranetBundle\Commission\Decision;

use IntranetBundle\Commission\Contract\CommissionDecisionRuleInterface;
use IntranetBundle\Commission\Enum\CommissionModeEnum;

/**
 * BUT : le semestre est validé si la moyenne de chaque UE est >= 10 (validation par UE),
 * ou, par compensation, si la moyenne pondérée (ECTS) est >= 10 et qu'aucune UE n'est < 8.
 *
 * NB : règle indicative, à ajuster selon la réglementation locale (seuils en constantes).
 */
final class ApcDecisionRule implements CommissionDecisionRuleInterface
{
    public const SEUIL_UE = 10.0;
    public const SEUIL_MINIMUM_COMPENSATION = 8.0;

    public function getMode(): CommissionModeEnum
    {
        return CommissionModeEnum::APC;
    }

    public function decide(array $moyennesUe, array $coefficients, ?float $moyenneGenerale): array
    {
        $values = array_filter($moyennesUe, static fn ($m) => null !== $m);
        if ([] === $values || count($values) < count($moyennesUe)) {
            return ['decision' => null, 'libelle' => 'Incomplet', 'uesValidees' => 0];
        }

        $validees = count(array_filter($values, static fn (float $m) => $m >= self::SEUIL_UE));
        if ($validees === count($moyennesUe)) {
            return ['decision' => true, 'libelle' => 'Semestre validé', 'uesValidees' => $validees];
        }

        $minimum = min($values);
        if (null !== $moyenneGenerale && $moyenneGenerale >= self::SEUIL_UE && $minimum >= self::SEUIL_MINIMUM_COMPENSATION) {
            return ['decision' => true, 'libelle' => 'Validé par compensation', 'uesValidees' => $validees];
        }

        return ['decision' => false, 'libelle' => 'Non validé', 'uesValidees' => $validees];
    }
}
