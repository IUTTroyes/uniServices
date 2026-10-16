<?php

namespace IntranetBundle\Commission\Enum;

/**
 * Mode de fonctionnement d'une commission (sous-commission) de calcul des moyennes.
 *
 * - APC : Approche Par Compétences (BUT) : affichage par UE / compétences, ECTS, validation par UE.
 * - CLASSIQUE : diplômes hors BUT : affichage par UE / matières avec moyenne générale du semestre.
 *
 * Pour ajouter un mode : ajouter un cas ici puis créer une stratégie dédiée
 * (voir CommissionStrategyInterface), aucune autre modification n'est nécessaire.
 */
enum CommissionModeEnum: string
{
    case APC = 'apc';
    case CLASSIQUE = 'classique';

    public function getLibelle(): string
    {
        return match ($this) {
            self::APC => 'Approche par compétences (BUT)',
            self::CLASSIQUE => 'Classique',
        };
    }
}
