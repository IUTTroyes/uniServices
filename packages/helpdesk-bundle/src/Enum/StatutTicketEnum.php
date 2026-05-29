<?php

namespace HelpdeskBundle\Enum;

enum StatutTicketEnum: string
{
    case A_TRAITER= 'À Traiter';
    case EN_ATTENTE = 'En attente';
    case EN_COURS = 'En cours de traitement';
    case REFUSE='Refusé';
    case CLOTURE='Cloturé';

    public function getStatuts(): array
    {
        return [
            self::A_TRAITER,
            self::EN_ATTENTE,
            self::EN_COURS,
            self::REFUSE,
            self::CLOTURE,

        ];
    }
}

