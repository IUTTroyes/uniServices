<?php

namespace UnifolioBundle\Tests\Service;

use App\Entity\Apc\ApcApprentissageCritique;
use App\Entity\Apc\ApcCompetence;
use App\Entity\Apc\ApcNiveau;
use App\Entity\Apc\ApcParcours;
use App\Entity\Apc\ApcReferentiel;
use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Structure\StructureAnnee;
use App\Entity\Structure\StructureDiplome;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use UnifolioBundle\Service\Perimetre\UnifolioPerimetre;
use UnifolioBundle\Service\Referentiel\ReferentielAnnee;

class ReferentielAnneeTest extends TestCase
{
    private ApcCompetence $competence;
    private ApcNiveau $niveauCommun;
    private ApcNiveau $niveauParcoursA;
    private ApcNiveau $niveauParcoursB;
    private ApcNiveau $niveauAutreAnnee;
    private ApcApprentissageCritique $ac;
    private ApcParcours $parcoursA;

    protected function setUp(): void
    {
        $this->competence = $this->entite(ApcCompetence::class, 1, ['getLibelle' => 'Comprendre', 'getNomCourt' => 'C1', 'getCouleur' => '#f00']);
        $this->parcoursA = $this->entite(ApcParcours::class, 10);
        $parcoursB = $this->entite(ApcParcours::class, 11);

        $this->ac = $this->entite(ApcApprentissageCritique::class, 100, ['getCode' => 'AC11.01', 'getLibelle' => 'Identifier']);
        $this->niveauCommun = $this->niveau(20, 1, [], [$this->ac]);
        $this->niveauParcoursA = $this->niveau(21, 2, [$this->parcoursA]);
        $this->niveauParcoursB = $this->niveau(22, 3, [$parcoursB]);
        $this->niveauAutreAnnee = $this->niveau(23, 4);
        $this->ac->method('getNiveau')->willReturn($this->niveauCommun);
    }

    public function testNiveauxFiltresParAnneeEtParcours(): void
    {
        $service = $this->service($this->parcoursA);
        $ids = array_map(static fn (ApcNiveau $n) => $n->getId(), $service->niveaux($this->createMock(EtudiantScolarite::class)));

        $this->assertSame([20, 21], $ids);
    }

    public function testSansParcoursTousLesNiveauxDeLAnnee(): void
    {
        $ids = array_map(static fn (ApcNiveau $n) => $n->getId(), $this->service(null)->niveaux($this->createMock(EtudiantScolarite::class)));

        $this->assertSame([20, 21, 22], $ids);
    }

    public function testContient(): void
    {
        $service = $this->service($this->parcoursA);
        $scolarite = $this->createMock(EtudiantScolarite::class);

        $this->assertTrue($service->contient($scolarite, $this->competence));
        $this->assertTrue($service->contient($scolarite, $this->niveauParcoursA));
        $this->assertTrue($service->contient($scolarite, $this->ac));
        $this->assertFalse($service->contient($scolarite, $this->niveauParcoursB), 'Niveau d\'un autre parcours');
        $this->assertFalse($service->contient($scolarite, $this->niveauAutreAnnee), 'Niveau d\'une autre année');
    }

    public function testArbre(): void
    {
        $arbre = $this->service($this->parcoursA)->arbre($this->createMock(EtudiantScolarite::class));

        $this->assertCount(1, $arbre);
        $this->assertSame('C1', $arbre[0]['nomCourt']);
        $this->assertSame([20, 21], array_column($arbre[0]['niveaux'], 'id'));
        $this->assertSame('AC11.01', $arbre[0]['niveaux'][0]['apprentissagesCritiques'][0]['code']);
    }

    public function testSansAnneeDeFormationRienNEstAccessible(): void
    {
        $perimetre = $this->createMock(UnifolioPerimetre::class);
        $perimetre->method('getAnneeFormation')->willReturn(null);

        $this->assertSame([], (new ReferentielAnnee($perimetre))->niveaux($this->createMock(EtudiantScolarite::class)));
    }

    public function testRepliSurLeReferentielDuDiplomeEtLeRangDAnnee(): void
    {
        // BUT2 : niveau 2 (ordreAnnee 2), niveau 1 d'une compétence qui commence en BUT2 (ordreAnnee 2),
        // niveau 1 sans ordreAnnee (0) → rang = ordre = 1, exclu.
        $n2 = $this->niveau(30, 2, [], [], 2);
        $n1Tardif = $this->niveau(31, 1, [], [], 2);
        $n1 = $this->niveau(32, 1, [], [], 0);
        $n3 = $this->niveau(33, 3, [], [], 3);
        $competence = $this->createMock(ApcCompetence::class);
        $competence->method('getNiveaux')->willReturn(new ArrayCollection([$n1, $n2, $n3, $n1Tardif]));
        $referentiel = $this->createMock(ApcReferentiel::class);
        $referentiel->method('getCompetences')->willReturn(new ArrayCollection([$competence]));

        $annee = $this->createMock(StructureAnnee::class);
        $annee->method('getNiveaux')->willReturn(new ArrayCollection());
        $annee->method('getOrdre')->willReturn(2);
        $diplome = $this->createMock(StructureDiplome::class);
        $diplome->method('getReferentiel')->willReturn($referentiel);
        $diplome->method('getParcours')->willReturn(null);

        $perimetre = $this->createMock(UnifolioPerimetre::class);
        $perimetre->method('getAnneeFormation')->willReturn($annee);
        $perimetre->method('getDiplome')->willReturn($diplome);

        $ids = array_map(static fn (ApcNiveau $n) => $n->getId(), (new ReferentielAnnee($perimetre))->niveaux($this->createMock(EtudiantScolarite::class)));
        sort($ids);

        $this->assertSame([30, 31], $ids);
    }

    private function service(?ApcParcours $parcours): ReferentielAnnee
    {
        $annee = $this->createMock(StructureAnnee::class);
        $annee->method('getNiveaux')->willReturn(new ArrayCollection([$this->niveauParcoursB, $this->niveauParcoursA, $this->niveauCommun]));

        $diplome = $this->createMock(StructureDiplome::class);
        $diplome->method('getParcours')->willReturn($parcours);

        $perimetre = $this->createMock(UnifolioPerimetre::class);
        $perimetre->method('getAnneeFormation')->willReturn($annee);
        $perimetre->method('getDiplome')->willReturn($diplome);

        return new ReferentielAnnee($perimetre);
    }

    /** @param ApcParcours[] $parcours @param ApcApprentissageCritique[] $acs */
    private function niveau(int $id, int $ordre, array $parcours = [], array $acs = [], ?int $ordreAnnee = null): ApcNiveau
    {
        return $this->entite(ApcNiveau::class, $id, [
            'getOrdre' => $ordre,
            'getOrdreAnnee' => $ordreAnnee,
            'getLibelle' => "Niveau $ordre",
            'getCompetence' => $this->competence,
            'getParcours' => new ArrayCollection($parcours),
            'getApprentissageCritique' => new ArrayCollection($acs),
        ]);
    }

    /**
     * @template T of object
     *
     * @param class-string<T>       $classe
     * @param array<string, mixed> $retours
     *
     * @return T&\PHPUnit\Framework\MockObject\MockObject
     */
    private function entite(string $classe, int $id, array $retours = []): object
    {
        $mock = $this->createMock($classe);
        $mock->method('getId')->willReturn($id);
        foreach ($retours as $methode => $valeur) {
            $mock->method($methode)->willReturn($valeur);
        }

        return $mock;
    }
}
