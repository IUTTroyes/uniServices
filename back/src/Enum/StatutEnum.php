<?php

namespace App\Enum;

enum StatutEnum : string implements BadgeEnumInterface
{
    case MCF = 'MCF';
    case ADM = 'ADM';
    case ASS = 'ASS';
    case TEC = 'TEC';
    case PU = 'PU';
    case ATER = 'ATER';
    case PRAG = 'PRAG';
    case IE = 'IE';
    case ENSAM = 'ENSAM';
    case DO = 'DO';
    case VAC = 'vacataire';
    case CDD = 'contractuel';
    case PRCE = 'PRCE';
    case BIATSS = 'BIATSS';
    case AUTRE = 'Autre';

    public function getBadge(): string
    {
        return match ($this) {
            self::MCF => 'info',
            self::ADM => 'info',
            self::ASS => 'info',
            self::TEC => 'info',
            self::PU => 'info',
            self::ATER => 'primary',
            self::PRAG => 'info',
            self::IE => 'success',
            self::CDD => 'primary',
            self::ENSAM => 'primary',
            self::DO => 'primary',
            self::VAC => 'success',
            self::PRCE => 'info',
            self::BIATSS => 'warn',
            self::AUTRE => 'secondary',
        };
    }

    public function getLibelle(): string
    {
        return match ($this) {
            self::MCF => 'Maître de conférences',
            self::ADM => 'Administratif',
            self::ASS => 'Assistant(e)',
            self::TEC => 'Techniciens',
            self::PU => 'Professeur des universités',
            self::ATER => 'Attaché temporaire d\'enseignement et de recherche',
            self::PRAG => 'Professeur agrégé',
            self::IE => 'Ingénieur d\'études',
            self::ENSAM => 'Enseignant associé',
            self::DO => 'Doctorant',
            self::CDD => 'Contractuel CDI/CDD',
            self::VAC => 'Enseignant Vacataire',
            self::PRCE => 'Professeur certifié',
            self::BIATSS => 'Personnel Biatss',
            self::AUTRE => 'Autre',
        };
    }
}
