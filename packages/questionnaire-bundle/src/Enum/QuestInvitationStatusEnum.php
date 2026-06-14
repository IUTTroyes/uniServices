<?php

namespace QuestionnaireBundle\Enum;

use App\Enum\BadgeEnumInterface;

enum QuestInvitationStatusEnum: string implements BadgeEnumInterface
{
    case PENDING = 'pending';
    case STARTED = 'started';
    case SUBMITTED = 'submitted';

    public static function getStatuts(): array
    {
        return [
            self::PENDING->getLibelle(),
            self::STARTED->getLibelle(),
            self::SUBMITTED->getLibelle(),
        ];
    }

    public function getBadge(): string
    {
        return match ($this) {
            self::SUBMITTED => 'success',
            self::STARTED => 'info',
            self::PENDING => 'warn',
        };
    }

    public function getLibelle(): string
    {
        return match ($this) {
            self::SUBMITTED => 'Terminé',
            self::STARTED => 'En cours',
            self::PENDING => 'En attente'
        };
    }
}

