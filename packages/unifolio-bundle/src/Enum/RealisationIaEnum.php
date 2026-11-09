<?php

namespace UnifolioBundle\Enum;

/**
 * Part de l'IA dans la réalisation d'une trace.
 * Valeurs entières identiques à UniFolio v1 pour faciliter la migration.
 */
enum RealisationIaEnum: int
{
    case AUCUN = 0;
    case PEU = 1;
    case MOYEN = 2;
    case AVANCE = 3;
    case NON_RENSEIGNE = 4;

    public function getLibelle(): string
    {
        return match ($this) {
            self::AUCUN => 'Aucune utilisation',
            self::PEU => 'Utilisation ponctuelle',
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
