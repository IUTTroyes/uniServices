<?php

namespace App\Enum;

use App\Enum\BadgeEnumInterface;

enum TypeEnseignementEnum: string implements BadgeEnumInterface {
    case TYPE_RESSOURCE = 'ressource';
    case TYPE_SAE = 'sae';
    case TYPE_MATIERE = 'matiere';


    public function getLibelle(): string
    {
        return match ($this) {
            self::TYPE_RESSOURCE => 'Ressource',
            self::TYPE_SAE => 'SAÉ',
            self::TYPE_MATIERE => 'Matière',
        };
    }

    public function getBadge(): string
    {
        return match ($this) {
            self::TYPE_RESSOURCE => 'primary',
            self::TYPE_SAE => 'warn',
            self::TYPE_MATIERE => 'success',
        };
    }
}
