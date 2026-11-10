<?php

namespace UnifolioBundle\Enum;

/**
 * Part de l'IA dans la conception d'une trace.
 * Valeurs entières identiques à UniFolio v1 pour faciliter la migration.
 */
enum ConceptionIaEnum: int
{
    case AUCUN = 0;
    case MOYEN = 1;
    case AVANCE = 2;
    case NON_RENSEIGNE = 3;

    public function getLibelle(): string
    {
        return match ($this) {
            self::AUCUN => 'Aucune utilisation',
            self::MOYEN => 'Utilisation modérée',
            self::AVANCE => 'Utilisation avancée',
            self::NON_RENSEIGNE => 'Non renseigné',
        };
    }

    public function isRenseigne(): bool
    {
        return $this !== self::NON_RENSEIGNE;
    }
}
