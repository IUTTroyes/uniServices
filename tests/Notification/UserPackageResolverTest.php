<?php

namespace App\Tests\Notification;

use App\Entity\Users\Etudiant;
use App\Repository\Structure\StructureDepartementPersonnelRepository;
use App\Security\UserEffectivePermissionService;
use App\Security\UserPackageResolver;
use PHPUnit\Framework\TestCase;

class UserPackageResolverTest extends TestCase
{
    private function resolver(bool $superAdmin = false): UserPackageResolver
    {
        $permissions = $this->createMock(UserEffectivePermissionService::class);
        $permissions->method('isSuperAdmin')->willReturn($superAdmin);

        return new UserPackageResolver($this->createMock(StructureDepartementPersonnelRepository::class), $permissions);
    }

    private function etudiant(?array $applications): Etudiant
    {
        return (new Etudiant())->setApplications($applications);
    }

    public function testUnEtudiantSansApplicationsADIntranet(): void
    {
        self::assertSame(['intranet'], $this->resolver()->packages($this->etudiant(null)));
        self::assertSame(['intranet'], $this->resolver()->packages($this->etudiant([])));
    }

    public function testLesApplicationsDUnEtudiantSontNormalisees(): void
    {
        self::assertSame(['intranet', 'unifolio', 'stage'], $this->resolver()->packages($this->etudiant(['Intranet', 'UniFolio', 'stages', 'unifolio'])));
    }

    public function testUnPackageAbsentEstRefuse(): void
    {
        $r = $this->resolver();
        $etudiant = $this->etudiant(['intranet']);
        self::assertFalse($r->hasPackage($etudiant, 'unifolio'));
        self::assertTrue($r->hasPackage($this->etudiant(['intranet', 'unifolio']), 'unifolio'));
        self::assertTrue($r->hasPackage($this->etudiant(['intranet', 'stage']), 'stages'), 'les anciens noms sont reconnus');
        self::assertTrue($r->hasPackage($this->etudiant(['intranet', 'documents']), 'document'));
    }

    public function testLeCoeurEtAuthSontToujoursDisponibles(): void
    {
        $r = $this->resolver();
        self::assertTrue($r->hasPackage($this->etudiant(['intranet']), 'auth'));
        self::assertTrue($r->hasPackage($this->etudiant(['intranet']), 'core'));
    }

    public function testLeSuperAdministrateurAToutes(): void
    {
        self::assertTrue($this->resolver(true)->hasPackage($this->etudiant(['intranet']), 'unifolio'));
    }

    public function testNomsCanoniques(): void
    {
        self::assertSame('stage', UserPackageResolver::canonique('Stages'));
        self::assertSame('documents', UserPackageResolver::canonique('DOCUMENT'));
        self::assertSame('unifolio', UserPackageResolver::canonique('UniFolio'));
    }
}
