<?php

namespace UnifolioBundle\Enum;

enum TypeCritereEnum: string
{
    case ECHELLE = 'echelle';
    case BOOLEEN = 'booleen';
    case NOTE = 'note';
    case TEXTE = 'texte';

    public function getLibelle(): string
    {
        return match ($this) {
            self::ECHELLE => 'Échelle de niveaux',
            self::BOOLEEN => 'Oui / Non',
            self::NOTE => 'Note numérique',
            self::TEXTE => 'Appréciation libre',
        };
    }

    /** Le critère contribue au score pondéré. */
    public function isQuantifiable(): bool
    {
        return $this !== self::TEXTE;
    }
}
