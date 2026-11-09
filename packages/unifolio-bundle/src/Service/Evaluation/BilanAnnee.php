<?php

namespace UnifolioBundle\Service\Evaluation;

use App\Entity\Apc\ApcApprentissageCritique;
use App\Entity\Apc\ApcCompetence;
use App\Entity\Apc\ApcNiveau;
use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use UnifolioBundle\Entity\Evaluation;
use UnifolioBundle\Entity\TraceRattachement;
use UnifolioBundle\Enum\GranulariteEnum;
use UnifolioBundle\Enum\StatutEvaluationEnum;
use UnifolioBundle\Repository\AutoEvaluationRepository;
use UnifolioBundle\Repository\EvaluationRepository;
use UnifolioBundle\Repository\TraceRepository;
use UnifolioBundle\Service\Grille\GrilleResolver;
use UnifolioBundle\Service\Referentiel\ReferentielAnnee;

/**
 * Vue d'ensemble d'une année : pour chaque élément du référentiel (selon la granularité de la formation),
 * les traces qui le démontrent, leurs auto-évaluations, les évaluations des enseignants et la synthèse.
 *
 * Vue étudiante (sans évaluateur) : seules les évaluations communiquées apparaissent.
 * Vue évaluateur : ses évaluations (quel que soit leur statut) et celles, rendues, des autres.
 */
