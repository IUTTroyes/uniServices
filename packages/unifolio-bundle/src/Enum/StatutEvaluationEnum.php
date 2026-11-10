<?php

namespace UnifolioBundle\Enum;

use App\Enum\BadgeEnumInterface;

enum StatutEvaluationEnum: string implements BadgeEnumInterface
{
    case EN_COURS = 'en_cours';
    case RENDUE = 'rendue';
    case REVISION_DEMANDEE = 'revision_demandee';

    public function getLibelle(): string
    {
        return match ($this) {
            self::EN_COURS => 'En cours',
            self::RENDUE => 'Rendue',
            self::REVISION_DEMANDEE => 'Révision demandée',
        };
    }

    public function getBadge(): string
    {
        return match ($this) {
            self::EN_COURS => 'warn',
            self::RENDUE => 'success',
            self::REVISION_DEMANDEE => 'danger',
        };
    }
}
