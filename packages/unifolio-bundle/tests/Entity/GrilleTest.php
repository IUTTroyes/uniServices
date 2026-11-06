<?php

namespace UnifolioBundle\Tests\Entity;

use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureDepartement;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Structure\StructureTypeDiplome;
use PHPUnit\Framework\TestCase;
use UnifolioBundle\Entity\Grille;
use UnifolioBundle\Entity\ParametrageFormation;
use UnifolioBundle\Enum\GranulariteEnum;
use UnifolioBundle\Enum\TypeCritereEnum;
use UnifolioBundle\Service\Grille\GrillesParDefaut;

class GrilleTest extends TestCase
{
    private StructureTypeDiplome $but;
    private StructureDepartement $mmi;
    private StructureDepartement $gea;

    protected function setUp(): void
    {
        $this->but = $this->type(1, true);
        $this->mmi = $this->departement(10);
        $this->gea = $this->departement(11);
    }

    public function testReserveeAuxDiplomesApc(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Grille('LP', $this->type(2, false), GranulariteEnum::AC);
    }

    public function testPorteeEtSpecificite(): void
    {
        $nationale = new Grille('N', $this->but, GranulariteEnum::AC);
        $dept = new Grille('D', $this->but, GranulariteEnum::AC, $this->mmi);
        $formation = new Grille('F', $this->but, GranulariteEnum::AC, null, $this->diplome(100, $this->but, $this->mmi));

        $this->assertSame([Grille::PORTEE_TYPE_DIPLOME, 0], [$nationale->getPortee(), $nationale->getSpecificite()]);
        $this->assertSame([Grille::PORTEE_DEPARTEMENT, 1], [$dept->getPortee(), $dept->getSpecificite()]);
        $this->assertSame([Grille::PORTEE_DIPLOME, 2], [$formation->getPortee(), $formation->getSpecificite()]);
        $this->assertSame($this->mmi, $formation->getDepartement(), 'Le département est déduit du diplôme');
    }

    public function testEstApplicableA(): void
    {
        $diplomeMmi = $this->diplome(100, $this->but, $this->mmi);
        $diplomeGea = $this->diplome(200, $this->but, $this->gea);

        $this->assertTrue((new Grille('N', $this->but, GranulariteEnum::AC))->estApplicableA($diplomeGea));
        $this->assertTrue((new Grille('D', $this->but, GranulariteEnum::AC, $this->mmi))->estApplicableA($diplomeMmi));
        $this->assertFalse((new Grille('D', $this->but, GranulariteEnum::AC, $this->mmi))->estApplicableA($diplomeGea));
        $this->assertFalse((new Grille('F', $this->but, GranulariteEnum::AC, null, $diplomeMmi))->estApplicableA($this->diplome(101, $this->but, $this->mmi)));
    }

    public function testCycleDeVersions(): void
    {
        $grille = new Grille('G', $this->but, GranulariteEnum::NIVEAU);
        $v1 = $grille->creerBrouillon();
        $this->assertSame(1, $v1->getNumero());
        $this->assertCount(4, $v1->getEchelons(), 'Échelle par défaut');
        $this->assertSame($v1, $grille->creerBrouillon(), 'Un seul brouillon à la fois');

        $this->assertSame(['Ajoutez au moins un critère.'], $v1->getErreursPublication());
        $v1->ajouterCritere('Maîtrise', TypeCritereEnum::ECHELLE)->setAide('aide')->setPoids(2);
        $v1->publier(new \DateTimeImmutable('2026-09-01'));
        $this->assertSame($v1, $grille->getVersionPubliee());
        $this->assertNull($grille->getBrouillon());

        $v2 = $grille->creerBrouillon();
        $this->assertSame(2, $v2->getNumero());
        $this->assertSame('Maîtrise', $v2->getCriteres()->first()->getLibelle(), 'Contenu recopié de la version publiée');
        $this->assertSame(2.0, $v2->getCriteres()->first()->getPoids());
        $this->assertSame($v1, $grille->getVersionPubliee(), 'La v1 reste la version en vigueur');

        $grille->supprimerBrouillon();
        $this->assertNull($grille->getBrouillon());
    }

