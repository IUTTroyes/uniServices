<?php

namespace UnifolioBundle\Enum;

/** Combinaison des évaluations quand plusieurs traces couvrent le même élément du référentiel. */
enum AgregationEnum: string
{
    case MAX = 'max';
    case MOYENNE = 'moyenne';
    case DERNIERE = 'derniere';

    public function getLibelle(): string
    {
        return match ($this) {
            self::MAX => 'Meilleure évaluation',
            self::MOYENNE => 'Moyenne des évaluations',
            self::DERNIERE => 'Évaluation la plus récente',
        };
    }
}
