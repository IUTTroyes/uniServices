<?php

namespace StageBundle\Enum;

use App\Enum\ModeNotificationEnum;
use App\Notification\NotificationType;

/**
 * Événements du package Stages notifiés via le système commun (docs/NOTIFICATIONS_SYSTEM.md).
 * Ce qui appelle une action rapide part tout de suite par défaut ; l'information de fond va dans le résumé.
 */
enum TypeNotificationEnum: string
{
    // Étudiant
    case SAISIE_OUVERTE = 'saisie_ouverte';
    case CONVENTION_VALIDEE = 'convention_validee';
    case CONVENTION_REFUSEE = 'convention_refusee';
    case CONVENTION_A_SIGNER = 'convention_a_signer';
    case CONVENTION_SIGNEE = 'convention_signee';
    case AVENANT_VALIDE = 'avenant_valide';
    case SOUTENANCE_PLANIFIEE = 'soutenance_planifiee';
    // Personnel
    case DEMANDE_DEPOSEE = 'demande_deposee';
    case AVENANT_DEMANDE = 'avenant_demande';
    case TUTEUR_AFFECTE = 'tuteur_affecte';
    case JURY_SOUTENANCE = 'jury_soutenance';

    /** Nom du package tel qu'il figure dans les packages activés (cf. UserPackageResolver). */
    public const SOURCE = 'stage';

    public function cle(): string
    {
        return self::SOURCE.'.'.$this->value;
    }

    public function getLibelle(): string
    {
        return match ($this) {
            self::SAISIE_OUVERTE => 'Je peux saisir ma demande de convention',
            self::CONVENTION_VALIDEE => 'Ma demande de convention est validée',
            self::CONVENTION_REFUSEE => 'Ma demande de convention est refusée',
            self::CONVENTION_A_SIGNER => 'Ma convention est prête à être signée',
            self::CONVENTION_SIGNEE => 'Ma convention signée est enregistrée',
            self::AVENANT_VALIDE => 'Un avenant à ma convention est validé',
            self::SOUTENANCE_PLANIFIEE => 'Ma soutenance de stage est planifiée',
            self::DEMANDE_DEPOSEE => 'Un étudiant dépose ou modifie sa demande de convention',
            self::AVENANT_DEMANDE => 'Un étudiant demande un avenant',
            self::TUTEUR_AFFECTE => 'On me confie le suivi d\'un stagiaire',
            self::JURY_SOUTENANCE => 'Je suis convoqué à une soutenance de stage',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::SAISIE_OUVERTE => 'Le responsable des stages ouvre la saisie de votre demande pour une période de stage.',
            self::CONVENTION_VALIDEE => 'Le responsable des stages valide votre demande ; la convention va être éditée.',
            self::CONVENTION_REFUSEE => 'Le responsable des stages refuse votre demande ; vous pouvez la corriger.',
            self::CONVENTION_A_SIGNER => 'La convention est éditée et envoyée pour signature.',
            self::CONVENTION_SIGNEE => 'Le service des stages a reçu la convention signée par toutes les parties.',
            self::AVENANT_VALIDE => 'Le responsable des stages valide un avenant ; les modifications sont appliquées.',
            self::SOUTENANCE_PLANIFIEE => 'Un créneau de soutenance vous est attribué (ou modifié).',
            self::DEMANDE_DEPOSEE => 'Une demande de convention d\'une période dont vous êtes responsable attend votre validation.',
            self::AVENANT_DEMANDE => 'Une demande d\'avenant d\'une période dont vous êtes responsable attend votre validation.',
            self::TUTEUR_AFFECTE => 'Vous êtes désigné tuteur universitaire d\'un étudiant en stage.',
            self::JURY_SOUTENANCE => 'Vous êtes tuteur ou assesseur d\'une soutenance planifiée (ou modifiée).',
        };
    }

    public function getRubrique(): string
    {
        return match ($this) {
            self::SAISIE_OUVERTE, self::CONVENTION_VALIDEE, self::CONVENTION_REFUSEE,
            self::CONVENTION_A_SIGNER, self::CONVENTION_SIGNEE => 'Ma convention',
            self::AVENANT_VALIDE => 'Avenants',
            self::SOUTENANCE_PLANIFIEE, self::JURY_SOUTENANCE => 'Soutenances',
            self::DEMANDE_DEPOSEE => 'Demandes de convention à traiter',
            self::AVENANT_DEMANDE => 'Avenants à traiter',
            self::TUTEUR_AFFECTE => 'Suivi de stagiaires',
        };
    }

    public function getAudience(): string
    {
        return match ($this) {
            self::DEMANDE_DEPOSEE, self::AVENANT_DEMANDE, self::TUTEUR_AFFECTE, self::JURY_SOUTENANCE => NotificationType::AUDIENCE_PERSONNEL,
            default => NotificationType::AUDIENCE_ETUDIANT,
        };
    }

    public function getModeParDefaut(): ModeNotificationEnum
    {
        return match ($this) {
            self::CONVENTION_SIGNEE, self::DEMANDE_DEPOSEE, self::AVENANT_DEMANDE, self::TUTEUR_AFFECTE => ModeNotificationEnum::RESUME,
            default => ModeNotificationEnum::IMMEDIAT,
        };
    }

    /**
     * Un refus ou une convention à signer appelle une action de l'étudiant : ces décisions ne peuvent pas être ignorées
     * (au pire, elles arrivent dans le résumé). Le reste laisse le choix complet.
     *
     * @return list<ModeNotificationEnum>
     */
    public function getModesAutorises(): array
    {
        return match ($this) {
            self::CONVENTION_REFUSEE, self::CONVENTION_A_SIGNER => [ModeNotificationEnum::IMMEDIAT, ModeNotificationEnum::RESUME],
            default => ModeNotificationEnum::cases(),
        };
    }

    public function definition(): NotificationType
    {
        return new NotificationType(
            cle: $this->cle(),
            source: self::SOURCE,
            sourceLibelle: 'Stages',
            libelle: $this->getLibelle(),
            description: $this->getDescription(),
            rubrique: $this->getRubrique(),
            audience: $this->getAudience(),
            modeParDefaut: $this->getModeParDefaut(),
            modesAutorises: $this->getModesAutorises(),
        );
    }
}
