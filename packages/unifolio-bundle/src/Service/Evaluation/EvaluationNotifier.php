<?php

namespace UnifolioBundle\Service\Evaluation;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Service\Email\EmailService;
use Psr\Log\LoggerInterface;
use UnifolioBundle\Entity\Campagne;
use UnifolioBundle\Entity\Evaluation;
use UnifolioBundle\Entity\Trace;

/**
 * Notifications par e-mail. Un échec d'envoi est journalisé mais ne bloque jamais l'évaluation.
 */
class EvaluationNotifier
{
    public function __construct(
        private readonly EmailService $emails,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function evaluationRendue(Evaluation $e): bool
    {
        if (!$e->isVisibleEtudiant()) {
            return false;
        }
        $cible = $e->getCible();

        return $this->envoyer('unifolio.evaluation_rendue', $e->getEtudiant(), $e->getCampagne(), [
            'etudiant_nom' => $e->getEtudiant()->getDisplay(),
            'evaluateur_nom' => $e->getEvaluateur()->getDisplay(),
            'cible' => trim(($cible instanceof \App\Entity\Apc\ApcApprentissageCritique ? $cible->getCode() . ' ' : '') . $cible->getLibelle()),
            'trace_titre' => $e->getRattachement()?->getTrace()->getTitre() ?? '',
            'score' => $e->getScore() !== null ? (string) round($e->getScore() * 100) : '',
            'appreciation' => $e->getAppreciation() ?? '',
            'urlPortal' => '/app/unifolio',
        ]);
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

        return $this->envoyer('unifolio.evaluation_rendue', $etudiant, $campagne, [
            'etudiant_nom' => $etudiant->getDisplay(),
            'evaluateur_nom' => $evaluateur->getDisplay(),
            'cible' => $trace !== null ? 'votre trace' : sprintf('votre bilan de l\'année (%d point(s))', \count($evaluations)),
            'trace_titre' => $trace?->getTitre() ?? '',
            'score' => $scores !== [] ? (string) round(array_sum($scores) / \count($scores) * 100) : '',
            'appreciation' => implode("\n", $appreciations),
            'urlPortal' => $trace !== null ? '/app/unifolio/traces/' . $trace->getUuidString() : '/app/unifolio/auto-evaluation',
        ]);
    }

    public function revisionDemandee(Evaluation $e): bool
    {
        $trace = $e->getRattachement()?->getTrace();
        if ($trace === null) {
            return false;
        }

        return $this->envoyer('unifolio.revision_demandee', $e->getEtudiant(), $e->getCampagne(), [
            'etudiant_nom' => $e->getEtudiant()->getDisplay(),
            'evaluateur_nom' => $e->getEvaluateur()->getDisplay(),
            'trace_titre' => $trace->getTitre(),
            'motif' => $e->getAppreciation() ?? '',
            'urlPortal' => '/app/unifolio/traces/' . $trace->getUuidString() . '/modifier',
        ]);
    }

    public function relanceEvaluateur(Personnel $evaluateur, Campagne $campagne, int $nbAEvaluer): bool
    {
        $to = $evaluateur->getMailUniv() ?? $evaluateur->getMailPerso();
        if ($to === null || $to === '') {
            $this->logger->warning('UniFolio : relance impossible, évaluateur sans adresse e-mail.', ['evaluateur' => $evaluateur->getId()]);

            return false;
        }

        return $this->envoyerA('unifolio.relance_evaluateur', $to, $campagne, [
            'evaluateur_nom' => $evaluateur->getDisplay(),
            'campagne_libelle' => $campagne->getLibelle(),
            'nb_a_evaluer' => $nbAEvaluer,
            'limite' => $campagne->getEvaluationLimite()?->format('d/m/Y'),
            'en_retard' => $campagne->isEvaluationEnRetard(),
            'urlPortal' => '/app/unifolio/evaluations',
        ]);
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
