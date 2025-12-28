<?php

namespace App\Enum;

enum QuestTypeRepeatEnum: string implements BadgeEnumInterface
{
    case MATIERE = 'matiere';
    case RESSOURCE = 'ressource';
    case SAE = 'sae';
    case PREVISIONNEL = 'previsionnel';

    public static function getTypesRepeat(): array
    {
        return [
            self::MATIERE->getLibelle(),
            self::RESSOURCE->getLibelle(),
            self::SAE->getLibelle(),
            self::PREVISIONNEL->getLibelle(),
        ];
    }

    public function getBadge(): string
    {
        return match ($this) {
            self::MATIERE => 'success',
            self::RESSOURCE => 'warn',
            self::SAE => 'danger',
            self::PREVISIONNEL => 'info',
        };
    }

    public function getLibelle(): string
    {
        return match ($this) {
            self::MATIERE => 'Matière',
            self::RESSOURCE => 'Ressource',
            self::SAE => 'SAÉ',
            self::PREVISIONNEL => 'Prévisionnel'
        };
    }
}
