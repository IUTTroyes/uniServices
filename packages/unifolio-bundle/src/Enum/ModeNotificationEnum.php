<?php

namespace UnifolioBundle\Enum;

/** Comment un type de notification parvient à son destinataire. */
enum ModeNotificationEnum: string
{
    case IMMEDIAT = 'immediat';
    case RESUME = 'resume';
    case AUCUN = 'aucun';

    public function getLibelle(): string
    {
        return match ($this) {
            self::IMMEDIAT => 'Tout de suite',
            self::RESUME => 'Dans le résumé',
            self::AUCUN => 'Jamais',
        };
    }
}
