<?php

namespace UnifolioBundle\Enum;

use App\Enum\BadgeEnumInterface;

enum StatutAutoEvaluationEnum: string implements BadgeEnumInterface
{
    case BROUILLON = 'brouillon';
    case SOUMISE = 'soumise';

    public function getLibelle(): string
    {
        return match ($this) {
            self::BROUILLON => 'En cours',
            self::SOUMISE => 'Soumise',
        };
    }

    public function getBadge(): string
    {
        return match ($this) {
            self::BROUILLON => 'warn',
            self::SOUMISE => 'success',
        };
    }
}
