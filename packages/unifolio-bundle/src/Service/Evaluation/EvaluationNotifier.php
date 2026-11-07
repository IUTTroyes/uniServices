<?php

namespace UnifolioBundle\Service\Evaluation;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Service\Email\EmailService;
use Psr\Log\LoggerInterface;
use UnifolioBundle\Entity\Campagne;
use UnifolioBundle\Entity\Evaluation;
use UnifolioBundle\Entity\Trace;
use UnifolioBundle\Enum\TypeNotificationEnum;
use UnifolioBundle\Service\Notification\NotificationManager;

/**
 * Notifications par e-mail. Un échec d'envoi est journalisé mais ne bloque jamais l'évaluation.
 */
class EvaluationNotifier
{
    public function __construct(
        private readonly EmailService $emails,
        private readonly LoggerInterface $logger,
        private readonly NotificationManager $notifications,
    ) {
    }

    public function evaluationRendue(Evaluation $e): bool
    {
        if (!$e->isVisibleEtudiant()) {
            return false;
        }
        $cible = $e->getCible();

        $trace = $e->getRattachement()?->getTrace();
        $contexte = [
            'etudiant_nom' => $e->getEtudiant()->getDisplay(),
            'evaluateur_nom' => $e->getEvaluateur()->getDisplay(),
            'cible' => trim(($cible instanceof \App\Entity\Apc\ApcApprentissageCritique ? $cible->getCode() . ' ' : '') . $cible->getLibelle()),
            'trace_titre' => $e->getRattachement()?->getTrace()->getTitre() ?? '',
            'score' => $e->getScore() !== null ? (string) round($e->getScore() * 100) : '',
            'appreciation' => $e->getAppreciation() ?? '',
            'urlPortal' => '/app/unifolio',
        ];
        $quoi = $trace !== null ? sprintf('votre trace « %s »', $trace->getTitre()) : 'votre bilan de l\'année';

        return $this->notifications->notifier(
            $e->getEtudiant(),
            TypeNotificationEnum::EVALUATION_RENDUE,
            sprintf('%s a évalué %s.', $e->getEvaluateur()->getDisplay(), $quoi),
            $trace !== null ? '/app/unifolio/traces/'.$trace->getUuidString() : '/app/unifolio/auto-evaluation',
            fn () => $this->envoyer('unifolio.evaluation_rendue', $e->getEtudiant(), $e->getCampagne(), $contexte),
        );
    }

    /**
     * Un seul e-mail pour toutes les évaluations rendues d'un coup (trace ou bilans).
     *
     * @param Evaluation[] $evaluations
     */
    public function lotRendu(Etudiant $etudiant, Campagne $campagne, Personnel $evaluateur, array $evaluations, ?Trace $trace): bool
    {
        $scores = array_filter(array_map(static fn (Evaluation $e) => $e->getScore(), $evaluations), static fn ($s) => $s !== null);
        $appreciations = [];
        foreach ($evaluations as $e) {
            if ($e->getAppreciation() !== null) {
                $cible = $e->getCible();
                $appreciations[] = ($cible instanceof \App\Entity\Apc\ApcApprentissageCritique && $cible->getCode() !== null ? $cible->getCode() : mb_strimwidth($cible->getLibelle(), 0, 40, '…')) . ' : ' . $e->getAppreciation();
            }
        }

        $contexte = [
            'etudiant_nom' => $etudiant->getDisplay(),
            'evaluateur_nom' => $evaluateur->getDisplay(),
            'cible' => $trace !== null ? 'votre trace' : sprintf('votre bilan de l\'année (%d point(s))', \count($evaluations)),
            'trace_titre' => $trace?->getTitre() ?? '',
            'score' => $scores !== [] ? (string) round(array_sum($scores) / \count($scores) * 100) : '',
            'appreciation' => implode("\n", $appreciations),
            'urlPortal' => $trace !== null ? '/app/unifolio/traces/' . $trace->getUuidString() : '/app/unifolio/auto-evaluation',
        ];

        return $this->notifications->notifier(
            $etudiant,
            TypeNotificationEnum::EVALUATION_RENDUE,
            $trace !== null
                ? sprintf('%s a évalué votre trace « %s ».', $evaluateur->getDisplay(), $trace->getTitre())
                : sprintf('%s a évalué votre bilan de l\'année (%d point(s)).', $evaluateur->getDisplay(), \count($evaluations)),
            $contexte['urlPortal'],
            fn () => $this->envoyer('unifolio.evaluation_rendue', $etudiant, $campagne, $contexte),
        );
    }

