<?php

namespace QuestionnaireBundle\Enum;

use App\Enum\ModeNotificationEnum;
use App\Notification\NotificationType;

/**
 * Types de notification du package (cloche + e-mail selon les préférences de chacun).
 * Invitations et relances existent pour chaque public (un type n'a qu'une audience) ; leurs e-mails dédiés héritent
 * des modèles « questionnaire.invitation » / « questionnaire.reminder », utilisés tels quels pour les adresses extérieures.
 */
enum TypeNotificationEnum: string
{
    case INVITATION_ETUDIANT = 'invitation_etudiant';
    case INVITATION_PERSONNEL = 'invitation_personnel';
    case RELANCE_ETUDIANT = 'relance_etudiant';
    case RELANCE_PERSONNEL = 'relance_personnel';
    case BILAN = 'completed';
    case RESULTATS = 'resultats';
    case RETOUR = 'retour';

    /** Nom du package tel qu'il figure dans les packages activés (cf. UserPackageResolver). */
    public const SOURCE = 'questionnaire';

    public function cle(): string
    {
        return self::SOURCE.'.'.$this->value;
    }

    public function definition(): NotificationType
    {
        return new NotificationType(
            cle: $this->cle(),
            source: self::SOURCE,
            sourceLibelle: 'Questionnaires',
            libelle: $this->libelle(),
            description: $this->description(),
            rubrique: $this->rubrique(),
            audience: $this->audience(),
            modeParDefaut: $this->modeParDefaut(),
            modesAutorises: $this->modesAutorises(),
        );
    }

    public function audience(): string
    {
        return match ($this) {
            self::INVITATION_ETUDIANT, self::RELANCE_ETUDIANT => NotificationType::AUDIENCE_ETUDIANT,
            default => NotificationType::AUDIENCE_PERSONNEL,
        };
    }

    public function libelle(): string
    {
        return match ($this) {
            self::INVITATION_ETUDIANT, self::INVITATION_PERSONNEL => 'Je suis invité(e) à répondre à un questionnaire',
            self::RELANCE_ETUDIANT, self::RELANCE_PERSONNEL => 'Rappel : un questionnaire attend ma réponse',
            self::BILAN => 'Un questionnaire dont je suis responsable est clôturé',
            self::RESULTATS => 'Les résultats d\'un questionnaire qui me concerne sont publiés',
            self::RETOUR => 'Un enseignant commente ses résultats',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::INVITATION_ETUDIANT, self::INVITATION_PERSONNEL => 'Un questionnaire vous est adressé, avec votre lien personnel pour répondre.',
            self::RELANCE_ETUDIANT, self::RELANCE_PERSONNEL => 'Rappel de l\'équipe (ou automatique) tant que vous n\'avez pas envoyé vos réponses.',
            self::BILAN => 'Bilan de participation à la clôture d\'un questionnaire dont vous êtes l\'auteur ou le référent qualité.',
            self::RESULTATS => 'Après relecture, les résultats des enseignements que vous assurez (ou qui vous sont confiés) sont disponibles.',
            self::RETOUR => 'Un enseignant ajoute un commentaire ou un plan d\'action sur les résultats d\'un questionnaire dont vous êtes responsable.',
        };
    }

    public function rubrique(): string
    {
        return match ($this) {
            self::INVITATION_ETUDIANT, self::INVITATION_PERSONNEL, self::RELANCE_ETUDIANT, self::RELANCE_PERSONNEL => 'Questionnaires à remplir',
            self::BILAN, self::RESULTATS, self::RETOUR => 'Résultats',
        };
    }

    public function modeParDefaut(): ModeNotificationEnum
    {
        return $this === self::RETOUR ? ModeNotificationEnum::RESUME : ModeNotificationEnum::IMMEDIAT;
    }

    /** @return list<ModeNotificationEnum>|null */
    public function modesAutorises(): ?array
    {
        return match ($this) {
            // L'invitation porte le lien personnel : elle ne peut pas être ignorée (au pire, dans le résumé).
            self::INVITATION_ETUDIANT, self::INVITATION_PERSONNEL => [ModeNotificationEnum::IMMEDIAT, ModeNotificationEnum::RESUME],
            // Un rappel n'a pas de sens dans un résumé.
            self::RELANCE_ETUDIANT, self::RELANCE_PERSONNEL => [ModeNotificationEnum::IMMEDIAT, ModeNotificationEnum::AUCUN],
            default => null,
        };
    }

    /** Type d'invitation ou de relance selon le public du destinataire. */
    public static function invitationPour(bool $etudiant): self
    {
        return $etudiant ? self::INVITATION_ETUDIANT : self::INVITATION_PERSONNEL;
    }

    public static function relancePour(bool $etudiant): self
    {
        return $etudiant ? self::RELANCE_ETUDIANT : self::RELANCE_PERSONNEL;
    }
}
