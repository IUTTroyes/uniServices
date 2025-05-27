<?php

namespace App\Enum;

use App\Enum\BadgeEnumInterface;

enum FicheHeureStatutEnum: string implements BadgeEnumInterface
{
    case BROUILLON = 'Brouillon';
    case SOUMISE = 'Soumise';
    case VALIDEE = 'Validée';
    case REJETEE = 'Rejetée';

    public function getLibelle(): string
    {
        return match ($this) {
            self::BROUILLON => 'Brouillon',
            self::SOUMISE => 'Soumise',
            self::VALIDEE => 'Validée',
            self::REJETEE => 'Rejetée',
        };
    }

    public function getBadge(): string
    {
        return match ($this) {
            self::BROUILLON => 'secondary',
            self::SOUMISE => 'info',
            self::VALIDEE => 'success',
            self::REJETEE => 'danger',
        };
    }
}
