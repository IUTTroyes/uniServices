<?php

namespace QuestionnaireBundle\Services\Notification;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Notification\NotificationService;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireInvitation;
use QuestionnaireBundle\Enum\TypeNotificationEnum as Type;
use QuestionnaireBundle\Services\QuestionnaireMailer;

/**
 * Traduit les événements des questionnaires en notifications (cloche + e-mail selon les préférences de chacun).
 * Seul point d'envoi du package ; à appeler APRÈS le flush de l'action.
 *
 *  - destinataire ayant un compte : NotificationService::notifier(), avec l'e-mail dédié du type pour le mode
 *    « tout de suite » ;
 *  - adresse extérieure (aucun compte) : e-mail direct (modèles « questionnaire.invitation » / « questionnaire.reminder »).
 *
 * Personne n'est notifié de sa propre action : invitations, relances et bilans sont adressés à d'autres que leur
 * auteur (une personne qui s'invite elle-même reçoit tout de même son lien personnel).
 */
class QuestionnaireNotifier
{
    public const URL_REPONSE = '/app/questionnaire/qualite/enquetes/take/';
    public const URL_RESULTATS = '/app/questionnaire/qualite/enquetes/analytics/';
    /** Restitution web filtrée pour un destinataire des résultats. */
    public const URL_MES_RESULTATS = '/app/questionnaire/qualite/enquetes/resultats/';

    public function __construct(
        private readonly NotificationService $notifications,
        private readonly QuestionnaireMailer $mailer,
        private readonly DestinataireResolver $destinataires,
    ) {
    }

    /** @return bool l'invitation a été transmise (notification enregistrée ou e-mail parti) */
    public function invitation(QuestionnaireInvitation $invitation): bool
    {
        $compte = $this->destinataires->pourAdresse($invitation->getEmail());
        if ($compte === null) {
            return $this->mailer->invitation($invitation);
        }
        $type = Type::invitationPour($compte instanceof Etudiant);
        $q = $invitation->getQuestionnaire();

        return null !== $this->notifications->notifier(
            $compte,
            $type->cle(),
            sprintf('Vous êtes invité(e) à répondre au questionnaire « %s »%s.', $q?->getTitle(), self::echeance($q)),
            self::URL_REPONSE.$invitation->getToken(),
            fn (): bool => $this->mailer->invitation($invitation, $type->cle()),
        );
    }

    /** @return bool la relance a été transmise (notification enregistrée ou e-mail parti) */
    public function relance(QuestionnaireInvitation $invitation, ?string $objet = null, ?string $message = null): bool
    {
        $compte = $this->destinataires->pourAdresse($invitation->getEmail());
        if ($compte === null) {
            return $this->mailer->relance($invitation, $objet, $message);
        }
        $type = Type::relancePour($compte instanceof Etudiant);
        $q = $invitation->getQuestionnaire();

        // Même lien que l'invitation : les relances successives fusionnent dans le résumé.
        return null !== $this->notifications->notifier(
            $compte,
            $type->cle(),
            sprintf('Rappel : le questionnaire « %s » attend votre réponse%s.', $q?->getTitle(), self::echeance($q)),
            self::URL_REPONSE.$invitation->getToken(),
            fn (): bool => $this->mailer->relance($invitation, $objet, $message, $type->cle()),
        );
    }

    /**
     * Bilan de clôture à l'auteur ou au référent qualité.
     *
     * @param array{total: int, submitted: int, rate: float} $stats
     */
    public function bilan(Questionnaire $q, Personnel $destinataire, array $stats, bool $complet, bool $dateAtteinte): bool
    {
        $adresse = $destinataire->getMailUniv() ?: $destinataire->getMailPerso();

        return null !== $this->notifications->notifier(
            $destinataire,
            Type::BILAN->cle(),
            sprintf('Le questionnaire « %s » est clôturé : %d réponse(s) sur %d (%s %%).', $q->getTitle(), $stats['submitted'], $stats['total'], $stats['rate']),
            self::URL_RESULTATS.$q->getUuidString(),
            $adresse ? fn (): bool => $this->mailer->bilan($q, $adresse, $destinataire->getDisplay(), $stats, $complet, $dateAtteinte) : null,
        );
    }

    /**
     * Résultats publiés : une notification par destinataire, pour toutes ses sections.
     *
     * @param list<string> $sections titres des sections qui le concernent
     */
    public function resultats(Questionnaire $q, Personnel $destinataire, array $sections): bool
    {
        $adresse = $destinataire->getMailUniv() ?: $destinataire->getMailPerso();

        return null !== $this->notifications->notifier(
            $destinataire,
            Type::RESULTATS->cle(),
            sprintf('Les résultats du questionnaire « %s » qui vous concernent sont disponibles (%d section(s)).', $q->getTitle(), \count($sections)),
            self::URL_MES_RESULTATS.$q->getUuidString(),
            $adresse ? fn (): bool => $this->mailer->resultats($q, $adresse, $destinataire->getDisplay(), $sections) : null,
        );
    }

    /** Retour d'un enseignant (commentaire, plan d'action) pour l'équipe : jamais à son propre auteur. */
    public function retour(Questionnaire $q, Personnel $destinataire, Personnel $enseignant, string $section): void
    {
        if ($destinataire->getId() === $enseignant->getId()) {
            return;
        }
        $this->notifications->notifier(
            $destinataire,
            Type::RETOUR->cle(),
            sprintf('%s a commenté ses résultats « %s » (questionnaire « %s »).', $enseignant->getDisplay(), $section, $q->getTitle()),
            self::URL_RESULTATS.$q->getUuidString(),
        );
    }

    private static function echeance(?Questionnaire $q): string
    {
        return $q?->getClosingDate() !== null ? ' avant le '.$q->getClosingDate()->format('d/m/Y') : '';
    }
}
