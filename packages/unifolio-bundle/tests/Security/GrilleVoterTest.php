<?php

namespace UnifolioBundle\Tests\Security;

use App\Entity\Structure\StructureDepartement;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Structure\StructureTypeDiplome;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Security\UserEffectivePermissionService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use UnifolioBundle\Entity\Grille;
use UnifolioBundle\Enum\GranulariteEnum;
use UnifolioBundle\Security\GrilleVoter;
use UnifolioBundle\Security\ReferentContexte;
use UnifolioBundle\Security\UnifolioRoles;

class GrilleVoterTest extends TestCase
{
    private StructureDepartement $mmi;
    private StructureDepartement $gea;
    private StructureTypeDiplome $but;

    protected function setUp(): void
    {
        $this->mmi = $this->departement(10);
        $this->gea = $this->departement(11);
        $this->but = $this->createMock(StructureTypeDiplome::class);
        $this->but->method('getId')->willReturn(1);
        $this->but->method('isApc')->willReturn(true);
    }

    public function testReferentDeDepartement(): void
    {
        $voter = $this->voter(roles: [UnifolioRoles::REFERENT], departement: $this->mmi);
        $token = $this->token($this->createMock(Personnel::class));

        $nationale = new Grille('N', $this->but, GranulariteEnum::AC);
        $sienne = new Grille('MMI', $this->but, GranulariteEnum::AC, $this->mmi);
        $autre = new Grille('GEA', $this->but, GranulariteEnum::AC, $this->gea);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $nationale, [GrilleVoter::VOIR]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $nationale, [GrilleVoter::MODIFIER]), 'Grille nationale en lecture seule');
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $sienne, [GrilleVoter::MODIFIER]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $autre, [GrilleVoter::VOIR]), 'Grille d\'un autre département invisible');

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $this->diplome($this->mmi), [GrilleVoter::PARAMETRER_FORMATION]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $this->diplome($this->gea), [GrilleVoter::PARAMETRER_FORMATION]));
    }

    public function testEnseignantSansRoleReferent(): void
    {
        $voter = $this->voter(roles: [UnifolioRoles::ENSEIGNANT], departement: $this->mmi);
        $vote = $voter->vote($this->token($this->createMock(Personnel::class)), new Grille('MMI', $this->but, GranulariteEnum::AC, $this->mmi), [GrilleVoter::MODIFIER]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $vote);
    }

    public function testSuperAdminModifieLesGrillesNationales(): void
    {
        $voter = $this->voter(roles: [], departement: null, superAdmin: true);
        $vote = $voter->vote($this->token($this->createMock(Personnel::class)), new Grille('N', $this->but, GranulariteEnum::AC), [GrilleVoter::MODIFIER]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $vote);
    }

    public function testEtudiantRefuse(): void
    {
        $vote = $this->voter([], $this->mmi)->vote($this->token($this->createMock(Etudiant::class)), new Grille('N', $this->but, GranulariteEnum::AC), [GrilleVoter::VOIR]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $vote);
    }

    /** @param string[] $roles */
    private function voter(array $roles, ?StructureDepartement $departement, bool $superAdmin = false): GrilleVoter
    {
        $permissions = $this->createMock(UserEffectivePermissionService::class);
        $permissions->method('hasAnyPermission')->willReturnCallback(static fn ($u, array $d) => array_intersect($d, $roles) !== []);

        $contexte = $this->createMock(ReferentContexte::class);
        $contexte->method('isSuperAdmin')->willReturn($superAdmin);
        $contexte->method('administre')->willReturnCallback(
            static fn ($u, ?StructureDepartement $d) => $superAdmin || ($departement !== null && $d !== null && $d->getId() === $departement->getId())
        );

        return new GrilleVoter($permissions, $contexte);
    }

    private function departement(int $id): StructureDepartement
    {
        $d = $this->createMock(StructureDepartement::class);
        $d->method('getId')->willReturn($id);

        return $d;
    }

    private function diplome(StructureDepartement $departement): StructureDiplome
    {
        $d = $this->createMock(StructureDiplome::class);
        $d->method('getDepartement')->willReturn($departement);

        return $d;
    }

    private function token(mixed $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }
}