class BilanAnnee
{
    public function __construct(
        private readonly ReferentielAnnee $referentiel,
        private readonly TraceRepository $traces,
        private readonly AutoEvaluationRepository $autoEvaluations,
        private readonly EvaluationRepository $evaluations,
        private readonly AutoEvaluationNormalizer $normalizer,
        private readonly EvaluationNormalizer $evaluationNormalizer,
        private readonly GrilleResolver $grilles,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function elements(Etudiant $etudiant, EtudiantScolarite $scolarite, GranulariteEnum $granularite, ?Personnel $evaluateur = null, bool $toutesRendues = false): array
    {
        $rattachements = [];
        foreach ($this->traces->findBibliotheque($etudiant, ['scolarite' => $scolarite->getId()]) as $trace) {
            foreach ($trace->getRattachements() as $r) {
                $rattachements[] = $r;
            }
        }
        $autoParRattachement = $this->autoEvaluations->findParRattachements($rattachements);

        $bilans = [];
        foreach ($this->autoEvaluations->findParScolarite($scolarite) as $a) {
            if ($a->isBilan() && $a->getGranularite() === $granularite) {
                $bilans[$a->getCible()->getId()] = $a;
            }
        }

        // Évaluations visibles selon le point de vue.
        $evalParRattachement = [];
        $evalBilan = [];
        foreach ($this->evaluations->findParScolarite($scolarite) as $e) {
            if (!$this->visible($e, $evaluateur, $toutesRendues)) {
                continue;
            }
            if ($e->isBilan()) {
                $evalBilan[$e->getCible()->getId()][] = $e;
            } else {
                $evalParRattachement[$e->getRattachement()->getId()][] = $e;
            }
        }
        $regle = $this->grilles->agregationPourScolarite($scolarite);

        $elements = [];
        foreach ($this->cibles($scolarite, $granularite) as $cible) {
            $competence = $cible instanceof ApcCompetence ? $cible
                : ($cible instanceof ApcNiveau ? $cible->getCompetence() : $cible->getNiveau()?->getCompetence());

            $traces = [];
            $evaluationsElement = [];
            foreach ($rattachements as $r) {
                if (!$this->concerne($r, $cible)) {
                    continue;
                }
                $auto = $autoParRattachement[$r->getId()] ?? null;
                $evalsTrace = $evalParRattachement[$r->getId()] ?? [];
                array_push($evaluationsElement, ...$evalsTrace);
                $traces[] = [
                    'uuid' => $r->getTrace()->getUuidString(),
                    'titre' => $r->getTrace()->getTitre(),
                    'statut' => $r->getTrace()->getStatut()->getLibelle(),
                    'statutValue' => $r->getTrace()->getStatut()->value,
                    'rattachementId' => $r->getId(),
                    'autoEvaluation' => $auto !== null ? $this->normalizer->resume($auto) : null,
                    'evaluations' => $this->resumes($evalsTrace, $evaluateur),
                ];
            }

            $bilan = $bilans[$cible->getId()] ?? null;
            $evalsBilan = $evalBilan[$cible->getId()] ?? [];
            $synthese = SyntheseElement::calculer($regle, $evaluationsElement);

            $elements[] = [
                'type' => $granularite->value,
                'id' => $cible->getId(),
                'code' => $cible instanceof ApcApprentissageCritique ? $cible->getCode() : null,
                'ordre' => $cible instanceof ApcNiveau ? $cible->getOrdre() : null,
                'libelle' => $cible->getLibelle(),
                'competence' => $competence !== null ? ['id' => $competence->getId(), 'nomCourt' => $competence->getNomCourt() ?? $competence->getLibelle(), 'libelle' => $competence->getLibelle(), 'couleur' => $competence->getCouleur()] : null,
                'traces' => $traces,
                'bilan' => $bilan !== null ? $this->normalizer->resume($bilan) : null,
                'evaluationsBilan' => $this->resumes($evalsBilan, $evaluateur),
                'synthese' => ['score' => $synthese['score'], 'nbTraces' => $synthese['nbTraces'], 'regle' => $regle->value],
            ];
        }

        return $elements;
    }

    /**
     * @param Evaluation[] $evaluations
     *
     * @return list<array<string, mixed>> résumés, avec l'indicateur « mienne » pour l'évaluateur courant
     */
    private function resumes(array $evaluations, ?Personnel $evaluateur): array
    {
        return array_map(
            fn (Evaluation $e) => $this->evaluationNormalizer->resume($e) + ['mienne' => $evaluateur !== null && $e->estDe($evaluateur)],
            $evaluations
        );
    }

    /** L'évaluateur voit les siennes (même en cours) et les rendues des autres ; l'étudiant les communiquées. */
    private function visible(Evaluation $e, ?Personnel $evaluateur, bool $toutesRendues = false): bool
    {
        // Suivi de promo : toutes les évaluations rendues, quel qu'en soit l'auteur ou la communication à l'étudiant.
        if ($toutesRendues) {
            return $e->getStatut() === StatutEvaluationEnum::RENDUE;
        }

        if ($evaluateur === null) {
            return $e->isVisibleEtudiant() && $e->getStatut() !== StatutEvaluationEnum::EN_COURS;
        }

        return $e->estDe($evaluateur) || $e->getStatut() === StatutEvaluationEnum::RENDUE;
    }

    /** @return list<ApcCompetence|ApcNiveau|ApcApprentissageCritique> éléments du référentiel de l'année, selon la granularité */
    public function cibles(EtudiantScolarite $scolarite, GranulariteEnum $granularite): array
    {
        $niveaux = $this->referentiel->niveaux($scolarite);

        return match ($granularite) {
            GranulariteEnum::NIVEAU => $niveaux,
            GranulariteEnum::AC => array_merge([], ...array_map(static fn (ApcNiveau $n) => $n->getApprentissageCritique()->toArray(), $niveaux)),
            GranulariteEnum::COMPETENCE => $this->competencesDe($niveaux),
        };
    }

    /**
     * @param ApcNiveau[] $niveaux
     *
     * @return ApcCompetence[] sans doublon, dans l'ordre des niveaux
     */
    private function competencesDe(array $niveaux): array
    {
        $competences = [];
        foreach ($niveaux as $niveau) {
            $c = $niveau->getCompetence();
            if ($c !== null) {
                $competences[$c->getId() ?? spl_object_id($c)] = $c;
            }
        }

        return array_values($competences);
    }

    /** Un rattachement concerne un élément s'il le vise directement ou vise un élément plus fin qui en dépend. */
    private function concerne(TraceRattachement $r, ApcCompetence|ApcNiveau|ApcApprentissageCritique $cible): bool
    {
        $meme = static fn (?object $a, ?object $b) => $a !== null && $b !== null && $a->getId() === $b->getId();

        return match (true) {
            $cible instanceof ApcApprentissageCritique => $meme($r->getApprentissageCritique(), $cible),
            $cible instanceof ApcNiveau => $meme($r->getNiveau(), $cible) || $meme($r->getApprentissageCritique()?->getNiveau(), $cible),
            $cible instanceof ApcCompetence => $meme($r->getCompetenceParente(), $cible),
        };
    }
}
