<?php

namespace QuestionnaireBundle\Services;

use App\Service\Email\EmailService;
use Psr\Log\LoggerInterface;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireInvitation;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Envois des e-mails du package (invitation, relance, bilan de clôture).
 *
 * Contexte en valeurs simples (le modèle personnalisé est rendu dans le sandbox, sans appel de méthode) ; liens
 * absolus vers le front (URL_FRONTEND), car ils sont aussi affichés en clair et repris dans la version texte.
 * Un envoi qui échoue est journalisé et ne fait jamais échouer l'action métier : la méthode renvoie false.
 */
class QuestionnaireMailer
{
    /** Route publique de réponse (sans compte : les invitations peuvent viser des adresses extérieures). */
    public const CHEMIN_REPONSE = '/app/questionnaire-reponse/';
    public const CHEMIN_RESULTATS = '/app/questionnaire/qualite/enquetes/analytics/';
    public const CHEMIN_MES_RESULTATS = '/app/questionnaire/qualite/enquetes/resultats/';

    public function __construct(
        private readonly EmailService $emails,
        private readonly LoggerInterface $logger,
        #[Autowire('%env(default::URL_FRONTEND)%')]
        private readonly ?string $urlFrontend = null,
    ) {
    }

    public function lienReponse(QuestionnaireInvitation $invitation): string
    {
        return $this->absolu(self::CHEMIN_REPONSE.$invitation->getToken());
    }

    public function lienResultats(Questionnaire $questionnaire): string
    {
        return $this->absolu(self::CHEMIN_RESULTATS.$questionnaire->getUuidString());
    }

    /** @param string $cle e-mail à utiliser : « questionnaire.invitation » (adresse extérieure) ou celui du type de notification */
    public function invitation(QuestionnaireInvitation $invitation, string $cle = 'questionnaire.invitation'): bool
    {
        return $this->envoyer($cle, $invitation->getEmail(), $invitation->getQuestionnaire(), $this->contexteRepondant($invitation));
    }

    /** Relance : objet et message facultatifs saisis par l'équipe (sinon ceux du modèle). */
    public function relance(QuestionnaireInvitation $invitation, ?string $objet = null, ?string $message = null, string $cle = 'questionnaire.reminder'): bool
    {
        return $this->envoyer($cle, $invitation->getEmail(), $invitation->getQuestionnaire(), [
            ...$this->contexteRepondant($invitation),
            'objet' => $objet ?? '',
            'message' => $message ?? '',
        ]);
    }

    /**
     * Bilan de clôture envoyé à l'auteur.
     *
     * @param array{total: int, submitted: int, rate: float} $stats
     */
    public function bilan(Questionnaire $questionnaire, string $destinataire, ?string $nom, array $stats, bool $complet, bool $dateAtteinte): bool
    {
        return $this->envoyer('questionnaire.completed', $destinataire, $questionnaire, [
            'destinataire_nom' => $nom ?? '',
            'questionnaire_titre' => (string) $questionnaire->getTitle(),
            'questionnaire_description' => (string) $questionnaire->getDescription(),
            'date_cloture' => $questionnaire->getClosingDate()?->format('d/m/Y à H:i') ?? '',
            'reponses' => $stats['submitted'],
            'invites' => $stats['total'],
            'taux' => $stats['rate'],
            'complet' => $complet,
            'date_atteinte' => $dateAtteinte,
            'lien' => $this->lienResultats($questionnaire),
        ]);
    }

    /**
     * Résultats publiés pour un destinataire (lien vers sa restitution web filtrée).
     *
     * @param list<string> $sections titres des sections qui le concernent
     */
    public function resultats(Questionnaire $questionnaire, string $destinataire, ?string $nom, array $sections): bool
    {
        return $this->envoyer('questionnaire.resultats', $destinataire, $questionnaire, [
            'destinataire_nom' => $nom ?? '',
            'questionnaire_titre' => (string) $questionnaire->getTitle(),
            'sections' => implode(', ', $sections),
            'nb_sections' => \count($sections),
            'anonyme' => $questionnaire->estAnonyme(),
            'lien' => $this->absolu(self::CHEMIN_MES_RESULTATS.$questionnaire->getUuidString()),
        ]);
    }

    /** @return array<string, mixed> */
    private function contexteRepondant(QuestionnaireInvitation $invitation): array
    {
        $questionnaire = $invitation->getQuestionnaire();

        return [
            'questionnaire_titre' => (string) $questionnaire?->getTitle(),
            'questionnaire_description' => (string) $questionnaire?->getDescription(),
            'date_cloture' => $questionnaire?->getClosingDate()?->format('d/m/Y') ?? '',
            'duree_estimee' => $questionnaire?->getEstimatedTime() ? (int) ceil($questionnaire->getEstimatedTime() / 60) : 0,
            'anonyme' => (bool) $questionnaire?->estAnonyme(),
            'lien' => $this->lienReponse($invitation),
        ];
    }

    /** @param array<string, mixed> $contexte */
    private function envoyer(string $cle, ?string $destinataire, ?Questionnaire $questionnaire, array $contexte): bool
    {
        if ($destinataire === null || trim($destinataire) === '') {
            return false;
        }
        try {
            $this->emails->send(emailKey: $cle, to: trim($destinataire), context: $contexte, departement: $questionnaire?->getDepartement());

            return true;
        } catch (\Throwable $e) {
            $this->logger->warning('E-mail de questionnaire non envoyé.', ['email' => $cle, 'destinataire' => $destinataire, 'erreur' => $e->getMessage()]);

            return false;
        }
    }

    private function absolu(string $chemin): string
    {
        return rtrim((string) $this->urlFrontend, '/').$chemin;
    }
}
