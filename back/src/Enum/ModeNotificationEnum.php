<?php

namespace App\Enum;

/** Comment une notification parvient à son destinataire par e-mail (la cloche de l'application reçoit tout, dans tous les cas). */
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
