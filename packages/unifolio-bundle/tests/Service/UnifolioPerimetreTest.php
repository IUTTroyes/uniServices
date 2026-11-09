<?php

namespace UnifolioBundle\Tests\Service;

use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Etudiant\EtudiantScolariteSemestre;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Structure\StructureSemestre;
use App\Entity\Structure\StructureTypeDiplome;
use App\Entity\Users\Etudiant;
use App\Repository\EtudiantScolariteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use UnifolioBundle\Service\Perimetre\UnifolioPerimetre;

class UnifolioPerimetreTest extends TestCase
{
    public function testEtudiantDeBUTEstEligible(): void
    {
        $perimetre = $this->perimetre($this->scolarite(apc: true));

        $this->assertTrue($perimetre->isEtudiantEligible($this->createMock(Etudiant::class)));
    }

    public function testEtudiantHorsAPCNEstPasEligible(): void
    {
        $perimetre = $this->perimetre($this->scolarite(apc: false));

        $this->assertFalse($perimetre->isEtudiantEligible($this->createMock(Etudiant::class)));
    }

    public function testEtudiantSansScolariteActiveNEstPasEligible(): void
    {
        $perimetre = $this->perimetre(null);

        $this->assertFalse($perimetre->isEtudiantEligible($this->createMock(Etudiant::class)));
    }

    public function testScolariteSansSemestreNEstPasEligible(): void
    {
        $scolarite = $this->createMock(EtudiantScolarite::class);
        $scolarite->method('getScolariteSemestre')->willReturn(new ArrayCollection());

        $this->assertFalse($this->perimetre($scolarite)->isScolariteEligible($scolarite));
    }

    private function perimetre(?EtudiantScolarite $scolarite): UnifolioPerimetre
    {
        $repository = $this->createMock(EtudiantScolariteRepository::class);
        $repository->method('findActiveByEtudiant')->willReturn($scolarite);

        return new UnifolioPerimetre($repository);
    }

    private function scolarite(bool $apc): EtudiantScolarite
    {
        $type = $this->createMock(StructureTypeDiplome::class);
        $type->method('isApc')->willReturn($apc);

        $diplome = $this->createMock(StructureDiplome::class);
        $diplome->method('getTypeDiplome')->willReturn($type);

        $semestre = $this->createMock(StructureSemestre::class);
        $semestre->method('getDiplome')->willReturn($diplome);

        $scolariteSemestre = $this->createMock(EtudiantScolariteSemestre::class);
        $scolariteSemestre->method('getSemestre')->willReturn($semestre);

        $scolarite = $this->createMock(EtudiantScolarite::class);
        $scolarite->method('getScolariteSemestre')->willReturn(new ArrayCollection([$scolariteSemestre]));

        return $scolarite;
    }
}
