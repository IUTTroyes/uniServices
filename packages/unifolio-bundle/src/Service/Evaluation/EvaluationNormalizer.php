<?php

namespace UnifolioBundle\Service\Evaluation;

use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Users\Personnel;
use UnifolioBundle\Entity\AutoEvaluation;
use UnifolioBundle\Entity\TraceRattachement;
use UnifolioBundle\Enum\GranulariteEnum;
use UnifolioBundle\Entity\Evaluation;
use UnifolioBundle\Entity\ReponseCritere;
use UnifolioBundle\Enum\StatutEvaluationEnum;
use UnifolioBundle\Repository\AutoEvaluationRepository;
use UnifolioBundle\Repository\EvaluationRepository;
use UnifolioBundle\Service\Grille\GrilleNormalizer;

class EvaluationNormalizer
{
    public function __construct(
        private readonly GrilleNormalizer $grilles,
        private readonly AutoEvaluationRepository $autoEvaluations,
        private readonly EvaluationRepository $evaluations,
    ) {
    }

    /** @return array<string, mixed> */
    public function resume(Evaluation $e): array
    {
        $cible = $e->getCible();
        $competence = $e->getCompetenceParente();

        return [
            'uuid' => $e->getUuidString(),
            'bilan' => $e->isBilan(),
            'statut' => ['value' => $e->getStatut()->value, 'libelle' => $e->getStatut()->getLibelle(), 'badge' => $e->getStatut()->getBadge()],
            'score' => $e->getScore(),
            'rendueLe' => $e->getRendueLe()?->format(\DATE_ATOM),
            'visibleEtudiant' => $e->isVisibleEtudiant(),
            'evaluateur' => ['id' => $e->getEvaluateur()->getId(), 'nom' => $e->getEvaluateur()->getDisplay()],
            'cible' => [
                'type' => $e->getGranularite()->value,
                'id' => $cible->getId(),
                'code' => $cible instanceof \App\Entity\Apc\ApcApprentissageCritique ? $cible->getCode() : null,
                'libelle' => $cible->getLibelle(),
            ],
            'competence' => $competence !== null ? ['id' => $competence->getId(), 'nomCourt' => $competence->getNomCourt() ?? $competence->getLibelle(), 'couleur' => $competence->getCouleur()] : null,
            'trace' => $e->getRattachement() !== null ? [
                'uuid' => $e->getRattachement()->getTrace()->getUuidString(),
                'titre' => $e->getRattachement()->getTrace()->getTitre(),
                'rattachementId' => $e->getRattachement()->getId(),
            ] : null,
        ];
    }

    /** Vue de l'évaluateur : grille complète, saisie, comparaison avec l'auto-évaluation, autres évaluateurs. */
    /** @return array<string, mixed> */
    public function detail(Evaluation $e): array
    {
        $contenu = $this->grilles->contenu($e->getGrilleVersion());
        unset($contenu['erreursPublication']);

        return $this->resume($e) + [
            'appreciation' => $e->getAppreciation(),
            'modifiable' => $e->isModifiable(),
            'erreursRendu' => $e->getErreursRendu(),
            'etudiant' => ['id' => $e->getEtudiant()->getId(), 'nom' => $e->getEtudiant()->getDisplay()],
            'campagne' => ['uuid' => $e->getCampagne()->getUuidString(), 'libelle' => $e->getCampagne()->getLibelle(), 'phase' => $e->getCampagne()->getPhase()->value],
            'grille' => ['libelle' => $e->getGrilleVersion()->getGrille()->getLibelle(), 'version' => $e->getGrilleVersion()->getNumero()] + $contenu,
            'reponses' => array_map(static fn (ReponseCritere $r) => [
                'critere' => $r->getCritere()->getId(),
                'echelon' => $r->getEchelon()?->getId(),
                'valeur' => $r->getValeurNumerique(),
                'booleen' => $r->getValeurBooleenne(),
                'texte' => $r->getTexte(),
            ], $e->getReponses()->toArray()),
            'reponsesDetaillees' => array_map($this->reponseDetail(...), $e->getReponses()->toArray()),
            'autoEvaluation' => $this->autoPourComparaison($e),
            'autres' => array_map($this->resume(...), $this->autresEvaluations($e)),
        ];
    }

