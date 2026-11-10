<?php

namespace UnifolioBundle\Enum;

/**
 * Niveau du référentiel APC sur lequel une formation rattache et évalue les traces (D5).
 */
enum GranulariteEnum: string
{
    case COMPETENCE = 'competence';
    case NIVEAU = 'niveau';
    case AC = 'ac';

    public function getLibelle(): string
    {
        return match ($this) {
            self::COMPETENCE => 'Compétence',
            self::NIVEAU => 'Niveau de compétence',
            self::AC => 'Apprentissage critique',
        };
    }
}
