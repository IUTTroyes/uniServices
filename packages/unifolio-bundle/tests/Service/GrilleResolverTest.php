<?php

namespace UnifolioBundle\Tests\Service;

use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureDepartement;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Structure\StructureTypeDiplome;
use PHPUnit\Framework\TestCase;
use UnifolioBundle\Entity\Grille;
use UnifolioBundle\Entity\ParametrageFormation;
use UnifolioBundle\Enum\GranulariteEnum;
use UnifolioBundle\Enum\TypeCritereEnum;
use UnifolioBundle\Repository\GrilleRepository;
use UnifolioBundle\Repository\ParametrageFormationRepository;
use UnifolioBundle\Service\Grille\GrilleResolver;
use UnifolioBundle\Service\Perimetre\UnifolioPerimetre;

class GrilleResolverTest extends TestCase
{
    private StructureTypeDiplome $but;
    private StructureDepartement $dept;
    private StructureDiplome $diplome;
    private StructureAnneeUniversitaire $annee;

    protected function setUp(): void
    {
        $this->but = $this->createMock(StructureTypeDiplome::class);
        $this->but->method('getId')->willReturn(1);
        $this->but->method('isApc')->willReturn(true);
        $this->dept = $this->createMock(StructureDepartement::class);
        $this->dept->method('getId')->willReturn(10);
        $this->diplome = $this->createMock(StructureDiplome::class);
        $this->diplome->method('getId')->willReturn(100);
        $this->diplome->method('getTypeDiplome')->willReturn($this->but);
        $this->diplome->method('getDepartement')->willReturn($this->dept);
        $this->annee = $this->createMock(StructureAnneeUniversitaire::class);
    }

    public function testSansParametrageGranulariteLibre(): void
    {
        $this->assertNull($this->resolver(null, [])->granularite($this->diplome, $this->annee));
    }

    public function testGrilleChoisieDansLeParametrage(): void
    {
        $choisie = $this->publiee(new Grille('Choisie', $this->but, GranulariteEnum::AC, $this->dept));
        $parametrage = new ParametrageFormation($this->diplome, $this->annee, GranulariteEnum::AC);
        $parametrage->setGrille($choisie);

        $resolver = $this->resolver($parametrage, [$this->publiee(new Grille('Autre', $this->but, GranulariteEnum::AC, null, $this->diplome))]);

        $this->assertSame($choisie, $resolver->grille($this->diplome, $this->annee));
        $this->assertSame(GranulariteEnum::AC, $resolver->granularite($this->diplome, $this->annee));
    }

    public function testSinonLaPlusSpecifiquePubliee(): void
    {
        $brouillon = new Grille('Brouillon formation', $this->but, GranulariteEnum::AC, null, $this->diplome);
        $brouillon->creerBrouillon();
        $dept = $this->publiee(new Grille('Département', $this->but, GranulariteEnum::AC, $this->dept));

        // Le dépôt renvoie les grilles triées par spécificité : la grille de formation non publiée est ignorée.
        $resolver = $this->resolver(new ParametrageFormation($this->diplome, $this->annee, GranulariteEnum::AC), [$brouillon, $dept]);

        $this->assertSame($dept, $resolver->grille($this->diplome, $this->annee));
    }

    private function publiee(Grille $grille): Grille
    {
        $v = $grille->creerBrouillon();
        $v->ajouterCritere('C', TypeCritereEnum::ECHELLE);
        $v->publier();

        return $grille;
    }

    /** @param Grille[] $applicables */
    private function resolver(?ParametrageFormation $parametrage, array $applicables): GrilleResolver
    {
        $parametrages = $this->createMock(ParametrageFormationRepository::class);
        $parametrages->method('findApplicable')->willReturn($parametrage);
        $grilles = $this->createMock(GrilleRepository::class);
        $grilles->method('findApplicables')->willReturn($applicables);

        return new GrilleResolver($parametrages, $grilles, $this->createMock(UnifolioPerimetre::class));
    }
}
