<?php

namespace UnifolioBundle\Tests\Service;

use App\Entity\Apc\ApcApprentissageCritique;
use App\Entity\Apc\ApcCompetence;
use App\Entity\Apc\ApcNiveau;
use App\Entity\Users\Etudiant;
use PHPUnit\Framework\TestCase;
use UnifolioBundle\Entity\Trace;
use UnifolioBundle\Entity\TraceRattachement;
use UnifolioBundle\Enum\GranulariteEnum;
use UnifolioBundle\Service\Evaluation\CibleLot;

class CibleLotTest extends TestCase
{
    public function testPointDeTrace(): void
    {
        $competence = $this->createMock(ApcCompetence::class);
        $competence->method('getId')->willReturn(49);
        $niveau = $this->createMock(ApcNiveau::class);
        $niveau->method('getCompetence')->willReturn($competence);
        $ac = $this->createMock(ApcApprentissageCritique::class);
        $ac->method('getId')->willReturn(479);
        $ac->method('getCode')->willReturn('AC21.01');
        $ac->method('getLibelle')->willReturn('Analyser');
        $ac->method('getNiveau')->willReturn($niveau);

        $rattachement = TraceRattachement::versApprentissageCritique(new Trace(new Etudiant(), 'Projet'), $ac);
        (new \ReflectionProperty($rattachement, 'id'))->setValue($rattachement, 5);
        $cible = CibleLot::pourRattachement($rattachement);

        $this->assertSame('r:5', $cible->cle);
        $this->assertSame(GranulariteEnum::AC, $cible->granularite);
        $this->assertSame('AC21.01 Analyser', $cible->libelle());
        $this->assertSame($competence, $cible->competence());
    }

    public function testElementDuReferentielPourUnBilan(): void
    {
        $competence = $this->createMock(ApcCompetence::class);
        $competence->method('getId')->willReturn(49);
        $competence->method('getLibelle')->willReturn('Comprendre');

        $cible = CibleLot::pourElement(GranulariteEnum::COMPETENCE, $competence);

        $this->assertSame('competence:49', $cible->cle);
        $this->assertNull($cible->rattachement);
        $this->assertSame('Comprendre', $cible->libelle());
        $this->assertSame($competence, $cible->competence());
    }

    public function testClesDistinctesEntreTraceEtBilan(): void
    {
        $niveau = $this->createMock(ApcNiveau::class);
        $niveau->method('getId')->willReturn(5);

        $this->assertSame('niveau:5', CibleLot::pourElement(GranulariteEnum::NIVEAU, $niveau)->cle);
    }
}
