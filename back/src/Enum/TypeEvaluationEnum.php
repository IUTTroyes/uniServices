<?php

namespace App\Enum;

enum TypeEvaluationEnum: string
{
    case TYPE_EVALUATION_TP = 'Travaux partiques';
    case TYPE_EVALUATION_EXAM = 'Exament';
    case TYPE_EVALUATION_PROJET = 'Projet';

    public static function getChoices(): array
    {
        return [
            'choice.' . self::TYPE_EVALUATION_TP->value => self::TYPE_EVALUATION_TP->value,
            'choice.' . self::TYPE_EVALUATION_EXAM->value => self::TYPE_EVALUATION_EXAM->value,
            'choice.' . self::TYPE_EVALUATION_PROJET->value => self::TYPE_EVALUATION_PROJET->value,
        ];
    }

    public static function getTypes(): array
    {
        return [
            self::TYPE_EVALUATION_TP->value,
            self::TYPE_EVALUATION_EXAM->value,
            self::TYPE_EVALUATION_PROJET->value,
        ];
    }
}
