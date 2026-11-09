<?php

namespace UnifolioBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use UnifolioBundle\Entity\Evaluation;
use UnifolioBundle\Entity\TraceRattachement;
use UnifolioBundle\Enum\AgregationEnum;
use UnifolioBundle\Enum\StatutEvaluationEnum;
use UnifolioBundle\Service\Evaluation\SyntheseElement;

class SyntheseElementTest extends TestCase
{
    public function testAgregationDesTraces(): void
    {
        $evals = [
            $this->evaluation(1, 0.4, '2026-10-01'),
            $this->evaluation(2, 0.9, '2026-11-01'),
            $this->evaluation(3, 0.6, '2026-12-01'),
        ];

        $this->assertEqualsWithDelta(0.9, SyntheseElement::calculer(AgregationEnum::MAX, $evals)['score'], 0.0001);
        $this->assertEqualsWithDelta((0.4 + 0.9 + 0.6) / 3, SyntheseElement::calculer(AgregationEnum::MOYENNE, $evals)['score'], 0.0001);
        $this->assertEqualsWithDelta(0.6, SyntheseElement::calculer(AgregationEnum::DERNIERE, $evals)['score'], 0.0001);
        $this->assertSame(3, SyntheseElement::calculer(AgregationEnum::MAX, $evals)['nbTraces']);
    }

    public function testPlusieursEvaluateursSurLaMemeTraceSontMoyennes(): void
    {
        $evals = [$this->evaluation(1, 0.4, '2026-10-01'), $this->evaluation(1, 0.8, '2026-10-02'), $this->evaluation(2, 0.5, '2026-10-03')];

        $resultat = SyntheseElement::calculer(AgregationEnum::MAX, $evals);

        $this->assertEqualsWithDelta(0.6, $resultat['score'], 0.0001);
        $this->assertSame(2, $resultat['nbTraces']);
    }

    public function testSeulesLesEvaluationsRenduesComptent(): void
    {
        $evals = [
            $this->evaluation(1, 0.9, '2026-10-01', StatutEvaluationEnum::EN_COURS),
            $this->evaluation(2, 0.7, '2026-10-02', StatutEvaluationEnum::REVISION_DEMANDEE),
            $this->evaluation(3, null, '2026-10-03'),
        ];

        $this->assertSame(['score' => null, 'nbTraces' => 0], SyntheseElement::calculer(AgregationEnum::MAX, $evals));
        $this->assertSame(['score' => null, 'nbTraces' => 0], SyntheseElement::calculer(AgregationEnum::MAX, []));
    }

    private function evaluation(int $rattachementId, ?float $score, string $date, StatutEvaluationEnum $statut = StatutEvaluationEnum::RENDUE): Evaluation
    {
        $rattachement = $this->createMock(TraceRattachement::class);
        $rattachement->method('getId')->willReturn($rattachementId);
        $e = $this->createMock(Evaluation::class);
        $e->method('getStatut')->willReturn($statut);
        $e->method('getScore')->willReturn($score);
        $e->method('getRattachement')->willReturn($rattachement);
        $e->method('getRendueLe')->willReturn(new \DateTimeImmutable($date));

        return $e;
    }
}
