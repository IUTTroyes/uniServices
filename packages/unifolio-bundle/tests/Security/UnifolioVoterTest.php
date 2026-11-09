<?php

namespace UnifolioBundle\Tests\Security;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Security\UserEffectivePermissionService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use UnifolioBundle\Security\UnifolioRoles;
use UnifolioBundle\Security\UnifolioVoter;
use UnifolioBundle\Service\Perimetre\UnifolioPerimetre;

class UnifolioVoterTest extends TestCase
{
    public function testIgnoreLesAttributsInconnus(): void
    {
        $voter = $this->voter(eligible: true, roles: []);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote($this->token(new Etudiant()), null, ['CAN_VIEW_STAGE']));
    }

    public function testEtudiantDeBUT(): void
    {
        $voter = $this->voter(eligible: true, roles: []);
        $token = $this->token($this->createMock(Etudiant::class));

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, null, [UnifolioVoter::ACCES]));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, null, [UnifolioVoter::ESPACE_ETUDIANT]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, null, [UnifolioVoter::CORRIGER]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, null, [UnifolioVoter::GERER]));
    }

    public function testEtudiantHorsBUTRefuse(): void
    {
        $voter = $this->voter(eligible: false, roles: []);
        $token = $this->token($this->createMock(Etudiant::class));

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, null, [UnifolioVoter::ACCES]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, null, [UnifolioVoter::ESPACE_ETUDIANT]));
    }

    public function testUtilisateurAnonymeRefuse(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter(true, [])->vote($token, null, [UnifolioVoter::ACCES]));
    }

    /** @return iterable<string, array{string[], string, bool}> */
    public static function personnels(): iterable
    {
        yield 'enseignant : accès' => [[UnifolioRoles::ENSEIGNANT], UnifolioVoter::ACCES, true];
        yield 'enseignant : corrige' => [[UnifolioRoles::ENSEIGNANT], UnifolioVoter::CORRIGER, true];
        yield 'enseignant : ne gère pas' => [[UnifolioRoles::ENSEIGNANT], UnifolioVoter::GERER, false];
        yield 'enseignant : ne modère pas' => [[UnifolioRoles::ENSEIGNANT], UnifolioVoter::MODERER, false];
        yield 'correcteur : corrige' => [[UnifolioRoles::CORRECTEUR], UnifolioVoter::CORRIGER, true];
        yield 'référent : gère' => [[UnifolioRoles::REFERENT], UnifolioVoter::GERER, true];
        yield 'référent : modère' => [[UnifolioRoles::REFERENT], UnifolioVoter::MODERER, true];
        yield 'publication : modère' => [[UnifolioRoles::PUBLICATION], UnifolioVoter::MODERER, true];
        yield 'publication : ne corrige pas' => [[UnifolioRoles::PUBLICATION], UnifolioVoter::CORRIGER, false];
        yield 'biatss : pas d\'accès' => [['ROLE_BIATSS'], UnifolioVoter::ACCES, false];
        yield 'super admin : gère' => [[UnifolioRoles::SUPER_ADMIN], UnifolioVoter::GERER, true];
        yield 'personnel : jamais d\'espace étudiant' => [[UnifolioRoles::SUPER_ADMIN], UnifolioVoter::ESPACE_ETUDIANT, false];
    }

    /** @param string[] $roles */
    #[DataProvider('personnels')]
    public function testPersonnels(array $roles, string $attribut, bool $attendu): void
    {
        $vote = $this->voter(eligible: false, roles: $roles)
            ->vote($this->token($this->createMock(Personnel::class)), null, [$attribut]);

        $this->assertSame($attendu ? VoterInterface::ACCESS_GRANTED : VoterInterface::ACCESS_DENIED, $vote);
    }

    /** @param string[] $roles */
    private function voter(bool $eligible, array $roles): UnifolioVoter
    {
        $permissions = $this->createMock(UserEffectivePermissionService::class);
        $permissions->method('isSuperAdmin')->willReturn(\in_array(UnifolioRoles::SUPER_ADMIN, $roles, true));
        $permissions->method('hasAnyPermission')->willReturnCallback(
            static fn ($user, array $demandes) => array_intersect($demandes, $roles) !== []
        );

        $perimetre = $this->createMock(UnifolioPerimetre::class);
        $perimetre->method('isEtudiantEligible')->willReturn($eligible);

        return new UnifolioVoter($permissions, $perimetre);
    }

    private function token(mixed $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }
}
