<?php

namespace App\Enum;

use App\Enum\BadgeEnumInterface;
use Symfony\Component\Serializer\Attribute\Groups;

enum TypeEnseignementEnum: string implements BadgeEnumInterface {
    case TYPE_RESSOURCE = 'ressource';
    case TYPE_SAE = 'sae';
    case TYPE_MATIERE = 'matiere';

    #[Groups(['enseignement:detail'])]
    public function getLibelle(): string
    {
        return match ($this) {
            self::TYPE_RESSOURCE => 'Ressource',
            self::TYPE_SAE => 'SAÉ',
            self::TYPE_MATIERE => 'Matière',
        };
    }

    #[Groups(['enseignement:detail'])]
    public function getBadge(): string
    {
        return match ($this) {
            self::TYPE_RESSOURCE => 'primary',
            self::TYPE_SAE => 'warn',
            self::TYPE_MATIERE => 'success',
        };
    }
}
