<?php

namespace HelpdeskBundle\Enum;

use Symfony\Component\Serializer\Attribute\Groups;

enum StatutTicketEnum: string
{
    case A_TRAITER= 'À Traiter';
    case EN_ATTENTE = 'En attente';
    case EN_COURS = 'En cours de traitement';
    case REFUSE='Refusé';
    case ACCEPTE='Accepté';
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
/*Retourne les statuts accessibles depuis le statut actuel*/
    public function getTransitionsAutorisees(): array{
        return match($this)
        {
            self::A_TRAITER  => [self::ACCEPTE, self::REFUSE],
            self::EN_ATTENTE => [self::EN_COURS, self::REFUSE],
            self::EN_COURS => [self::EN_ATTENTE, self::CLOTURE, self::REFUSE],
            self::REFUSE => [self::ACCEPTE],
            self::CLOTURE => [self::EN_COURS],
        };
    }

}

