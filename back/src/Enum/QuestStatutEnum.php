<?php

namespace App\Enum;

enum QuestStatutEnum: string implements BadgeEnumInterface
{
    case PUBLISHED = 'published';
    case DRAFT = 'draft';
    case CLOSED = 'closed';

    public static function getStatuts(): array
    {
        return [
            self::PUBLISHED->getLibelle(),
            self::DRAFT->getLibelle(),
            self::CLOSED->getLibelle(),
        ];
    }

    public function getBadge(): string
    {
        return match ($this) {
            self::PUBLISHED => 'success',
            self::DRAFT => 'warn',
            self::CLOSED => 'danger',
        };
    }

    public function getLibelle(): string
    {
        return match ($this) {
            self::PUBLISHED => 'Publié',
            self::DRAFT => 'Brouillon',
            self::CLOSED => 'Fermé'
        };
    }
}
