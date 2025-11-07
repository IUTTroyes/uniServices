<?php

namespace App\Enum;

enum TypeEvaluationEnum: string
{
    case TYPE_EVALUATION_TP = 'Travaux pratiques';
    case TYPE_EVALUATION_EXAM = 'Examen';
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
            self::TYPE_EVALUATION_TP,
            self::TYPE_EVALUATION_EXAM,
            self::TYPE_EVALUATION_PROJET,
        ];
    }

    public static function getSeverity(): array
    {
        return [
            self::TYPE_EVALUATION_TP->value => 'success',
            self::TYPE_EVALUATION_EXAM->value => 'warn',
            self::TYPE_EVALUATION_PROJET->value => 'info',
        ];
    }
    public static function getIcon(): array
    {
        return [
            self::TYPE_EVALUATION_TP->value => '⚙️',
            self::TYPE_EVALUATION_EXAM->value => '📝',
            self::TYPE_EVALUATION_PROJET->value => '🧠',
        ];
    }
}
