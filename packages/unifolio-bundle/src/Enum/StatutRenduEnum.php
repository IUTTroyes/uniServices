<?php

namespace UnifolioBundle\Enum;

/** État de la génération statique d'une publication de portfolio (D2). */
enum StatutRenduEnum: string
{
    case EN_ATTENTE = 'en_attente';
    case GENERE = 'genere';
    case ERREUR = 'erreur';

    public function getLibelle(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'Génération en cours',
            self::GENERE => 'Généré',
            self::ERREUR => 'Erreur de génération',
        };
    }
}
