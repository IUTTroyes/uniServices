<?php

namespace UnifolioBundle\Tests\Security;

use App\Entity\Structure\StructureDepartement;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Security\UserEffectivePermissionService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use UnifolioBundle\Entity\Campagne;
use UnifolioBundle\Entity\Evaluation;
use UnifolioBundle\Enum\StatutEvaluationEnum;
use UnifolioBundle\Security\EvaluationVoter;
use UnifolioBundle\Security\ReferentContexte;
use UnifolioBundle\Security\UnifolioRoles;
use UnifolioBundle\Service\Campagne\EvaluateurAcces;

class EvaluationVoterTest extends TestCase
{
    public function testAuteurVoitEtModifie(): void
    {
        $e = $this->evaluation(auteur: $this->personnel(7), statut: StatutEvaluationEnum::EN_COURS);
        $voter = $this->voter();

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($this->token($this->personnel(7)), $e, [EvaluationVoter::VOIR]));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($this->token($this->personnel(7)), $e, [EvaluationVoter::MODIFIER]));
    }

    public function testAutreEvaluateurVoitSeulementLesRendues(): void
    {
        $voter = $this->voter(affecte: true);
        $autre = $this->token($this->personnel(8));

        $enCours = $this->evaluation($this->personnel(7), StatutEvaluationEnum::EN_COURS);
        $rendue = $this->evaluation($this->personnel(7), StatutEvaluationEnum::RENDUE);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($autre, $enCours, [EvaluationVoter::VOIR]));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($autre, $rendue, [EvaluationVoter::VOIR]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($autre, $rendue, [EvaluationVoter::MODIFIER]), 'Jamais l\'évaluation d\'un autre');
    }

    public function testEvaluateurNonAffecteNeVoitRien(): void
    {
        $vote = $this->voter(affecte: false)->vote($this->token($this->personnel(8)), $this->evaluation($this->personnel(7), StatutEvaluationEnum::RENDUE), [EvaluationVoter::VOIR]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $vote);
    }

    public function testReferentDuDepartementConsulteSansModifier(): void
    {
        $voter = $this->voter(roles: [UnifolioRoles::REFERENT], administre: true);
        $e = $this->evaluation($this->personnel(7), StatutEvaluationEnum::EN_COURS);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($this->token($this->personnel(9)), $e, [EvaluationVoter::VOIR]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($this->token($this->personnel(9)), $e, [EvaluationVoter::MODIFIER]));
    }

    public function testEtudiantVoitSeulementCeQuiLuiEstCommuniquee(): void
    {
        $voter = $this->voter();
        $etudiant = $this->createMock(Etudiant::class);
        $etudiant->method('getId')->willReturn(1);
        $token = $this->token($etudiant);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $this->evaluation($this->personnel(7), StatutEvaluationEnum::RENDUE, visible: true, etudiantId: 1), [EvaluationVoter::VOIR]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $this->evaluation($this->personnel(7), StatutEvaluationEnum::RENDUE, visible: false, etudiantId: 1), [EvaluationVoter::VOIR]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $this->evaluation($this->personnel(7), StatutEvaluationEnum::RENDUE, visible: true, etudiantId: 2), [EvaluationVoter::VOIR]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $this->evaluation($this->personnel(7), StatutEvaluationEnum::RENDUE, visible: true, etudiantId: 1), [EvaluationVoter::MODIFIER]));
    }

    private function evaluation(Personnel $auteur, StatutEvaluationEnum $statut, bool $visible = false, int $etudiantId = 1): Evaluation
    {
        $etudiant = $this->createMock(Etudiant::class);
        $etudiant->method('getId')->willReturn($etudiantId);
        $diplome = $this->createMock(StructureDiplome::class);
        $diplome->method('getDepartement')->willReturn($this->createMock(StructureDepartement::class));
        $campagne = $this->createMock(Campagne::class);
        $campagne->method('getDiplome')->willReturn($diplome);

        $e = $this->createMock(Evaluation::class);
        $e->method('estDe')->willReturnCallback(static fn (Personnel $p) => $p->getId() === $auteur->getId());
        $e->method('getStatut')->willReturn($statut);
        $e->method('isVisibleEtudiant')->willReturn($visible);
        $e->method('getEtudiant')->willReturn($etudiant);
        $e->method('getCampagne')->willReturn($campagne);

        return $e;
    }

    /** @param string[] $roles */
    private function voter(array $roles = [], bool $administre = false, bool $affecte = false): EvaluationVoter
    {
        $permissions = $this->createMock(UserEffectivePermissionService::class);
        $permissions->method('hasAnyPermission')->willReturnCallback(static fn ($u, array $d) => array_intersect($d, $roles) !== []);
        $contexte = $this->createMock(ReferentContexte::class);
        $contexte->method('isSuperAdmin')->willReturn(false);
        $contexte->method('administre')->willReturn($administre);
        $acces = $this->createMock(EvaluateurAcces::class);
        $acces->method('estAffecte')->willReturn($affecte);

        return new EvaluationVoter($permissions, $contexte, $acces);
    }

    private function personnel(int $id): Personnel
    {
        $p = $this->createMock(Personnel::class);
        $p->method('getId')->willReturn($id);

        return $p;
    }

    private function token(mixed $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }
}
