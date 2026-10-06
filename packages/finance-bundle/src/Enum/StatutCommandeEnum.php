<?php

namespace FinanceBundle\Enum;

enum StatutCommandeEnum: string
{
    case BROUILLON = 'BROUILLON';
    case SOUMIS = 'SOUMIS';
    case AVIS_DIRECTION = 'AVIS_DIRECTION';
    case VERIFICATION_FINANCIERE = 'VERIFICATION_FINANCIERE';
    case VISA_ENGAGEMENT = 'VISA_ENGAGEMENT';
    case COMMANDE_VALIDEE = 'COMMANDE_VALIDEE';
    case COMMANDE_TRANSMISE = 'COMMANDE_TRANSMISE';
    case SERVICE_FAIT_CONSTATE = 'SERVICE_FAIT_CONSTATE';
    case SERVICE_FAIT_CERTIFIE = 'SERVICE_FAIT_CERTIFIE';
    case FACTURE_RECUE = 'FACTURE_RECUE';
    case PAYE = 'PAYE';
    case REJETE = 'REJETE';
    case CLOTURE = 'CLOTURE';

    public function getLabel(): string
    {
        return match ($this) {
            self::BROUILLON => 'Brouillon',
            self::SOUMIS => 'Demande soumise',
            self::AVIS_DIRECTION => 'Avis de la Direction',
            self::VERIFICATION_FINANCIERE => 'Vérification financière',
            self::VISA_ENGAGEMENT => 'Visa engagement',
            self::COMMANDE_VALIDEE => 'Commande validée',
            self::COMMANDE_TRANSMISE => 'BC transmis au fournisseur',
            self::SERVICE_FAIT_CONSTATE => 'Service fait constaté',
            self::SERVICE_FAIT_CERTIFIE => 'Service fait certifié',
            self::FACTURE_RECUE => 'Facture reçue',
            self::PAYE => 'Payé',
            self::REJETE => 'Rejeté',
            self::CLOTURE => 'Clôturé',
        };
    }

    public function getBadgeSeverity(): string
    {
        return match ($this) {
            self::BROUILLON => 'secondary',
            self::SOUMIS, self::AVIS_DIRECTION, self::VERIFICATION_FINANCIERE => 'info',
            self::VISA_ENGAGEMENT, self::COMMANDE_VALIDEE, self::COMMANDE_TRANSMISE => 'warn',
            self::SERVICE_FAIT_CONSTATE => 'help',
            self::SERVICE_FAIT_CERTIFIE, self::PAYE, self::CLOTURE => 'success',
            self::REJETE => 'danger',
            default => 'info',
        };
    }
}
