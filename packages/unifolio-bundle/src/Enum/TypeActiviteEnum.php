<?php

namespace UnifolioBundle\Enum;

enum TypeActiviteEnum: string
{
    case SAE = 'sae';
    case RESSOURCE = 'ressource';
    case STAGE = 'stage';
    case ALTERNANCE = 'alternance';
    case PROJET_PERSONNEL = 'projet_personnel';
    case ASSOCIATIF = 'associatif';
    case AUTRE = 'autre';

    public function getLibelle(): string
    {
        return match ($this) {
            self::SAE => 'SAÉ',
            self::RESSOURCE => 'Ressource',
            self::STAGE => 'Stage',
            self::ALTERNANCE => 'Alternance',
            self::PROJET_PERSONNEL => 'Projet personnel',
            self::ASSOCIATIF => 'Engagement associatif',
            self::AUTRE => 'Autre',
        };
    }
}
