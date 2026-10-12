<?php

namespace IntranetBundle\Enum\PlanCours;

use App\Enum\BadgeEnumInterface;
use Symfony\Component\Serializer\Attribute\Groups;

enum PlanCoursEnum: string implements BadgeEnumInterface
{
    case NONE = 'NONE';
    case EN_COURS = 'EN_COURS';
    case COMPLET = 'COMPLET';

    #[Groups(['plancours:read', 'plancours:admin'])]
    public function getBadge(): string
    {
        return match ($this) {
            self::NONE => 'danger',
            self::EN_COURS => 'warn',
            self::COMPLET => 'success',
        };
    }

    #[Groups(['plancours:read', 'plancours:admin'])]
    public function getLibelle(): string
    {
        return match ($this) {
            self::NONE => 'Non commencé',
            self::EN_COURS => 'En cours',
            self::COMPLET => 'Complet',
        };
    }
}
