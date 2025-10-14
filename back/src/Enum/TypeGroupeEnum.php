<?php

namespace App\Enum;

enum TypeGroupeEnum: string
{
    case TYPE_GROUPE_TD = 'TD';
    case TYPE_GROUPE_TP = 'TP';
    case TYPE_GROUPE_CM = 'CM';
    case TYPE_GROUPE_SPECIAL = 'Spécial';
    case TYPE_GROUPE_AUTRE = 'Autre';

    public static function getChoices(): array
    {
        return [
            'choice.' . self::TYPE_GROUPE_TD->value => self::TYPE_GROUPE_TD,
            'choice.' . self::TYPE_GROUPE_TP->value => self::TYPE_GROUPE_TP,
            'choice.' . self::TYPE_GROUPE_CM->value => self::TYPE_GROUPE_CM,
            'choice.' . self::TYPE_GROUPE_SPECIAL->value => self::TYPE_GROUPE_SPECIAL,
            'choice.' . self::TYPE_GROUPE_AUTRE->value => self::TYPE_GROUPE_AUTRE,
        ];
    }

    public static function getTypes(): array
    {
        return [
            self::TYPE_GROUPE_TD,
            self::TYPE_GROUPE_TP,
            self::TYPE_GROUPE_CM,
            self::TYPE_GROUPE_SPECIAL,
            self::TYPE_GROUPE_AUTRE,
        ];
    }
}
