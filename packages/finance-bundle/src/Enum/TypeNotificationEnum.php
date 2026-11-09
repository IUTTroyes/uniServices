<?php

namespace FinanceBundle\Enum;

use App\Enum\ModeNotificationEnum;
use App\Notification\NotificationType;

/**
 * Événements du package Finances notifiés via le système commun (docs/NOTIFICATIONS_SYSTEM.md). Tous concernent des
 * personnels. Chacun choisit, type par type, « tout de suite », « dans le résumé » ou « jamais » (sauf restriction) ;
 * le service financier dispose en plus d'un récapitulatif des demandes en attente deux fois par jour.
 */
enum TypeNotificationEnum: string
{
    // Demandeur
    case AVIS_RENDU = 'avis_rendu';
    case DEMANDE_REJETEE = 'demande_rejetee';
    case COMMANDE_ENGAGEE = 'commande_engagee';
    case SERVICE_FAIT_A_CONSTATER = 'service_fait_a_constater';
    case COMMANDE_PAYEE = 'commande_payee';
    // Valideur
    case DEMANDE_A_VISER = 'demande_a_viser';
    // Service financier
    case DEMANDE_A_VERIFIER = 'demande_a_verifier';
    case SERVICE_FAIT_A_CERTIFIER = 'service_fait_a_certifier';
    // Récapitulatif périodique (finance:recapitulatif)
    case RECAPITULATIF = 'recapitulatif';

    /** Nom du package tel qu'il figure dans les packages activés (cf. UserPackageResolver). */
    public const SOURCE = 'finance';

    public function cle(): string
    {
        return self::SOURCE.'.'.$this->value;
    }

    public function getLibelle(): string
    {
        return match ($this) {
            self::AVIS_RENDU => 'Un avis est rendu sur ma demande d\'achat',
            self::DEMANDE_REJETEE => 'Ma demande d\'achat est rejetée',
            self::COMMANDE_ENGAGEE => 'Ma commande est engagée par le service financier',
            self::SERVICE_FAIT_A_CONSTATER => 'Ma commande est passée : je devrai constater la réception',
            self::COMMANDE_PAYEE => 'Ma commande est payée',
            self::DEMANDE_A_VISER => 'Une demande d\'achat attend mon avis',
            self::DEMANDE_A_VERIFIER => 'Une demande d\'achat est à vérifier (service financier)',
            self::SERVICE_FAIT_A_CERTIFIER => 'Un service fait est à certifier (service financier)',
            self::RECAPITULATIF => 'Récapitulatif des demandes d\'achat en attente (deux fois par jour)',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::AVIS_RENDU => 'Avis favorable, ou demande de précisions sur votre demande d\'achat.',
            self::DEMANDE_REJETEE => 'Avis défavorable, ou rejet par le service financier, avec le motif.',
            self::COMMANDE_ENGAGEE => 'Le visa d\'engagement est accordé : la commande va être passée.',
            self::SERVICE_FAIT_A_CONSTATER => 'Le bon de commande est transmis au fournisseur ; à la livraison, constatez le service fait.',
            self::COMMANDE_PAYEE => 'Le paiement de la facture est enregistré.',
            self::DEMANDE_A_VISER => 'Une demande d\'achat de votre département est soumise et attend votre avis.',
            self::DEMANDE_A_VERIFIER => 'Une demande d\'achat a reçu un avis favorable et attend la vérification financière.',
            self::SERVICE_FAIT_A_CERTIFIER => 'Une réception est constatée et attend la certification du service fait.',
            self::RECAPITULATIF => 'Chaque jour ouvré à 8 h et 13 h 30 : les demandes qui attendent une action de votre part, par statut.',
        };
    }

    public function getRubrique(): string
    {
        return match ($this) {
            self::AVIS_RENDU, self::DEMANDE_REJETEE, self::COMMANDE_ENGAGEE, self::SERVICE_FAIT_A_CONSTATER, self::COMMANDE_PAYEE => 'Mes demandes d\'achat',
            self::DEMANDE_A_VISER => 'Demandes à viser',
            self::DEMANDE_A_VERIFIER, self::SERVICE_FAIT_A_CERTIFIER => 'Demandes à traiter',
            self::RECAPITULATIF => 'Récapitulatif',
        };
    }

    public function getModeParDefaut(): ModeNotificationEnum
    {
        return match ($this) {
            // Information de fond, ou couverte par le récapitulatif du service financier.
            self::COMMANDE_ENGAGEE, self::COMMANDE_PAYEE, self::DEMANDE_A_VERIFIER, self::SERVICE_FAIT_A_CERTIFIER => ModeNotificationEnum::RESUME,
            default => ModeNotificationEnum::IMMEDIAT,
        };
    }

    /**
     * Un rejet ou un avis appelle une réaction du demandeur : il ne peut pas être ignoré (au pire, dans le résumé).
     * Le récapitulatif est périodique : « dans le résumé » n'aurait pas de sens.
     *
     * @return list<ModeNotificationEnum>
     */
    public function getModesAutorises(): array
    {
        return match ($this) {
            self::AVIS_RENDU, self::DEMANDE_REJETEE => [ModeNotificationEnum::IMMEDIAT, ModeNotificationEnum::RESUME],
            self::RECAPITULATIF => [ModeNotificationEnum::IMMEDIAT, ModeNotificationEnum::AUCUN],
            default => ModeNotificationEnum::cases(),
        };
    }

    public function definition(): NotificationType
    {
        return new NotificationType(
            cle: $this->cle(),
            source: self::SOURCE,
            sourceLibelle: 'Finances & Achats',
            libelle: $this->getLibelle(),
            description: $this->getDescription(),
            rubrique: $this->getRubrique(),
            audience: NotificationType::AUDIENCE_PERSONNEL,
            modeParDefaut: $this->getModeParDefaut(),
            modesAutorises: $this->getModesAutorises(),
        );
    }
}
