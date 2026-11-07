<?php

namespace UnifolioBundle\Tests\Entity;

use App\Entity\Structure\StructureAnnee;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Structure\StructureTypeDiplome;
use PHPUnit\Framework\TestCase;
use UnifolioBundle\Entity\Campagne;
use UnifolioBundle\Enum\PhaseCampagneEnum;

class CampagneTest extends TestCase
{
    private StructureDiplome $diplome;

    protected function setUp(): void
    {
        $type = $this->createMock(StructureTypeDiplome::class);
        $type->method('isApc')->willReturn(true);
        $this->diplome = $this->createMock(StructureDiplome::class);
        $this->diplome->method('getId')->willReturn(10);
        $this->diplome->method('getTypeDiplome')->willReturn($type);
    }

    public function testReserveeAuxDiplomesApc(): void
    {
        $type = $this->createMock(StructureTypeDiplome::class);
        $type->method('isApc')->willReturn(false);
        $diplome = $this->createMock(StructureDiplome::class);
        $diplome->method('getTypeDiplome')->willReturn($type);

        $this->expectException(\InvalidArgumentException::class);
        new Campagne('LP', $diplome, $this->createMock(StructureAnneeUniversitaire::class), new \DateTimeImmutable());
    }

    public function testPhasesSelonLeCalendrier(): void
    {
        $c = $this->campagne()->definirCalendrier($this->d('2026-09-01'), $this->d('2026-12-15'), $this->d('2027-01-31'), $this->d('2027-02-15'));

        $this->assertSame(PhaseCampagneEnum::A_VENIR, $c->getPhase($this->d('2026-08-31')));
        $this->assertSame(PhaseCampagneEnum::DEPOT, $c->getPhase($this->d('2026-10-01')));
        $this->assertSame(PhaseCampagneEnum::DEPOT, $c->getPhase($this->d('2026-12-15')));
        $this->assertSame(PhaseCampagneEnum::EVALUATION, $c->getPhase($this->d('2026-12-16')));
        $this->assertSame(PhaseCampagneEnum::CLOTUREE, $c->getPhase($this->d('2027-02-15')));
        $this->assertTrue($c->getPhase($this->d('2026-10-01'))->accepteEvaluations());
        $this->assertTrue($c->getPhase($this->d('2027-01-10'))->accepteEvaluations());
        $this->assertFalse($c->getPhase($this->d('2026-08-31'))->accepteEvaluations());
        $this->assertFalse($c->getPhase($this->d('2027-03-01'))->accepteEvaluations());
    }

    public function testSansLimitesLaCampagneResteEnDepotJusquALaCloture(): void
    {
        $c = $this->campagne()->definirCalendrier($this->d('2026-09-01'), null, null, null);

        $this->assertSame(PhaseCampagneEnum::DEPOT, $c->getPhase($this->d('2030-01-01')));
        $this->assertFalse($c->isEvaluationEnRetard($this->d('2030-01-01')));
    }

    public function testRetardDEvaluation(): void
    {
        $c = $this->campagne()->definirCalendrier($this->d('2026-09-01'), $this->d('2026-12-01'), $this->d('2027-01-10'), $this->d('2027-02-01'));

        $this->assertFalse($c->isEvaluationEnRetard($this->d('2027-01-10')));
        $this->assertTrue($c->isEvaluationEnRetard($this->d('2027-01-20')));
        $this->assertFalse($c->isEvaluationEnRetard($this->d('2027-02-02')), 'Clôturée : plus « en retard »');
    }

    public function testLesDatesDoiventSeSuivre(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->campagne()->definirCalendrier($this->d('2026-09-01'), $this->d('2026-12-01'), $this->d('2026-11-01'), null);
    }

    public function testCloturerRamenePasLesLimitesAuDela(): void
    {
        $c = $this->campagne()->definirCalendrier($this->d('2026-09-01'), $this->d('2027-06-01'), $this->d('2027-06-30'), null);
        $c->cloturer($this->d('2026-12-01'));

        $this->assertTrue($c->isCloturee($this->d('2026-12-01')));
        $this->assertEquals($this->d('2026-12-01'), $c->getDepotLimite());
        $this->assertEquals($this->d('2026-12-01'), $c->getEvaluationLimite());
    }

    public function testAnneeDeBUTDuMemeDiplomeSeulement(): void
    {
        $autreDiplome = $this->createMock(StructureDiplome::class);
        $autreDiplome->method('getId')->willReturn(99);
        $annee = $this->createMock(StructureAnnee::class);
        $annee->method('getDiplome')->willReturn($autreDiplome);

        $this->expectException(\InvalidArgumentException::class);
        $this->campagne()->setAnneeFormation($annee);
    }

    private function campagne(): Campagne
    {
        return new Campagne('Campagne 2026', $this->diplome, $this->createMock(StructureAnneeUniversitaire::class), $this->d('2026-09-01'));
    }

    private function d(string $date): \DateTimeImmutable
    {
        return new \DateTimeImmutable($date);
    }
}
