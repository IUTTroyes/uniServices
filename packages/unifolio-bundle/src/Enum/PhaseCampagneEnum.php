<?php

namespace UnifolioBundle\Enum;

use App\Enum\BadgeEnumInterface;

enum PhaseCampagneEnum: string implements BadgeEnumInterface
{
    case A_VENIR = 'a_venir';
    case DEPOT = 'depot';
    case EVALUATION = 'evaluation';
    case CLOTUREE = 'cloturee';

    public function getLibelle(): string
    {
        return match ($this) {
            self::A_VENIR => 'À venir',
            self::DEPOT => 'Dépôt en cours',
            self::EVALUATION => 'Évaluation en cours',
            self::CLOTUREE => 'Clôturée',
        };
    }

    public function getBadge(): string
    {
        return match ($this) {
            self::A_VENIR => 'secondary',
            self::DEPOT => 'info',
            self::EVALUATION => 'warn',
            self::CLOTUREE => 'success',
        };
    }

    /** Les enseignants peuvent évaluer pendant le dépôt (au fil de l'eau) et pendant l'évaluation. */
    public function accepteEvaluations(): bool
    {
        return \in_array($this, [self::DEPOT, self::EVALUATION], true);
    }
}
