<?php

namespace App\Enum;

enum QuestTypeQuestionEnum: string
{
    case SingleChoice = 'single_choice';
    case MultipleChoice = 'multiple_choice';
    case TextShort = 'text_short';
    case TextLong = 'text_long';
    case Scale = 'scale';
    case Matrix = 'matrix';
    case Ranking = 'ranking';

    public function label(): string
    {
        return match ($this) {
            self::SingleChoice => 'Choix unique',
            self::MultipleChoice => 'Choix multiples',
            self::TextShort => 'Texte court',
            self::TextLong => 'Texte long',
            self::Scale => 'Échelle',
            self::Matrix => 'Grille',
            self::Ranking => 'Classement',
        };
    }
}
