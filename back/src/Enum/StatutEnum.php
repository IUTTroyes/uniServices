<?php

namespace App\Enum;

enum StatutEnum : string implements BadgeEnumInterface
{
    case MCF = 'MCF';
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
    case PRO = 'PRO';
    case TEC = 'TEC';
    case ADM = 'ADM';
    case ASS = 'ASS';
    case PR = 'PR';
    case PEPS = 'PEPS';
    case Profession = 'Profession';
    case PRCACDD = 'PRCACDD';
    case CONTRAC = 'CONTRAC';
    case AUTRE = 'Autre';

    public static function getStatuts(): array
    {
        return [
            self::MCF->getLibelle(),
            self::PU->getLibelle(),
            self::ATER->getLibelle(),
            self::PRAG->getLibelle(),
            self::IE->getLibelle(),
            self::ENSAM->getLibelle(),
            self::DO->getLibelle(),
            self::VAC->getLibelle(),
            self::PRCE->getLibelle(),
            self::BIATSS->getLibelle(),
            self::PRO->getLibelle(),
            self::TEC->getLibelle(),
            self::ADM->getLibelle(),
            self::ASS->getLibelle(),
            self::CDD->getLibelle(),
            self::PR->getLibelle(),
            self::Profession->getLibelle(),
            self::PEPS->getLibelle(),
            self::PRCACDD->getLibelle(),
            self::CONTRAC->getLibelle(),
            self::AUTRE->getLibelle(),
        ];
    }

    public function getBadge(): string
    {
        return match ($this) {
            self::MCF => 'success',
            self::PU => 'success',
            self::ATER => 'primary',
            self::PRAG => 'success',
            self::IE => 'info',
            self::ENSAM => 'primary',
            self::DO => 'primary',
            self::VAC => 'info',
            self::PRCE => 'success',
            self::BIATSS => 'warn',
            self::PRO => 'primary',
            self::TEC => 'warn',
            self::ADM => 'secondary',
            self::ASS => 'secondary',
            self::CDD => 'success',
            self::PR => 'success',
            self::Profession => 'primary',
            self::PEPS => 'secondary',
            self::PRCACDD => 'secondary',
            self::CONTRAC => 'success',
            self::AUTRE => 'secondary',
        };
    }

    public function getLibelle(): string
    {
        return match ($this) {
            self::MCF => 'Maître de conférences',
            self::PU => 'Professeur des universités',
            self::ATER => 'Attaché temporaire d\'enseignement et de recherche',
            self::PRAG => 'Professeur agrégé',
            self::IE => 'Ingénieur d\'études',
            self::ENSAM => 'Enseignant associé',
            self::DO => 'Doctorant',
            self::VAC => 'Enseignant Vacataire',
            self::PRCE => 'Professeur certifié',
            self::BIATSS => 'Personnel Biatss',
            self::PRO => 'Intervenant Professionnel',
            self::TEC => 'Technicien',
            self::ADM => 'Administratif',
            self::CDD => 'Contractuel',
            self::ASS => 'Assistant',
            self::PR => 'Pro',
            self::Profession => 'Pro',
            self::PEPS => 'Peps',
            self::PRCACDD => 'PrcaCdd',
            self::CONTRAC => 'Contractuel',
            self::AUTRE => 'Autre',
        };
    }
}
