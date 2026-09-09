<?php

namespace IntranetBundle\Enum;

use App\Enum\BadgeEnumInterface;

enum EtatJustificatifEnum: int implements BadgeEnumInterface
{
    case EN_ATTENTE = 0;
    case VALIDE = 1;
    case REFUSE = 2;

    public function getBadge(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'warn',
            self::VALIDE => 'success',
            self::REFUSE => 'danger',
        };
    }

    public function getLibelle(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'En attente',
            self::VALIDE => 'Validé',
            self::REFUSE => 'Refusé',
        };
    }
}