    /** Vue de l'étudiant : uniquement ce que l'enseignant lui a communiqué. @return array<string, mixed> */
    public function retourEtudiant(Evaluation $e): array
    {
        return $this->resume($e) + [
            'appreciation' => $e->getAppreciation(),
            'reponses' => array_values(array_map(
                $this->reponseDetail(...),
                array_filter($e->getReponses()->toArray(), static fn (ReponseCritere $r) => $r->getCritere()->isVisibleEtudiant())
            )),
        ];
    }

    /** @return array<string, mixed> */
    public function reponseDetail(ReponseCritere $r): array
    {
        $echelon = $r->getEchelon();
        $critere = $r->getCritere();

        return [
            'critere' => $critere->getId(),
            'libelle' => $critere->getLibelle(),
            'type' => $critere->getType()->value,
            'poids' => $critere->getPoids(),
            'echelon' => $echelon !== null ? ['libelle' => $echelon->getLibelle(), 'couleur' => $echelon->getCouleur(), 'valeur' => $echelon->getValeur()] : null,
            'valeur' => $r->getValeurNumerique(),
            'noteMax' => $critere->getNoteMax(),
            'booleen' => $r->getValeurBooleenne(),
            'texte' => $r->getTexte(),
            'normalisee' => $r->getValeurNormalisee(),
        ];
    }

    /**
     * Vue allégée d'une évaluation (sans la grille) pour les écrans qui en affichent plusieurs à la fois.
     *
     * @return array<string, mixed>
     */
    public function detailLeger(Evaluation $e): array
    {
        return $this->resume($e) + [
            'appreciation' => $e->getAppreciation(),
            'modifiable' => $e->isModifiable(),
            'grilleVersion' => $e->getGrilleVersion()->getNumero(),
            'erreursRendu' => $e->getErreursRendu(),
            'reponses' => array_map(static fn (ReponseCritere $r) => [
                'critere' => $r->getCritere()->getId(),
                'echelon' => $r->getEchelon()?->getId(),
                'valeur' => $r->getValeurNumerique(),
                'booleen' => $r->getValeurBooleenne(),
                'texte' => $r->getTexte(),
            ], $e->getReponses()->toArray()),
            'reponsesDetaillees' => array_map($this->reponseDetail(...), $e->getReponses()->toArray()),
        ];
    }

    /** @return array<string, mixed>|null auto-évaluation soumise de l'étudiant sur la même cible */
    private function autoPourComparaison(Evaluation $e): ?array
    {
        return $this->autoSoumise($e->getRattachement(), $e->getScolarite(), $e->getGranularite(), (int) $e->getCible()->getId());
    }

    /** @return array<string, mixed>|null auto-évaluation soumise (jamais un brouillon) sur une cible */
    public function autoSoumise(?TraceRattachement $rattachement, EtudiantScolarite $scolarite, GranulariteEnum $granularite, int $cibleId): ?array
    {
        $auto = $rattachement !== null
            ? $this->autoEvaluations->findOneByRattachement($rattachement)
            : $this->autoEvaluations->findBilan($scolarite, $granularite, $cibleId);
        if (!$auto instanceof AutoEvaluation || $auto->isModifiable()) {
            return null;
        }

        return [
            'uuid' => $auto->getUuidString(),
            'score' => $auto->getScore(),
            'reflexion' => $auto->getReflexion(),
            'soumiseLe' => $auto->getSoumiseLe()?->format(\DATE_ATOM),
            'reponses' => array_map($this->reponseDetail(...), $auto->getReponses()->toArray()),
        ];
    }

    /** @return Evaluation[] évaluations rendues des autres évaluateurs sur la même cible */
    private function autresEvaluations(Evaluation $e): array
    {
        return $this->autresRendues($e->getRattachement(), $e->getScolarite(), $e->getGranularite(), (int) $e->getCible()->getId(), $e->getEvaluateur());
    }

    /** @return Evaluation[] évaluations rendues par d'autres évaluateurs que $sauf sur une cible */
    public function autresRendues(?TraceRattachement $rattachement, EtudiantScolarite $scolarite, GranulariteEnum $granularite, int $cibleId, ?Personnel $sauf): array
    {
        $memeCible = $rattachement !== null
            ? $this->evaluations->findBy(['rattachement' => $rattachement])
            : $this->evaluations->findBilans($scolarite, $granularite, $cibleId);

        return array_values(array_filter(
            $memeCible,
            static fn (Evaluation $autre) => ($sauf === null || !$autre->estDe($sauf)) && $autre->getStatut() === StatutEvaluationEnum::RENDUE
        ));
    }
}
