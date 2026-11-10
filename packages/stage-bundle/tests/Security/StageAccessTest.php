<?php

namespace StageBundle\Tests\Security;

use App\Entity\Structure\StructureDepartement;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Security\DepartmentPermissionChecker;
use App\Security\UserEffectivePermissionService;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use StageBundle\Entity\Stages\StageEtudiant;
use StageBundle\Entity\Stages\StagePeriode;
use StageBundle\Security\StageAccess;

/** Le rôle de responsable des stages ne vaut que dans les départements où il est attribué. */
class StageAccessTest extends TestCase
{
    private StructureDepartement $deptA;
    private StructureDepartement $deptB;

    protected function setUp(): void
    {
        $this->deptA = new StructureDepartement();
        $this->deptB = new StructureDepartement();
    }

    private function access(bool $superAdmin = false): StageAccess
    {
        $permissions = $this->createMock(UserEffectivePermissionService::class);
        $permissions->method('isSuperAdmin')->willReturn($superAdmin);
        $permissions->method('hasPermission')->willReturn(true); // gestionnaire dans son département actif
        $permissions->method('hasAnyPermissionInDepartment')->willReturnCallback(fn ($user, array $roles, $d) => $d === $this->deptA);
        $departements = $this->createMock(DepartmentPermissionChecker::class);
        // Gestionnaire uniquement dans le département A
        $departements->method('checkPermission')->willReturnCallback(
            fn ($user, StructureDepartement $d, string $role) => $d === $this->deptA && $role === 'ROLE_STAGE_MANAGER'
        );

        return new StageAccess($permissions, $departements, new \StageBundle\Security\StageVoter($permissions));
    }

    private function periode(?StructureDepartement $departement): StagePeriode
    {
        $periode = $this->createMock(StagePeriode::class);
        $periode->method('getDepartement')->willReturn($departement);
        $periode->method('getCoResponsables')->willReturn(new ArrayCollection());

        return $periode;
    }

    private function dossier(?StructureDepartement $departement): StageEtudiant
    {
        return (new StageEtudiant())->setEtudiant(new Etudiant())->setStagePeriode($this->periode($departement));
    }

    public function testGereLesDossiersDeSonDepartement(): void
    {
        $responsable = new Personnel();
        self::assertTrue($this->access()->peutGerer($responsable, $this->dossier($this->deptA)));
        self::assertTrue($this->access()->peutVoir($responsable, $this->dossier($this->deptA)));
    }

    public function testNeVoitPasLesDossiersDUnAutreDepartement(): void
    {
        $responsable = new Personnel();
        self::assertFalse($this->access()->peutGerer($responsable, $this->dossier($this->deptB)));
        self::assertFalse($this->access()->peutVoir($responsable, $this->dossier($this->deptB)));
        self::assertFalse($this->access()->estGestionnaire($responsable, $this->periode($this->deptB)));
    }

    public function testUnePeriodeSansDepartementEstReserveeAuSuperAdmin(): void
    {
        self::assertFalse($this->access()->estGestionnaire(new Personnel(), $this->periode(null)));
        self::assertTrue($this->access(superAdmin: true)->estGestionnaire(new Personnel(), $this->periode(null)));
    }

    public function testLeTuteurDUnAutreDepartementSansDroitsNeVoitPasSonStagiaire(): void
    {
        $tuteur = new Personnel();
        $dossier = $this->dossier($this->deptB)->setTuteurUniversitaire($tuteur);
        self::assertFalse($this->access()->peutVoir($tuteur, $dossier));
        self::assertFalse($this->access()->peutGerer($tuteur, $dossier));
    }

    public function testUnEtudiantNEstJamaisGestionnaire(): void
    {
        self::assertFalse($this->access()->estGestionnaire(new Etudiant(), $this->periode($this->deptA)));
    }
}
