<?php

namespace App\Enum;

enum TypeSalleEnum: string
{
    case TYPE_SALLE_INFO = 'INFO';
    case TYPE_SALLE_AMPHI = 'AMPHI';
    case TYPE_SALLE_EVAL = 'EVAL';
    case TYPE_SALLE_TD = 'TD';
    case TYPE_SALLE_TP = 'TP';

    public static function getChoices(): array
    {
        return [
            'choice.' . self::TYPE_SALLE_INFO->value => self::TYPE_SALLE_INFO,
            'choice.' . self::TYPE_SALLE_AMPHI->value => self::TYPE_SALLE_AMPHI,
            'choice.' . self::TYPE_SALLE_EVAL->value => self::TYPE_SALLE_EVAL,
            'choice.' . self::TYPE_SALLE_TD->value => self::TYPE_SALLE_TD,
            'choice.' . self::TYPE_SALLE_TP->value => self::TYPE_SALLE_TP,
        ];
    }

    public static function getTypes(): array
    {
        return [
            self::TYPE_SALLE_INFO,
            self::TYPE_SALLE_AMPHI,
            self::TYPE_SALLE_EVAL,
            self::TYPE_SALLE_TD,
            self::TYPE_SALLE_TP,
        ];
    }
}
