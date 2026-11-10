<?php

namespace UnifolioBundle\Tests\Entity;

use App\Entity\Apc\ApcCompetence;
use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Structure\StructureTypeDiplome;
use App\Entity\Users\Etudiant;
use PHPUnit\Framework\TestCase;
use UnifolioBundle\Entity\AutoEvaluation;
use UnifolioBundle\Entity\Grille;
use UnifolioBundle\Entity\GrilleVersion;
use UnifolioBundle\Entity\Trace;
use UnifolioBundle\Entity\TraceRattachement;
use UnifolioBundle\Enum\GranulariteEnum;
use UnifolioBundle\Enum\StatutAutoEvaluationEnum;
use UnifolioBundle\Enum\TypeCritereEnum;

class AutoEvaluationTest extends TestCase
{
    private GrilleVersion $version;
    /** @var array<string, int> identifiants simulés des critères et échelons */
    private array $ids = [];

    protected function setUp(): void
    {
        $type = $this->createMock(StructureTypeDiplome::class);
        $type->method('isApc')->willReturn(true);
        $grille = new Grille('G', $type, GranulariteEnum::COMPETENCE);
        $this->version = $grille->creerBrouillon(); // échelle par défaut : valeurs 0..3
        $this->version->ajouterCritere('Maîtrise', TypeCritereEnum::ECHELLE)->setPoids(2);
        $this->version->ajouterCritere('Preuve authentique', TypeCritereEnum::BOOLEEN)->setPoids(1);
        $this->version->ajouterCritere('Note', TypeCritereEnum::NOTE)->setNoteMax(20)->setPoids(1)->setObligatoire(false);
        $this->version->ajouterCritere('Commentaire', TypeCritereEnum::TEXTE)->setObligatoire(false);
        $this->version->ajouterCritere('Réservé enseignant', TypeCritereEnum::ECHELLE)->setUtiliseAutoEval(false);

        // Les entités ne sont pas persistées : on leur donne des identifiants pour simuler la saisie.
        $n = 1;
        foreach ([...$this->version->getCriteres(), ...$this->version->getEchelons()] as $objet) {
            (new \ReflectionProperty($objet, 'id'))->setValue($objet, $n);
            $this->ids[$objet->getLibelle()] = $n++;
        }
        $this->version->publier();
    }

    public function testCriteresDAutoEvaluationSeulement(): void
    {
        $auto = $this->bilan();
        $libelles = array_map(static fn ($c) => $c->getLibelle(), $auto->getCriteres());

        $this->assertNotContains('Réservé enseignant', $libelles);
        $this->assertCount(4, $libelles);
    }

    public function testScorePondere(): void
    {
        $auto = $this->bilan();
        $auto->repondre([
            $this->ids['Maîtrise'] => ['echelon' => $this->ids['Acquis']],      // 2/3 → 0,667 × 2
            $this->ids['Preuve authentique'] => ['booleen' => true],            // 1 × 1
            $this->ids['Note'] => ['valeur' => 10],                              // 0,5 × 1
            $this->ids['Commentaire'] => ['texte' => 'Bien'],                    // non quantifiable
        ]);

        $this->assertCount(4, $auto->getReponses());
        $this->assertEqualsWithDelta((2 * 2 / 3 + 1 + 0.5) / 4, $auto->getScore(), 0.0001);
    }

    public function testRefuseUnCritereHorsAutoEvaluation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->bilan()->repondre([$this->ids['Réservé enseignant'] => ['echelon' => $this->ids['Acquis']]]);
    }

    public function testRefuseUneNoteHorsBornes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->bilan()->repondre([$this->ids['Note'] => ['valeur' => 25]]);
    }

    public function testRefuseUnEchelonInconnu(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->bilan()->repondre([$this->ids['Maîtrise'] => ['echelon' => 9999]]);
    }

    public function testSoumissionExigeLesCriteresObligatoires(): void
    {
        $auto = $this->bilan();
        $auto->repondre([$this->ids['Maîtrise'] => ['echelon' => $this->ids['Maîtrisé']]]);
        $this->assertStringContainsString('Preuve authentique', implode(' ', $auto->getErreursSoumission()));

        $auto->repondre([
            $this->ids['Maîtrise'] => ['echelon' => $this->ids['Maîtrisé']],
            $this->ids['Preuve authentique'] => ['booleen' => false],
        ]);
        $auto->soumettre(new \DateTimeImmutable('2026-11-06'));

        $this->assertSame(StatutAutoEvaluationEnum::SOUMISE, $auto->getStatut());
        $this->assertFalse($auto->isModifiable());
    }

    public function testSoumiseNonModifiableJusquARepriseEnSaisie(): void
    {
        $auto = $this->bilan();
        $auto->repondre([
            $this->ids['Maîtrise'] => ['echelon' => $this->ids['Acquis']],
            $this->ids['Preuve authentique'] => ['booleen' => true],
        ]);
        $auto->soumettre();

        try {
            $auto->setReflexion('Après coup');
            $this->fail('Modification acceptée après soumission');
        } catch (\DomainException) {
            $this->addToAssertionCount(1);
        }

        $auto->reprendre();
        $auto->setReflexion('Après coup');
        $this->assertSame('Après coup', $auto->getReflexion());
    }

    public function testReponsesVidesIgnorees(): void
    {
        $auto = $this->bilan();
        $auto->repondre([
            $this->ids['Maîtrise'] => ['echelon' => null],
            $this->ids['Commentaire'] => ['texte' => '   '],
        ]);

        $this->assertCount(0, $auto->getReponses());
        $this->assertNull($auto->getScore());
    }

    public function testVersionNonPublieeRefusee(): void
    {
        $type = $this->createMock(StructureTypeDiplome::class);
        $type->method('isApc')->willReturn(true);
        $brouillon = (new Grille('G', $type, GranulariteEnum::AC))->creerBrouillon();

        $this->expectException(\InvalidArgumentException::class);
        AutoEvaluation::bilan(new Etudiant(), $this->createMock(EtudiantScolarite::class), $brouillon, $this->createMock(ApcCompetence::class));
    }

    public function testAutoEvaluationDUneTrace(): void
    {
        $etudiant = new Etudiant();
        $trace = new Trace($etudiant, 'Projet');
        $competence = $this->createMock(ApcCompetence::class);
        $competence->method('getId')->willReturn(5);
        $rattachement = TraceRattachement::versCompetence($trace, $competence);

        $auto = AutoEvaluation::pourRattachement($rattachement, $this->createMock(EtudiantScolarite::class), $this->version);

        $this->assertFalse($auto->isBilan());
        $this->assertSame($etudiant, $auto->getEtudiant());
        $this->assertSame(GranulariteEnum::COMPETENCE, $auto->getGranularite());
        $this->assertSame($competence, $auto->getCompetenceParente());
    }

    private function bilan(): AutoEvaluation
    {
        return AutoEvaluation::bilan(new Etudiant(), $this->createMock(EtudiantScolarite::class), $this->version, $this->createMock(ApcCompetence::class));
    }
}
