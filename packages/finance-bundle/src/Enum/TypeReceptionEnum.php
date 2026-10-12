<?php

namespace FinanceBundle\Enum;

enum TypeReceptionEnum: string
{
    case TOTALE = 'TOTALE';
    case PARTIELLE = 'PARTIELLE';

    public function getLabel(): string
    {
        return match ($this) {
            self::TOTALE => 'Totalité de la commande',
            self::PARTIELLE => 'Partie des biens/prestations',
        };
    }
}
