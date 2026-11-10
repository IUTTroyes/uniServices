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

}
