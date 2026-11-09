<?php

namespace App\Tests\Security;

use App\Entity\Structure\StructureDepartement;
use App\Entity\Users\Personnel;
use App\Security\DepartmentPermissionChecker;
use App\Security\StructureVoter;
use App\Security\UserEffectivePermissionService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/** Modifier ou supprimer un département : rôle vérifié dans CE département, pas globalement. */
final class StructureVoterDepartementTest extends TestCase
{
    private function voter(StructureDepartement $ouChef, bool $superAdmin = false): StructureVoter
    {
        $permissions = $this->createMock(UserEffectivePermissionService::class);
        $permissions->method('isSuperAdmin')->willReturn($superAdmin);
        $departements = $this->createMock(DepartmentPermissionChecker::class);
        $departements->method('checkPermission')->willReturnCallback(
            fn ($user, StructureDepartement $d, string $role) => $d === $ouChef && $role === 'ROLE_CHEF_DEPT'
        );

        return new StructureVoter($permissions, $departements);
    }

    private static function departement(?int $id): StructureDepartement
    {
        $d = new StructureDepartement();
        if ($id !== null) {
            (new \ReflectionProperty($d, 'id'))->setValue($d, $id);
        }

        return $d;
    }

    private static function vote(StructureVoter $voter, string $attribut, StructureDepartement $d): int
    {
        $user = new Personnel();
        $token = new UsernamePasswordToken($user, 'main', ['ROLE_USER']);

        return $voter->vote($token, $d, [$attribut]);
    }

    public function testChefDeDepartementLimiteASonDepartement(): void
    {
        $a = self::departement(1);
        $b = self::departement(2);
        $voter = $this->voter($a);

        self::assertSame(VoterInterface::ACCESS_GRANTED, self::vote($voter, StructureVoter::CAN_EDIT_DEPARTEMENT, $a));
        self::assertSame(VoterInterface::ACCESS_DENIED, self::vote($voter, StructureVoter::CAN_EDIT_DEPARTEMENT, $b));
        self::assertSame(VoterInterface::ACCESS_DENIED, self::vote($voter, StructureVoter::CAN_DELETE_DEPARTEMENT, $a), 'Supprimer exige ROLE_ADMIN.');
        self::assertSame(VoterInterface::ACCESS_DENIED, self::vote($voter, StructureVoter::CAN_EDIT_DEPARTEMENT, self::departement(null)), 'Créer : super administrateur seulement.');
    }

    public function testSuperAdministrateur(): void
    {
        $voter = $this->voter(self::departement(1), true);
        self::assertSame(VoterInterface::ACCESS_GRANTED, self::vote($voter, StructureVoter::CAN_EDIT_DEPARTEMENT, self::departement(null)));
        self::assertSame(VoterInterface::ACCESS_GRANTED, self::vote($voter, StructureVoter::CAN_DELETE_DEPARTEMENT, self::departement(2)));
    }
}
