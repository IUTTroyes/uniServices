<?php

namespace UnifolioBundle\Service\Evaluation;

use UnifolioBundle\Entity\AutoEvaluation;
use UnifolioBundle\Entity\GrilleCritere;
use UnifolioBundle\Entity\ReponseCritere;
use UnifolioBundle\Service\Grille\GrilleNormalizer;

class AutoEvaluationNormalizer
{
    public function __construct(
        private readonly GrilleNormalizer $grilles,
    ) {
    }

    /** @return array<string, mixed> */
    public function resume(AutoEvaluation $a): array
    {
        $cible = $a->getCible();
        $competence = $a->getCompetenceParente();

        return [
            'uuid' => $a->getUuidString(),
            'bilan' => $a->isBilan(),
            'statut' => ['value' => $a->getStatut()->value, 'libelle' => $a->getStatut()->getLibelle(), 'badge' => $a->getStatut()->getBadge()],
            'score' => $a->getScore(),
            'soumiseLe' => $a->getSoumiseLe()?->format(\DATE_ATOM),
            'misAJourLe' => $a->getUpdatedAt()?->format(\DATE_ATOM),
            'cible' => [
                'type' => $a->getGranularite()->value,
                'id' => $cible->getId(),
                'code' => $cible instanceof \App\Entity\Apc\ApcApprentissageCritique ? $cible->getCode() : null,
                'libelle' => $cible->getLibelle(),
            ],
            'competence' => $competence !== null ? ['id' => $competence->getId(), 'nomCourt' => $competence->getNomCourt() ?? $competence->getLibelle(), 'couleur' => $competence->getCouleur()] : null,
            'trace' => $a->getRattachement() !== null ? [
                'uuid' => $a->getRattachement()->getTrace()->getUuidString(),
                'titre' => $a->getRattachement()->getTrace()->getTitre(),
                'argumentaire' => $a->getRattachement()->getArgumentaire(),
            ] : null,
        ];
    }

    /** @return array<string, mixed> */
    public function detail(AutoEvaluation $a): array
    {
        $contenu = $this->grilles->contenu($a->getGrilleVersion());
        $ids = array_map(static fn (GrilleCritere $c) => $c->getId(), $a->getCriteres());
        $contenu['criteres'] = array_values(array_filter($contenu['criteres'], static fn (array $c) => \in_array($c['id'], $ids, true)));
        unset($contenu['erreursPublication']);

        return $this->resume($a) + [
            'reflexion' => $a->getReflexion(),
            'modifiable' => $a->isModifiable(),
            'erreursSoumission' => $a->getErreursSoumission(),
            'grille' => ['libelle' => $a->getGrilleVersion()->getGrille()->getLibelle(), 'version' => $a->getGrilleVersion()->getNumero()] + $contenu,
            'reponses' => array_map(static fn (ReponseCritere $r) => [
                'critere' => $r->getCritere()->getId(),
                'echelon' => $r->getEchelon()?->getId(),
                'valeur' => $r->getValeurNumerique(),
                'booleen' => $r->getValeurBooleenne(),
                'texte' => $r->getTexte(),
            ], $a->getReponses()->toArray()),
        ];
    }
}