    public function revisionDemandee(Evaluation $e): bool
    {
        $trace = $e->getRattachement()?->getTrace();
        if ($trace === null) {
            return false;
        }

        $contexte = [
            'etudiant_nom' => $e->getEtudiant()->getDisplay(),
            'evaluateur_nom' => $e->getEvaluateur()->getDisplay(),
            'trace_titre' => $trace->getTitre(),
            'motif' => $e->getAppreciation() ?? '',
            'urlPortal' => '/app/unifolio/traces/' . $trace->getUuidString() . '/modifier',
        ];

        return $this->notifications->notifier(
            $e->getEtudiant(),
            TypeNotificationEnum::REVISION_DEMANDEE,
            sprintf('%s vous demande de réviser la trace « %s ».', $e->getEvaluateur()->getDisplay(), $trace->getTitre()),
            $contexte['urlPortal'],
            fn () => $this->envoyer('unifolio.revision_demandee', $e->getEtudiant(), $e->getCampagne(), $contexte),
        );
    }

    public function relanceEvaluateur(Personnel $evaluateur, Campagne $campagne, int $nbAEvaluer): bool
    {
        $to = $evaluateur->getMailUniv() ?? $evaluateur->getMailPerso();
        if ($to === null || $to === '') {
            $this->logger->warning('UniFolio : relance impossible, évaluateur sans adresse e-mail.', ['evaluateur' => $evaluateur->getId()]);

            return false;
        }

        $contexte = [
            'evaluateur_nom' => $evaluateur->getDisplay(),
            'campagne_libelle' => $campagne->getLibelle(),
            'nb_a_evaluer' => $nbAEvaluer,
            'limite' => $campagne->getEvaluationLimite()?->format('d/m/Y'),
            'en_retard' => $campagne->isEvaluationEnRetard(),
            'urlPortal' => '/app/unifolio/evaluations',
        ];

        // Un rappel qui part « dans un résumé » n'aurait pas de sens : seuls « tout de suite » et « jamais » sont possibles.
        return $this->notifications->notifier(
            $evaluateur,
            TypeNotificationEnum::RELANCE_EVALUATEUR,
            sprintf('%d évaluation(s) en attente — %s', $nbAEvaluer, $campagne->getLibelle()),
            $contexte['urlPortal'],
            fn () => $this->envoyerA('unifolio.relance_evaluateur', $to, $campagne, $contexte),
        );
    }

    /** @param array<string, mixed> $contexte */
    private function envoyer(string $cle, Etudiant $etudiant, Campagne $campagne, array $contexte): bool
    {
        $to = $etudiant->getMailUniv() ?? $etudiant->getMailPerso();
        if ($to === null || $to === '') {
            $this->logger->warning('UniFolio : notification impossible, étudiant sans adresse e-mail.', ['etudiant' => $etudiant->getId(), 'email' => $cle]);

            return false;
        }

        return $this->envoyerA($cle, $to, $campagne, $contexte);
    }

    /** @param array<string, mixed> $contexte */
    private function envoyerA(string $cle, string $to, Campagne $campagne, array $contexte): bool
    {
        try {
            $this->emails->send($cle, $to, $contexte, $campagne->getDiplome()->getDepartement());

            return true;
        } catch (\Throwable $e) {
            $this->logger->warning('UniFolio : échec de l\'envoi d\'un e-mail.', ['email' => $cle, 'erreur' => $e->getMessage()]);

            return false;
        }
    }
}
