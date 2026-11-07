<?php

namespace UnifolioBundle\Tests\Entity;

use App\Entity\Apc\ApcCompetence;
use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Structure\StructureTypeDiplome;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use PHPUnit\Framework\TestCase;
use UnifolioBundle\Entity\Campagne;
use UnifolioBundle\Entity\Evaluation;
use UnifolioBundle\Entity\Grille;
use UnifolioBundle\Entity\GrilleVersion;
use UnifolioBundle\Entity\Trace;
use UnifolioBundle\Entity\TraceRattachement;
use UnifolioBundle\Enum\GranulariteEnum;
use UnifolioBundle\Enum\StatutEvaluationEnum;
use UnifolioBundle\Enum\TypeCritereEnum;

class EvaluationTest extends TestCase
{
    private GrilleVersion $version;
    /** @var array<string, int> */
    private array $ids = [];
    private Campagne $campagne;
    private Personnel $evaluateur;

    protected function setUp(): void
    {
        $type = $this->createMock(StructureTypeDiplome::class);
        $type->method('isApc')->willReturn(true);
        $this->version = (new Grille('G', $type, GranulariteEnum::COMPETENCE))->creerBrouillon();
        $this->version->ajouterCritere('Maîtrise', TypeCritereEnum::ECHELLE)->setPoids(2);
        $this->version->ajouterCritere('Remarque', TypeCritereEnum::TEXTE)->setObligatoire(false);
        $n = 1;
        foreach ([...$this->version->getCriteres(), ...$this->version->getEchelons()] as $objet) {
            (new \ReflectionProperty($objet, 'id'))->setValue($objet, $n);
            $this->ids[$objet->getLibelle()] = $n++;
        }
        $this->version->publier();

        $diplome = $this->createMock(StructureDiplome::class);
        $diplome->method('getTypeDiplome')->willReturn($type);
        $this->campagne = new Campagne('C', $diplome, $this->createMock(StructureAnneeUniversitaire::class), new \DateTimeImmutable('2026-09-01'));
        $this->evaluateur = $this->createMock(Personnel::class);
        $this->evaluateur->method('getId')->willReturn(7);
    }

    public function testCycleComplet(): void
    {
        $e = $this->evaluationDeTrace();
        $this->assertSame(StatutEvaluationEnum::EN_COURS, $e->getStatut());
        $this->assertStringContainsString('Maîtrise', implode(' ', $e->getErreursRendu()));

        $e->renseigner([
            'appreciation' => 'Bon travail',
            'reponses' => [$this->ids['Maîtrise'] => ['echelon' => $this->ids['Acquis']], $this->ids['Remarque'] => ['texte' => 'RAS']],
        ]);
        $this->assertEqualsWithDelta(2 / 3, $e->getScore(), 0.0001);

        $e->rendre(true, new \DateTimeImmutable('2026-11-06'));
        $this->assertSame(StatutEvaluationEnum::RENDUE, $e->getStatut());
        $this->assertTrue($e->isVisibleEtudiant());
        $this->assertFalse($e->isModifiable());

        $e->rouvrir();
        $this->assertSame(StatutEvaluationEnum::EN_COURS, $e->getStatut());
        $this->assertFalse($e->isVisibleEtudiant(), 'Une évaluation rouverte n\'est plus communiquée');
        $this->assertNull($e->getRendueLe());
    }

    public function testRenduRefuseSiCritereObligatoireManquant(): void
    {
        $this->expectException(\DomainException::class);
        $this->evaluationDeTrace()->rendre();
    }

    public function testRenduFigeLEvaluation(): void
    {
        $e = $this->evaluationDeTrace();
        $e->renseigner(['reponses' => [$this->ids['Maîtrise'] => ['echelon' => $this->ids['Acquis']]]]);
        $e->rendre();

        $this->expectException(\DomainException::class);
        $e->renseigner(['appreciation' => 'Trop tard']);
    }

    public function testToutesLesColonnesDeLaGrilleSontProposeesALEvaluateur(): void
    {
        $this->assertCount(2, $this->evaluationDeTrace()->getCriteres());
    }

    public function testDemandeDeRevisionExigeUnMotif(): void
    {
        $e = $this->evaluationDeTrace();
        try {
            $e->demanderRevision('   ');
            $this->fail('Motif vide accepté');
        } catch (\DomainException) {
            $this->addToAssertionCount(1);
        }

        $e->demanderRevision('Précisez la démarche.', new \DateTimeImmutable('2026-11-06'));
        $this->assertSame(StatutEvaluationEnum::REVISION_DEMANDEE, $e->getStatut());
        $this->assertSame('Précisez la démarche.', $e->getAppreciation());
        $this->assertTrue($e->isVisibleEtudiant());
    }

    public function testPasDeRevisionSurUnBilan(): void
    {
        $bilan = Evaluation::bilan($this->campagne, $this->evaluateur, new Etudiant(), $this->createMock(EtudiantScolarite::class), $this->version, $this->createMock(ApcCompetence::class));

        $this->assertTrue($bilan->isBilan());
        $this->expectException(\DomainException::class);
        $bilan->demanderRevision('Non');
    }

    public function testEvaluationEnCoursNEstPasCommunicable(): void
    {
        $this->expectException(\DomainException::class);
        $this->evaluationDeTrace()->setVisibleEtudiant(true);
    }

    public function testReponseHorsGrilleRefusee(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->evaluationDeTrace()->renseigner(['reponses' => [9999 => ['echelon' => $this->ids['Acquis']]]]);
    }

    public function testEstDe(): void
    {
        $e = $this->evaluationDeTrace();
        $autre = $this->createMock(Personnel::class);
        $autre->method('getId')->willReturn(8);

        $this->assertTrue($e->estDe($this->evaluateur));
        $this->assertFalse($e->estDe($autre));
    }

    private function evaluationDeTrace(): Evaluation
    {
        $trace = new Trace(new Etudiant(), 'Projet');
        $competence = $this->createMock(ApcCompetence::class);
        $competence->method('getId')->willReturn(3);
        $rattachement = TraceRattachement::versCompetence($trace, $competence);

        return Evaluation::pourRattachement($this->campagne, $this->evaluateur, $rattachement, $this->createMock(EtudiantScolarite::class), $this->version);
    }
}
