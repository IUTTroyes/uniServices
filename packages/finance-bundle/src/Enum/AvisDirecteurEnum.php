<?php

namespace FinanceBundle\Enum;

enum AvisDirecteurEnum: string
{
    case FAVORABLE = 'FAVORABLE';
    case DEFAVORABLE = 'DEFAVORABLE';
    case DEMANDE_INFO = 'DEMANDE_INFO';

    public function getLabel(): string
    {
        return match ($this) {
            self::FAVORABLE => 'Favorable',
            self::DEFAVORABLE => 'Défavorable',
            self::DEMANDE_INFO => 'Demande d’information complémentaire',
        };
    }
}
