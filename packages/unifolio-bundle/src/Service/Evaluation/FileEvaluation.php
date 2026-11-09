<?php

namespace UnifolioBundle\Service\Evaluation;

use App\Entity\Users\Personnel;
use UnifolioBundle\Entity\Campagne;
use UnifolioBundle\Entity\Evaluation;
use UnifolioBundle\Enum\StatutEvaluationEnum;
use UnifolioBundle\Enum\StatutTraceEnum;
use UnifolioBundle\Repository\AutoEvaluationRepository;
use UnifolioBundle\Repository\CampagneAffectationRepository;
use UnifolioBundle\Repository\EvaluationRepository;
use UnifolioBundle\Repository\TraceRepository;
use UnifolioBundle\Service\Campagne\CampagneEtudiants;
use UnifolioBundle\Service\Campagne\CampagneNormalizer;

/** File « à évaluer » d'un enseignant : ses étudiants par campagne, avec ce qu'il reste à faire. */
class FileEvaluation
{
    private const STATUTS_EVALUABLES = [StatutTraceEnum::DEPOSEE, StatutTraceEnum::EN_EVALUATION, StatutTraceEnum::EVALUEE, StatutTraceEnum::A_REVISER];

    public function __construct(
        private readonly CampagneAffectationRepository $affectations,
        private readonly CampagneEtudiants $etudiants,
        private readonly TraceRepository $traces,
        private readonly EvaluationRepository $evaluations,
        private readonly AutoEvaluationRepository $autoEvaluations,
        private readonly CampagneNormalizer $campagnes,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function pour(Personnel $evaluateur): array
    {
        $parCampagne = [];
        foreach ($this->affectations->findParEvaluateur($evaluateur) as $a) {
            $parCampagne[$a->getCampagne()->getId()]['campagne'] = $a->getCampagne();
            $parCampagne[$a->getCampagne()->getId()]['etudiants'][] = $a->getEtudiant();
        }

        $file = [];
        foreach ($parCampagne as $groupe) {
            $file[] = $this->campagne($evaluateur, $groupe['campagne'], $groupe['etudiants']);
        }
        usort($file, static fn (array $a, array $b) => [$a['campagne']['phase']['value'] === 'cloturee', $b['campagne']['anneeUniversitaire']['libelle']] <=> [$b['campagne']['phase']['value'] === 'cloturee', $a['campagne']['anneeUniversitaire']['libelle']]);

        return $file;
    }

    /**
     * @param \App\Entity\Users\Etudiant[] $etudiants
     *
     * @return array<string, mixed>
     */
    public function campagne(Personnel $evaluateur, Campagne $campagne, array $etudiants): array
    {
        $scolarites = $this->etudiants->scolarites($campagne);
        $mesEvaluations = [];
        foreach ($this->evaluations->findParEvaluateurEtCampagne($evaluateur, $campagne) as $e) {
            if ($e->getRattachement() !== null) {
                $mesEvaluations[$e->getRattachement()->getId()] = $e;
            }
        }

        $lignes = [];
        $totaux = ['aEvaluer' => 0, 'enCours' => 0, 'aReprendre' => 0, 'enAttente' => 0];
        foreach ($etudiants as $etudiant) {
            $scolarite = $scolarites[$etudiant->getId()] ?? null;
            $ligne = ['id' => $etudiant->getId(), 'nom' => $etudiant->getNom(), 'prenom' => $etudiant->getPrenom(), 'scolarite' => $scolarite?->getId(), 'nbTraces' => 0, 'aEvaluer' => 0, 'enCours' => 0, 'rendues' => 0, 'aReprendre' => 0, 'enAttente' => 0, 'attenteAutoEvaluation' => 0];
            if ($scolarite !== null) {
                $traces = array_filter(
                    $this->traces->findBibliotheque($etudiant, ['scolarite' => $scolarite->getId()]),
                    static fn ($t) => \in_array($t->getStatut(), self::STATUTS_EVALUABLES, true)
                );
                $rattachements = [];
                foreach ($traces as $t) {
                    array_push($rattachements, ...$t->getRattachements()->toArray());
                }
                $autos = $campagne->isAutoEvaluationRequise() ? $this->autoEvaluations->findParRattachements($rattachements) : [];

                $ligne['nbTraces'] = \count($traces);
                foreach ($traces as $t) {
                    foreach ($t->getRattachements() as $r) {
                        $this->compter($ligne, $t, $mesEvaluations[$r->getId()] ?? null, $campagne->isAutoEvaluationRequise() ? ($autos[$r->getId()] ?? null) : false);
                    }
                }
            }
            foreach (['aEvaluer', 'enCours', 'aReprendre', 'enAttente'] as $k) {
                $totaux[$k] += $ligne[$k];
            }
            $lignes[] = $ligne;
        }

        return ['campagne' => $this->campagnes->resume($campagne), 'etudiants' => $lignes, 'totaux' => $totaux];
    }

    /**
     * @param array<string, int|string|null>                 $ligne
     * @param \UnifolioBundle\Entity\AutoEvaluation|false|null $auto false = auto-évaluation non exigée
     */
    private function compter(array &$ligne, \UnifolioBundle\Entity\Trace $trace, ?Evaluation $mienne, \UnifolioBundle\Entity\AutoEvaluation|false|null $auto): void
    {
        if ($trace->getStatut() === StatutTraceEnum::A_REVISER) {
            if ($mienne?->getStatut() === StatutEvaluationEnum::REVISION_DEMANDEE) {
                ++$ligne['enAttente'];
            }

            return;
        }

        switch ($mienne?->getStatut()) {
            case null:
                if ($auto !== false && ($auto === null || $auto->isModifiable())) {
                    ++$ligne['attenteAutoEvaluation'];
                } else {
                    ++$ligne['aEvaluer'];
                }
                break;
            case StatutEvaluationEnum::EN_COURS:
                ++$ligne['enCours'];
                break;
            case StatutEvaluationEnum::RENDUE:
                ++$ligne['rendues'];
                break;
            case StatutEvaluationEnum::REVISION_DEMANDEE:
                $depuis = $trace->getDeposeeLe();
                if ($depuis !== null && $mienne->getRendueLe() !== null && $mienne->getRendueLe() < $depuis) {
                    ++$ligne['aReprendre'];
                } else {
                    ++$ligne['enAttente'];
                }
                break;
        }
    }
}
