<?php

namespace IntranetBundle\Commission\Contract;

use IntranetBundle\Commission\Enum\CommissionModeEnum;

/**
 * Règle de décision (proposition de validation) appliquée à un étudiant.
 * Isolée des stratégies d'affichage pour pouvoir être modifiée / remplacée indépendamment.
 */
interface CommissionDecisionRuleInterface
{
    public function getMode(): CommissionModeEnum;

    /**
     * @param array<int, float|null> $moyennesUe  moyennes par id d'UE
     * @param array<int, float>      $coefficients coefficient (ou ECTS selon le mode) par id d'UE
     * @param float|null             $moyenneGenerale moyenne générale du semestre
     *
     * @return array{decision: bool|null, libelle: string, uesValidees: int}
     */
    public function decide(array $moyennesUe, array $coefficients, ?float $moyenneGenerale): array;
}
