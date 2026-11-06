<?php

namespace UnifolioBundle\Enum;

enum SourceCritereEnum: string
{
    /** Critère évalué librement par l'enseignant. */
    case SAISIE = 'saisie';
    /** Critère « Usage de l'IA » (D6) : l'évaluateur voit la déclaration de l'étudiant. */
    case DECLARATION_IA = 'declaration_ia';

    public function getLibelle(): string
    {
        return match ($this) {
            self::SAISIE => 'Critère libre',
            self::DECLARATION_IA => 'Usage de l\'IA',
        };
    }
}
