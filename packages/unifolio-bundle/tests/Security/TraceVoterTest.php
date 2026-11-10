<?php

namespace UnifolioBundle\Tests\Security;

use App\Entity\Apc\ApcCompetence;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Security\UserEffectivePermissionService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use UnifolioBundle\Entity\Trace;
use UnifolioBundle\Entity\TraceMedia;
use UnifolioBundle\Entity\TraceRattachement;
use UnifolioBundle\Enum\ConceptionIaEnum;
use UnifolioBundle\Enum\RealisationIaEnum;
use UnifolioBundle\Enum\StatutTraceEnum;
use UnifolioBundle\Enum\TypeMediaEnum;
use UnifolioBundle\Security\TraceVoter;
use UnifolioBundle\Security\UnifolioRoles;
use UnifolioBundle\Service\Campagne\EvaluateurAcces;

class TraceVoterTest extends TestCase
{
    private Etudiant $proprietaire;
    private Trace $trace;

    protected function setUp(): void
    {
        $this->proprietaire = $this->etudiant(1);
        $this->trace = new Trace($this->proprietaire, 'Projet');
    }

    public function testProprietaireSurBrouillon(): void
    {
        $voter = $this->voter([]);
        $token = $this->token($this->proprietaire);

        foreach ([TraceVoter::VOIR, TraceVoter::MODIFIER, TraceVoter::SUPPRIMER, TraceVoter::DEPOSER] as $droit) {
            $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $this->trace, [$droit]), $droit);
        }
    }

    public function testAutreEtudiantRefuse(): void
    {
        $vote = $this->voter([])->vote($this->token($this->etudiant(2)), $this->trace, [TraceVoter::VOIR]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $vote);
    }

    public function testTraceEnEvaluationNiModifiableNiSupprimable(): void
    {
        $this->trace->addMedia(TraceMedia::depuisTexte($this->trace, TypeMediaEnum::TEXTE, 'x'));
        $competence = $this->createMock(ApcCompetence::class);
        $competence->method('getId')->willReturn(1);
        $this->trace->addRattachement(TraceRattachement::versCompetence($this->trace, $competence));
        $this->trace->setConceptionIa(ConceptionIaEnum::AUCUN)->setRealisationIa(RealisationIaEnum::AUCUN);
        $this->trace->deposer();
        $this->trace->changerStatut(StatutTraceEnum::EN_EVALUATION);

        $voter = $this->voter([]);
        $token = $this->token($this->proprietaire);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $this->trace, [TraceVoter::VOIR]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $this->trace, [TraceVoter::MODIFIER]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $this->trace, [TraceVoter::SUPPRIMER]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $this->trace, [TraceVoter::DEPOSER]));
    }

    public function testReferentVoitMaisNeModifiePas(): void
    {
        $voter = $this->voter([UnifolioRoles::REFERENT]);
        $token = $this->token($this->createMock(Personnel::class));

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $this->trace, [TraceVoter::VOIR]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $this->trace, [TraceVoter::MODIFIER]));
    }

    public function testEvaluateurAffecteVoitSansPouvoirModifier(): void
    {
        $voter = $this->voter([UnifolioRoles::ENSEIGNANT], evaluateurAffecte: true);
        $token = $this->token($this->createMock(Personnel::class));

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $this->trace, [TraceVoter::VOIR]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $this->trace, [TraceVoter::MODIFIER]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $this->trace, [TraceVoter::DEPOSER]));
    }

    public function testEnseignantSansAffectationNeVoitPas(): void
    {
        $vote = $this->voter([UnifolioRoles::ENSEIGNANT])->vote($this->token($this->createMock(Personnel::class)), $this->trace, [TraceVoter::VOIR]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $vote);
    }

    public function testIgnoreLesAutresSujets(): void
    {
        $vote = $this->voter([])->vote($this->token($this->proprietaire), new \stdClass(), [TraceVoter::VOIR]);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $vote);
    }

    /** @param string[] $roles */
    private function voter(array $roles, bool $evaluateurAffecte = false): TraceVoter
    {
        $evaluateurs = $this->createMock(EvaluateurAcces::class);
        $evaluateurs->method('peutVoirTrace')->willReturn($evaluateurAffecte);

        $permissions = $this->createMock(UserEffectivePermissionService::class);
        $permissions->method('isSuperAdmin')->willReturn(false);
        $permissions->method('hasAnyPermission')->willReturnCallback(static fn ($u, array $d) => array_intersect($d, $roles) !== []);

        return new TraceVoter($permissions, $evaluateurs);
    }

    private function etudiant(int $id): Etudiant
    {
        $e = $this->createMock(Etudiant::class);
        $e->method('getId')->willReturn($id);

        return $e;
    }

    private function token(mixed $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }
}
