<?php

namespace UnifolioBundle\Service\Evaluation;

use App\Entity\Apc\ApcApprentissageCritique;
use App\Entity\Apc\ApcCompetence;
use App\Entity\Apc\ApcNiveau;
use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use UnifolioBundle\Entity\Campagne;
use UnifolioBundle\Entity\Evaluation;
use UnifolioBundle\Entity\Trace;
use UnifolioBundle\Entity\TraceRattachement;
use UnifolioBundle\Enum\GranulariteEnum;
use UnifolioBundle\Enum\StatutEvaluationEnum;
use UnifolioBundle\Enum\StatutTraceEnum;
use UnifolioBundle\Repository\AutoEvaluationRepository;
use UnifolioBundle\Repository\EvaluationRepository;
use UnifolioBundle\Service\Campagne\CampagneResolver;
use UnifolioBundle\Service\Campagne\EvaluateurAcces;
use UnifolioBundle\Service\Referentiel\ReferentielAnnee;
use UnifolioBundle\Service\Trace\UnifolioValidationException;

/**
 * Cycle d'une évaluation enseignante : ouverture, saisie, remise à l'étudiant, demande de révision,
 * réouverture. Garde le statut des traces cohérent avec l'avancement des évaluations.
 */
class EvaluationManager
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EvaluationRepository $evaluations,
        private readonly AutoEvaluationRepository $autoEvaluations,
        private readonly CampagneResolver $campagnes,
        private readonly EvaluateurAcces $acces,
        private readonly AutoEvaluationManager $autoEvaluationManager,
        private readonly ReferentielAnnee $referentiel,
        private readonly EvaluationNotifier $notifier,
    ) {
    }

    // ---------- Ouverture ----------

    public function ouvrirPourRattachement(Personnel $evaluateur, TraceRattachement $rattachement, bool $exigerAutoEvaluation = true): Evaluation
    {
        $trace = $rattachement->getTrace();
        $scolarite = $trace->getScolarite()
            ?? throw UnifolioValidationException::un('Cette trace n\'est rattachée à aucune année de BUT.');
        $campagne = $this->campagneOuverte($scolarite);
        $this->verifierAffectation($evaluateur, $trace->getEtudiant());

        if (!\in_array($trace->getStatut(), [StatutTraceEnum::DEPOSEE, StatutTraceEnum::EN_EVALUATION, StatutTraceEnum::EVALUEE], true)) {
            throw UnifolioValidationException::un(sprintf('Cette trace n\'est pas en attente d\'évaluation (statut « %s »).', $trace->getStatut()->getLibelle()));
        }
        if ($trace->getStatut() === StatutTraceEnum::DEPOSEE) {
            $trace->changerStatut(StatutTraceEnum::EN_EVALUATION);
        }

        if (($existante = $this->evaluations->findOneParEvaluateurEtRattachement($evaluateur, $rattachement)) !== null) {
            $this->em->flush();

            return $existante;
        }

        if ($exigerAutoEvaluation) {
            $this->verifierAutoEvaluation($campagne, $this->autoEvaluations->findOneByRattachement($rattachement));
        }
        $evaluation = $this->garder(fn () => Evaluation::pourRattachement($campagne, $evaluateur, $rattachement, $scolarite, $this->autoEvaluationManager->versionPour($scolarite)));
        $this->em->persist($evaluation);
        $this->em->flush();

        return $evaluation;
    }

    public function ouvrirBilan(Personnel $evaluateur, EtudiantScolarite $scolarite, string $type, int $id): Evaluation
    {
        $etudiant = $scolarite->getEtudiant() ?? throw UnifolioValidationException::un('Scolarité sans étudiant.');
        $campagne = $this->campagneOuverte($scolarite);
        $this->verifierAffectation($evaluateur, $etudiant);

        $granularite = $this->autoEvaluationManager->granularitePour($scolarite);
        if (GranulariteEnum::tryFrom($type) !== $granularite) {
            throw UnifolioValidationException::un(sprintf('Cette formation évalue par « %s ».', $granularite->getLibelle()));
        }
        if (($existante = $this->evaluations->findBilan($evaluateur, $scolarite, $granularite, $id)) !== null) {
            return $existante;
        }

        $cible = match ($granularite) {
            GranulariteEnum::COMPETENCE => $this->em->find(ApcCompetence::class, $id),
            GranulariteEnum::NIVEAU => $this->em->find(ApcNiveau::class, $id),
            GranulariteEnum::AC => $this->em->find(ApcApprentissageCritique::class, $id),
        };
        if ($cible === null || !$this->referentiel->contient($scolarite, $cible)) {
            throw UnifolioValidationException::un('Cet élément ne fait pas partie du référentiel de l\'année de l\'étudiant.');
        }

        $this->verifierAutoEvaluation($campagne, $this->autoEvaluations->findBilan($scolarite, $granularite, $id));
        $evaluation = $this->garder(fn () => Evaluation::bilan($campagne, $evaluateur, $etudiant, $scolarite, $this->autoEvaluationManager->versionPour($scolarite), $cible));
        $this->em->persist($evaluation);
        $this->em->flush();

        return $evaluation;
    }

    // ---------- Saisie et remise ----------

    /**
     * @param array{appreciation?: ?string, reponses?: mixed} $d reponses : [{ critere, echelon?, valeur?, booleen?, texte? }]
     */
    public function enregistrer(Evaluation $e, array $d): Evaluation
    {
        $this->verifierCampagneOuverte($e);
        $donnees = [];
        if (\array_key_exists('appreciation', $d)) {
            $donnees['appreciation'] = $d['appreciation'];
        }
        if (\array_key_exists('reponses', $d)) {
            $saisies = [];
            foreach (\is_array($d['reponses']) ? $d['reponses'] : [] as $r) {
                if (\is_array($r) && isset($r['critere'])) {
                    $saisies[(string) $r['critere']] = $r;
                }
            }
            $donnees['reponses'] = $saisies;
        }
        $this->garder(fn () => $e->renseigner($donnees));
        $this->em->flush();

        return $e;
    }

    /** Remet l'évaluation (visible de l'étudiant par défaut) ; la trace passe « évaluée » quand tout est rendu. */
    public function rendre(Evaluation $e, bool $visibleEtudiant = true, bool $notifier = true): void
    {
        $this->verifierCampagneOuverte($e);
        $erreurs = $e->getErreursRendu();
        if ($erreurs !== []) {
            throw new UnifolioValidationException($erreurs);
        }
        $this->garder(fn () => $e->rendre($visibleEtudiant));
        if (!$e->isBilan()) {
            $this->synchroniserTrace($e->getRattachement()->getTrace());
        }
        $this->em->flush();
        if ($notifier) {
            $this->notifier->evaluationRendue($e);
        }
    }

    /** Demande à l'étudiant de réviser la trace : elle repasse « à réviser » et il est prévenu. */
    public function demanderRevision(Evaluation $e, string $motif, bool $notifier = true): void
    {
        $this->verifierCampagneOuverte($e);
        $this->garder(fn () => $e->demanderRevision($motif));
        $this->synchroniserTrace($e->getRattachement()->getTrace());
        $this->em->flush();
        if ($notifier) {
            $this->notifier->revisionDemandee($e);
        }
    }

    /** Rouvre une évaluation rendue (ou une révision demandée, une fois la trace redéposée). */
    public function rouvrir(Evaluation $e): Evaluation
    {
        $this->verifierCampagneOuverte($e);
        if ($e->isModifiable()) {
            return $e;
        }
        if (!$e->isBilan()) {
            $trace = $e->getRattachement()->getTrace();
            if ($trace->getStatut() === StatutTraceEnum::A_REVISER) {
                throw UnifolioValidationException::un('L\'étudiant doit d\'abord réviser et redéposer sa trace.');
            }
            if (\in_array($trace->getStatut(), [StatutTraceEnum::DEPOSEE, StatutTraceEnum::EVALUEE], true)) {
                $trace->changerStatut(StatutTraceEnum::EN_EVALUATION);
            }
        }
        $e->rouvrir();
        $this->em->flush();

        return $e;
    }

    public function supprimer(Evaluation $e): void
    {
        $this->verifierCampagneOuverte($e);
        if ($e->getStatut() !== StatutEvaluationEnum::EN_COURS) {
            throw UnifolioValidationException::un('Seule une évaluation en cours peut être supprimée.');
        }
        $this->em->remove($e);
        $this->em->flush();
    }

    // ---------- Cohérence des traces ----------

    /**
     * Aligne le statut d'une trace sur ses évaluations :
     *  - une révision demandée depuis le dernier dépôt la renvoie « à réviser » ;
     *  - tous ses rattachements ayant une évaluation rendue la rend « évaluée » (et inversement).
     */
    public function synchroniserTrace(Trace $trace): void
    {
        $statut = $trace->getStatut();
        if (!\in_array($statut, [StatutTraceEnum::EN_EVALUATION, StatutTraceEnum::EVALUEE], true)) {
            return;
        }
        $evaluations = $this->evaluations->findParTrace($trace);

        $depuis = $trace->getDeposeeLe();
        foreach ($evaluations as $e) {
            if ($e->getStatut() === StatutEvaluationEnum::REVISION_DEMANDEE && ($depuis === null || $e->getRendueLe() >= $depuis)) {
                $trace->changerStatut(StatutTraceEnum::A_REVISER);

                return;
            }
        }

        $tousRendus = $trace->getRattachements()->count() > 0;
        foreach ($trace->getRattachements() as $r) {
            $rendue = false;
            foreach ($evaluations as $e) {
                if ($e->getRattachement() === $r && $e->getStatut() === StatutEvaluationEnum::RENDUE) {
                    $rendue = true;
                    break;
                }
            }
            $tousRendus = $tousRendus && $rendue;
        }

        if ($tousRendus && $statut === StatutTraceEnum::EN_EVALUATION) {
            $trace->changerStatut(StatutTraceEnum::EVALUEE);
        } elseif (!$tousRendus && $statut === StatutTraceEnum::EVALUEE) {
            $trace->changerStatut(StatutTraceEnum::EN_EVALUATION);
        }
    }

    // ---------- Règles ----------

    private function campagneOuverte(EtudiantScolarite $scolarite): Campagne
    {
        $campagne = $this->campagnes->pourScolarite($scolarite)
            ?? throw UnifolioValidationException::un('Aucune campagne d\'évaluation n\'est définie pour cette année de BUT.');
        $this->verifierPhase($campagne);

        return $campagne;
    }

    private function verifierCampagneOuverte(Evaluation $e): void
    {
        $this->verifierPhase($e->getCampagne());
    }

    private function verifierPhase(Campagne $campagne): void
    {
        $phase = $campagne->getPhase();
        if (!$phase->accepteEvaluations()) {
            throw UnifolioValidationException::un(sprintf(
                $phase->value === 'cloturee'
                    ? 'La campagne « %s » est clôturée : les évaluations ne sont plus modifiables.'
                    : 'La campagne « %s » n\'est pas encore ouverte.',
                $campagne->getLibelle()
            ));
        }
    }

    private function verifierAffectation(Personnel $evaluateur, Etudiant $etudiant): void
    {
        if (!$this->acces->estAffecte($evaluateur, $etudiant)) {
            throw new AccessDeniedException('Vous n\'êtes pas chargé(e) d\'évaluer cet étudiant.');
        }
    }

    private function verifierAutoEvaluation(Campagne $campagne, ?\UnifolioBundle\Entity\AutoEvaluation $auto): void
    {
        if ($campagne->isAutoEvaluationRequise() && ($auto === null || $auto->isModifiable())) {
            throw UnifolioValidationException::un('Cette campagne exige que l\'étudiant s\'auto-évalue d\'abord : son auto-évaluation n\'est pas encore soumise.');
        }
    }

    /**
     * @template T
     *
     * @param callable(): T $fn
     *
     * @return T
     */
    private function garder(callable $fn): mixed
    {
        try {
            return $fn();
        } catch (\InvalidArgumentException|\DomainException $e) {
            throw UnifolioValidationException::un($e->getMessage());
        }
    }
}
