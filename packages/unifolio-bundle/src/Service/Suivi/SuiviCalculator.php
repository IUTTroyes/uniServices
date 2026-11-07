<?php

namespace UnifolioBundle\Service\Suivi;

use App\Entity\Etudiant\EtudiantScolarite;
use UnifolioBundle\Service\Evaluation\AutoEvaluationManager;
use UnifolioBundle\Service\Evaluation\BilanAnnee;
use UnifolioBundle\Service\Trace\UnifolioValidationException;

/**
 * Progression d'un étudiant sur UNE année de BUT, à la granularité choisie par la formation.
 * Un élément est « couvert » quand au moins une trace déposée (ou en cours d'évaluation / évaluée) lui est rattachée.
 * Les scores sont normalisés entre 0 et 1.
 */
class SuiviCalculator
{
    private const STATUTS_COUVRANTS = ['deposee', 'en_evaluation', 'evaluee'];
    /** Écart auto-évaluation / évaluation à partir duquel on le signale (comme la comparaison de l'évaluateur). */
    public const ECART_NOTABLE = 0.2;

    public function __construct(
        private readonly BilanAnnee $bilan,
        private readonly AutoEvaluationManager $autoEvaluation,
    ) {
    }

    /**
     * @param bool $vueEnseignant true : toutes les évaluations rendues ; false : seulement celles communiquées à l'étudiant
     * @param bool $detail        inclure les éléments (AC / niveaux) de chaque compétence
     *
     * @return array<string, mixed>
     */
    public function pourScolarite(EtudiantScolarite $scolarite, bool $vueEnseignant, bool $detail = false): array
    {
        try {
            $granularite = $this->autoEvaluation->granularitePour($scolarite);
        } catch (UnifolioValidationException $e) {
            return ['disponible' => false, 'message' => $e->getMessage(), 'competences' => [], 'resume' => self::resumeVide()];
        }

        $elements = $this->bilan->elements($scolarite->getEtudiant(), $scolarite, $granularite, null, $vueEnseignant);
        $competences = [];
        $traitees = 0;
        $aEvaluer = 0;
        $ecartMax = null;
        foreach ($elements as $el) {
            $c = $el['competence'] ?? null;
            $cle = (int) ($c['id'] ?? 0);
            $competences[$cle] ??= [
                'id' => $c['id'] ?? null, 'nomCourt' => $c['nomCourt'] ?? 'Autre', 'libelle' => $c['libelle'] ?? null, 'couleur' => $c['couleur'] ?? null,
                'total' => 0, 'couverts' => 0, 'nbTraces' => 0, '_eval' => [], '_auto' => [], 'elements' => [],
            ];

            $couvertes = array_filter($el['traces'], static fn (array $t) => \in_array($t['statutValue'], self::STATUTS_COUVRANTS, true));
            $couvert = $couvertes !== [];
            $eval = $el['synthese']['score'] ?? $this->moyenne(array_map(
                static fn (array $e) => $e['score'] ?? null,
                array_filter($el['evaluationsBilan'], static fn (array $e) => ($e['statut']['value'] ?? null) === 'rendue')
            ));
            $auto = $this->scoreAuto($el);
            foreach ($couvertes as $t) {
                $rendue = array_filter($t['evaluations'], static fn (array $e) => ($e['statut']['value'] ?? null) === 'rendue');
                if ($rendue === []) {
                    ++$aEvaluer;
                }
            }

            $comp = &$competences[$cle];
            ++$comp['total'];
            $comp['couverts'] += $couvert ? 1 : 0;
            $comp['nbTraces'] += \count($couvertes);
            $traitees += \count($couvertes);
            if ($eval !== null) {
                $comp['_eval'][] = $eval;
            }
            if ($auto !== null) {
                $comp['_auto'][] = $auto;
            }
            if ($eval !== null && $auto !== null) {
                $ecartMax = max($ecartMax ?? 0.0, abs($eval - $auto));
            }
            if ($detail) {
                $comp['elements'][] = [
                    'type' => $el['type'], 'id' => $el['id'], 'code' => $el['code'], 'ordre' => $el['ordre'], 'libelle' => $el['libelle'],
                    'couvert' => $couvert, 'nbTraces' => \count($couvertes), 'evalScore' => $eval, 'autoScore' => $auto,
                ];
            }
            unset($comp);
        }

        $total = 0;
        $couverts = 0;
        $evals = [];
        $autos = [];
        foreach ($competences as &$comp) {
            $comp['evalScore'] = $this->moyenne($comp['_eval']);
            $comp['autoScore'] = $this->moyenne($comp['_auto']);
            array_push($evals, ...$comp['_eval']);
            array_push($autos, ...$comp['_auto']);
            $total += $comp['total'];
            $couverts += $comp['couverts'];
            unset($comp['_eval'], $comp['_auto']);
            if (!$detail) {
                unset($comp['elements']);
            }
        }
        unset($comp);

        return [
            'disponible' => true,
            'granularite' => ['value' => $granularite->value, 'libelle' => $granularite->getLibelle()],
            'competences' => array_values($competences),
            'resume' => [
                'total' => $total, 'couverts' => $couverts, 'nbTraces' => $traitees, 'aEvaluer' => $aEvaluer,
                'evalScore' => $this->moyenne($evals), 'autoScore' => $this->moyenne($autos), 'ecartMax' => $ecartMax !== null ? round($ecartMax, 4) : null,
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function resumeVide(): array
    {
        return ['total' => 0, 'couverts' => 0, 'nbTraces' => 0, 'aEvaluer' => 0, 'evalScore' => null, 'autoScore' => null, 'ecartMax' => null];
    }

    /** Auto-évaluation du bilan de l'élément si elle est soumise, sinon moyenne de celles de ses traces. */
    private function scoreAuto(array $el): ?float
    {
        $soumise = static fn (?array $a) => $a !== null && ($a['statut']['value'] ?? null) === 'soumise' && ($a['score'] ?? null) !== null;
        if ($soumise($el['bilan'])) {
            return (float) $el['bilan']['score'];
        }

        return $this->moyenne(array_map(
            static fn (array $t) => $soumise($t['autoEvaluation']) ? (float) $t['autoEvaluation']['score'] : null,
            $el['traces']
        ));
    }

    /** @param array<int, float|int|null> $valeurs */
    private function moyenne(array $valeurs): ?float
    {
        $valeurs = array_values(array_filter($valeurs, static fn ($v) => $v !== null));

        return $valeurs === [] ? null : round(array_sum($valeurs) / \count($valeurs), 4);
    }
}
