<?php

namespace UnifolioBundle\Enum;

/** Rythme d'envoi du résumé groupé des notifications. */
enum FrequenceResumeEnum: string
{
    case QUOTIDIEN = 'quotidien';
    case HEBDOMADAIRE = 'hebdomadaire';

    public function getLibelle(): string
    {
        return match ($this) {
            self::QUOTIDIEN => 'Chaque soir',
            self::HEBDOMADAIRE => 'Chaque lundi matin',
        };
    }

    public function getPeriode(): string
    {
        return match ($this) {
            self::QUOTIDIEN => 'de la journée',
            self::HEBDOMADAIRE => 'de la semaine',
        };
    }
}