    public function testVersionPublieeFigee(): void
    {
        $grille = new Grille('G', $this->but, GranulariteEnum::AC);
        $v1 = $grille->creerBrouillon();
        $v1->ajouterCritere('C', TypeCritereEnum::ECHELLE);
        $v1->publier();

        $this->expectException(\DomainException::class);
        $v1->ajouterCritere('Ajout tardif', TypeCritereEnum::TEXTE);
    }

    public function testErreursDePublication(): void
    {
        $v = (new Grille('G', $this->but, GranulariteEnum::AC))->creerBrouillon();
        $v->viderContenu();
        $v->ajouterCritere('Échelle', TypeCritereEnum::ECHELLE)->setPoids(0);
        $v->ajouterCritere('Note', TypeCritereEnum::NOTE);
        $v->ajouterEchelon('Seul', 1);

        $erreurs = implode(' | ', $v->getErreursPublication());
        $this->assertStringContainsString('note maximale', $erreurs);
        $this->assertStringContainsString('au moins deux niveaux', $erreurs);

        $v->ajouterEchelon('Doublon', 1);
        $this->assertStringContainsString('valeur différente', implode(' ', $v->getErreursPublication()));
    }

    public function testAppreciationLibreSansPoids(): void
    {
        $v = (new Grille('G', $this->but, GranulariteEnum::AC))->creerBrouillon();
        $critere = $v->ajouterCritere('Appréciation', TypeCritereEnum::TEXTE)->setPoids(5);

        $this->assertSame(0.0, $critere->getPoids());
        $this->assertSame(['Au moins un critère évalué (hors appréciation libre) doit avoir un poids positif.'], $v->getErreursPublication());
    }

    public function testCouleurEchelonValidee(): void
    {
        $v = (new Grille('G', $this->but, GranulariteEnum::AC))->creerBrouillon();
        $this->expectException(\InvalidArgumentException::class);
        $v->ajouterEchelon('X', 5, 'red;background:url(x)');
    }

    public function testGrillesParDefautPublieesEtValides(): void
    {
        foreach (GranulariteEnum::cases() as $granularite) {
            $grille = GrillesParDefaut::construire($this->but, $granularite);
            $this->assertSame($granularite, $grille->getGranularite());
            $this->assertNotNull($grille->getVersionPubliee());
            $this->assertSame(Grille::PORTEE_TYPE_DIPLOME, $grille->getPortee());
            $this->assertCount(4, $grille->getVersionPubliee()->getCriteres());
        }
    }

    public function testParametrageVerifieLaGrille(): void
    {
        $diplome = $this->diplome(100, $this->but, $this->mmi);
        $parametrage = new ParametrageFormation($diplome, $this->createMock(StructureAnneeUniversitaire::class), GranulariteEnum::AC);

        $parametrage->setGrille(new Grille('AC', $this->but, GranulariteEnum::AC, $this->mmi));
        $this->assertNotNull($parametrage->getGrille());

        $parametrage->setGranularite(GranulariteEnum::NIVEAU);
        $this->assertNull($parametrage->getGrille(), 'Changer de granularité retire une grille incompatible');

        try {
            $parametrage->setGrille(new Grille('AC', $this->but, GranulariteEnum::AC));
            $this->fail('Granularité incohérente acceptée');
        } catch (\InvalidArgumentException) {
            $this->addToAssertionCount(1);
        }

        $this->expectException(\InvalidArgumentException::class);
        $parametrage->setGrille(new Grille('Autre dept', $this->but, GranulariteEnum::NIVEAU, $this->gea));
    }

    private function type(int $id, bool $apc): StructureTypeDiplome
    {
        $t = $this->createMock(StructureTypeDiplome::class);
        $t->method('getId')->willReturn($id);
        $t->method('isApc')->willReturn($apc);
        $t->method('getSigle')->willReturn('BUT');

        return $t;
    }

    private function departement(int $id): StructureDepartement
    {
        $d = $this->createMock(StructureDepartement::class);
        $d->method('getId')->willReturn($id);

        return $d;
    }

    private function diplome(int $id, StructureTypeDiplome $type, StructureDepartement $departement): StructureDiplome
    {
        $d = $this->createMock(StructureDiplome::class);
        $d->method('getId')->willReturn($id);
        $d->method('getTypeDiplome')->willReturn($type);
        $d->method('getDepartement')->willReturn($departement);

        return $d;
    }
}
