<?php

namespace FinanceBundle\Enum;

enum TypePrestationEnum: string
{
    case FOURNITURES = 'Fournitures';
    case SERVICES = 'Services';
    case TRAVAUX = 'Travaux';

    public function getLabel(): string
    {
        return match ($this) {
            self::FOURNITURES => 'Fournitures',
            self::SERVICES => 'Services',
            self::TRAVAUX => 'Travaux',
        };
    }
}
