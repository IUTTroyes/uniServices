<?php

namespace FinanceBundle\Enum;

/**
 * Actions du circuit d'une demande d'achat (POST /api/finance_bon_commandes/{id}/transitions/{action}).
 * Qui peut faire quoi et depuis quel statut : FinanceBundle\Workflow\FinanceWorkflow.
 */
enum ActionWorkflowEnum: string
{
    case SOUMETTRE = 'soumettre';
    case AVIS_FAVORABLE = 'avis_favorable';
    case AVIS_DEFAVORABLE = 'avis_defavorable';
    case DEMANDER_INFORMATION = 'demander_information';
    case VERIFIER = 'verifier';
    case VISER = 'viser';
    case TRANSMETTRE = 'transmettre';
    case CONSTATER_SERVICE_FAIT = 'constater_service_fait';
    case CERTIFIER_SERVICE_FAIT = 'certifier_service_fait';
    case RECEVOIR_FACTURE = 'recevoir_facture';
    case REJETER_FACTURE = 'rejeter_facture';
    case PAYER = 'payer';
    case CLOTURER = 'cloturer';
    case REJETER = 'rejeter';
    case REOUVRIR = 'reouvrir';

    public function getLibelle(): string
    {
        return match ($this) {
            self::SOUMETTRE => 'Soumettre la demande',
            self::AVIS_FAVORABLE => 'Avis favorable',
            self::AVIS_DEFAVORABLE => 'Avis défavorable',
            self::DEMANDER_INFORMATION => 'Demander des précisions',
            self::VERIFIER => 'Valider la vérification financière',
            self::VISER => 'Accorder le visa d\'engagement',
            self::TRANSMETTRE => 'Transmettre le bon de commande',
            self::CONSTATER_SERVICE_FAIT => 'Constater le service fait',
            self::CERTIFIER_SERVICE_FAIT => 'Certifier le service fait',
            self::RECEVOIR_FACTURE => 'Enregistrer la facture',
            self::REJETER_FACTURE => 'Rejeter la facture',
            self::PAYER => 'Enregistrer le paiement',
            self::CLOTURER => 'Clôturer la commande',
            self::REJETER => 'Rejeter la demande',
            self::REOUVRIR => 'Rouvrir la demande',
        };
    }

    /** Sévérité du bouton (PrimeVue). */
    public function getSeverite(): string
    {
        return match ($this) {
            self::AVIS_DEFAVORABLE, self::REJETER, self::REJETER_FACTURE => 'danger',
            self::DEMANDER_INFORMATION, self::REOUVRIR => 'secondary',
            default => 'primary',
        };
    }

    /** L'action demande un commentaire (motif affiché au demandeur). */
    public function exigeCommentaire(): bool
    {
        return \in_array($this, [self::AVIS_DEFAVORABLE, self::DEMANDER_INFORMATION, self::REJETER, self::REJETER_FACTURE], true);
    }

    /** Action irréversible ou lourde de conséquences : le front demande confirmation. */
    public function aConfirmer(): bool
    {
        return $this !== self::SOUMETTRE;
    }
}
