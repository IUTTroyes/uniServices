<?php

namespace UnifolioBundle\Service\Evaluation;

use UnifolioBundle\Entity\Evaluation;
use UnifolioBundle\Enum\AgregationEnum;
use UnifolioBundle\Enum\StatutEvaluationEnum;

/**
 * Score d'un élément du référentiel à partir des évaluations rendues de ses traces.
 * Plusieurs évaluateurs sur un même rattachement : moyenne de leurs scores ;
 * puis combinaison des rattachements selon la règle de la formation (D5).
 */
final class SyntheseElement
{
    /**
     * @param Evaluation[] $evaluations évaluations de traces (rattachements) pour un même élément
     *
     * @return array{score: ?float, nbTraces: int}
     */
    public static function calculer(AgregationEnum $regle, array $evaluations): array
    {
        /** @var array<int|string, array{scores: float[], date: ?\DateTimeImmutable}> $parRattachement */
        $parRattachement = [];
        foreach ($evaluations as $e) {
            if ($e->getStatut() !== StatutEvaluationEnum::RENDUE || $e->getScore() === null || $e->getRattachement() === null) {
                continue;
            }
            $cle = $e->getRattachement()->getId() ?? spl_object_id($e->getRattachement());
            $parRattachement[$cle] ??= ['scores' => [], 'date' => null];
            $parRattachement[$cle]['scores'][] = $e->getScore();
            $date = $e->getRendueLe();
            if ($date !== null && ($parRattachement[$cle]['date'] === null || $date > $parRattachement[$cle]['date'])) {
                $parRattachement[$cle]['date'] = $date;
            }
        }
        if ($parRattachement === []) {
            return ['score' => null, 'nbTraces' => 0];
        }

        $scores = array_map(static fn (array $g) => array_sum($g['scores']) / \count($g['scores']), $parRattachement);

        $score = match ($regle) {
            AgregationEnum::MAX => max($scores),
            AgregationEnum::MOYENNE => array_sum($scores) / \count($scores),
            AgregationEnum::DERNIERE => self::laPlusRecente($parRattachement, $scores),
        };

        return ['score' => round($score, 4), 'nbTraces' => \count($parRattachement)];
    }

    /**
     * @param array<int|string, array{scores: float[], date: ?\DateTimeImmutable}> $groupes
     * @param array<int|string, float>                                             $scores
     */
    private static function laPlusRecente(array $groupes, array $scores): float
    {
        $retenu = null;
        $date = null;
        foreach ($groupes as $cle => $g) {
            if ($retenu === null || ($g['date'] !== null && ($date === null || $g['date'] > $date))) {
                $retenu = $cle;
                $date = $g['date'];
            }
        }

        return $scores[$retenu];
    }

    private function __construct()
    {
    }
}
