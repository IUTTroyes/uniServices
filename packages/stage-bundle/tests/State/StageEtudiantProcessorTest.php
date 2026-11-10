<?php

namespace StageBundle\Tests\State;

use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Entity\Structure\StructureDepartement;
use App\Security\DepartmentPermissionChecker;
use App\Security\UserEffectivePermissionService;
use PHPUnit\Framework\TestCase;
use StageBundle\Entity\Stages\StageEtudiant;
use StageBundle\Entity\Stages\StagePeriode;
use StageBundle\Enum\EtatStageEnum;
use StageBundle\Security\StageAccess;
use StageBundle\Service\Notification\StageNotifier;
use StageBundle\Service\StageEntrepriseResolver;
use StageBundle\Service\StageReglesLegales;
use ApiPlatform\Validator\Exception\ValidationException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use StageBundle\State\StageEtudiantProcessor;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class StageEtudiantProcessorTest extends TestCase
{
    private int $persistances = 0;
    private int $notifications = 0;

    /** @var list<array{champ: string, message: string}> erreurs renvoyées par le contrôle des règles légales */
    private array $erreursLegales = [];

    private function processor(Etudiant|Personnel $user, bool $gestionnaire = false): StageEtudiantProcessor
    {
        $regles = $this->createMock(StageReglesLegales::class);
        $regles->method('verifier')->willReturnCallback(fn () => $this->erreursLegales);
        $persist = $this->createMock(ProcessorInterface::class);
        $persist->method('process')->willReturnCallback(function ($data) {
            ++$this->persistances;

            return $data;
        });
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);
        $permissions = $this->createMock(UserEffectivePermissionService::class);
        $permissions->method('isSuperAdmin')->willReturn(false);
        $permissions->method('hasPermission')->willReturn($gestionnaire);
        $permissions->method('hasAnyPermissionInDepartment')->willReturn($gestionnaire);
        $departements = $this->createMock(DepartmentPermissionChecker::class);
        $departements->method('checkPermission')->willReturn($gestionnaire);
        $notifier = $this->createMock(StageNotifier::class);
        $notifier->method('dossierEnregistre')->willReturnCallback(function () {
            ++$this->notifications;
        });

        return new StageEtudiantProcessor($persist, $security, new StageAccess($permissions, $departements, new \StageBundle\Security\StageVoter($permissions)), $notifier, $this->createMock(StageEntrepriseResolver::class), $regles);
    }

    /** @param array<string, mixed> $corps */
    private function patch(StageEtudiantProcessor $p, StageEtudiant $apres, StageEtudiant $avant, array $corps): void
    {
        $request = new Request(content: json_encode($corps, \JSON_THROW_ON_ERROR));
        $p->process($apres, new Patch(), [], ['previous_data' => $avant, 'request' => $request]);
    }

    private function stage(Etudiant $etudiant, EtatStageEnum $etat, ?Personnel $tuteur = null): StageEtudiant
    {
        return (new StageEtudiant())->setEtudiant($etudiant)->setEtatStage($etat)->setTuteurUniversitaire($tuteur)
            ->setStagePeriode((new StagePeriode())->setLibelle('P'));
    }

    private function periodeDuDepartement(): StagePeriode
    {
        $periode = $this->createMock(StagePeriode::class);
        $periode->method('getDepartement')->willReturn(new StructureDepartement());
        $periode->method('getCoResponsables')->willReturn(new \Doctrine\Common\Collections\ArrayCollection());

        return $periode;
    }

    public function testLEtudiantDeposeSaDemande(): void
    {
        $etu = new Etudiant();
        $avant = $this->stage($etu, EtatStageEnum::AUTORISE);
        $apres = (clone $avant)->setEtatStage(EtatStageEnum::DEPOSE)->setSujetStage('Sujet');

        $this->patch($this->processor($etu), $apres, $avant, ['etatStage' => 'ETAT_STAGE_DEPOSE', 'sujetStage' => 'Sujet']);

        self::assertSame(1, $this->persistances);
        self::assertSame(1, $this->notifications);
    }

    public function testLEtudiantNePeutPasSeValider(): void
    {
        $etu = new Etudiant();
        $avant = $this->stage($etu, EtatStageEnum::DEPOSE);
        $apres = (clone $avant)->setEtatStage(EtatStageEnum::VALIDE);

        $this->expectException(AccessDeniedHttpException::class);
        $this->patch($this->processor($etu), $apres, $avant, ['etatStage' => 'ETAT_STAGE_VALIDE']);
    }

    public function testLEtudiantNePeutPasEcrireUnChampReserve(): void
    {
        $etu = new Etudiant();
        $avant = $this->stage($etu, EtatStageEnum::DEPOSE);
        $apres = (clone $avant)->setEvaluationNote(20.0);

        $this->expectException(AccessDeniedHttpException::class);
        $this->patch($this->processor($etu), $apres, $avant, ['evaluationNote' => 20]);
    }

    public function testConventionSigneeVerrouilleeCoteEtudiant(): void
    {
        $etu = new Etudiant();
        $avant = $this->stage($etu, EtatStageEnum::CONVENTION_RECUE);
        $apres = (clone $avant)->setEtatStage(EtatStageEnum::DEPOSE);

        $this->expectException(AccessDeniedHttpException::class);
        $this->patch($this->processor($etu), $apres, $avant, ['etatStage' => 'ETAT_STAGE_DEPOSE']);
    }

    public function testLeTuteurSansHabilitationNePeutPasEcrire(): void
    {
        $tuteur = new Personnel();
        $avant = $this->stage(new Etudiant(), EtatStageEnum::VALIDE, $tuteur);

        $this->expectException(AccessDeniedHttpException::class);
        $this->patch($this->processor($tuteur), (clone $avant)->setEvaluationNote(15.0), $avant, ['evaluationNote' => 15]);
    }

    public function testUnAutrePersonnelEstRefuse(): void
    {
        $avant = $this->stage(new Etudiant(), EtatStageEnum::VALIDE, new Personnel());

        $this->expectException(AccessDeniedHttpException::class);
        $this->patch($this->processor(new Personnel()), clone $avant, $avant, ['sujetStage' => 'x']);
    }

    public function testLeGestionnaireFaitTout(): void
    {
        $avant = $this->stage(new Etudiant(), EtatStageEnum::DEPOSE)->setStagePeriode($this->periodeDuDepartement());
        $this->patch($this->processor(new Personnel(), true), (clone $avant)->setEtatStage(EtatStageEnum::VALIDE), $avant, ['etatStage' => 'ETAT_STAGE_VALIDE', 'evaluationNote' => 12]);

        self::assertSame(1, $this->persistances);
    }

    public function testLesDatesDeTraitementSontPoseesParLeServeur(): void
    {
        $avant = $this->stage(new Etudiant(), EtatStageEnum::DEPOSE)->setStagePeriode($this->periodeDuDepartement());
        $apres = (clone $avant)->setEtatStage(EtatStageEnum::VALIDE);
        $this->patch($this->processor(new Personnel(), true), $apres, $avant, ['etatStage' => 'ETAT_STAGE_VALIDE']);
        self::assertNotNull($apres->getDateValidation());

        $envoyee = (clone $apres)->setEtatStage(EtatStageEnum::CONVENTION_ENVOYEE);
        $this->patch($this->processor(new Personnel(), true), $envoyee, $apres, ['etatStage' => 'ETAT_STAGE_CONVENTION_ENVOYEE']);
        self::assertNotNull($envoyee->getDateConventionEnvoyee());
        self::assertNotNull($envoyee->getDateImprime(), 'une convention envoyée a forcément été éditée');
    }

    public function testUnNouveauDepotDeLEtudiantMetAJourLaDateDeDepot(): void
    {
        $etu = new Etudiant();
        $avant = $this->stage($etu, EtatStageEnum::DEPOSE)->setDateDepotFormulaire(new \DateTime('2020-01-01'));
        $apres = clone $avant;
        $this->patch($this->processor($etu), $apres, $avant, ['sujetStage' => 'Nouveau sujet']);

        self::assertGreaterThan(new \DateTime('2025-01-01'), $apres->getDateDepotFormulaire());
    }

    public function testUneTransitionImpossibleEstRefusee(): void
    {
        $avant = $this->stage(new Etudiant(), EtatStageEnum::REFUS)->setStagePeriode($this->periodeDuDepartement());

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->patch($this->processor(new Personnel(), true), (clone $avant)->setEtatStage(EtatStageEnum::CONVENTION_RECUE), $avant, ['etatStage' => 'ETAT_STAGE_CONVENTION_RECUE']);
    }

    public function testUneConventionSigneeNeSeReinitialisePas(): void
    {
        $avant = $this->stage(new Etudiant(), EtatStageEnum::CONVENTION_RECUE)->setStagePeriode($this->periodeDuDepartement());

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->patch($this->processor(new Personnel(), true), (clone $avant)->setEtatStage(EtatStageEnum::AUTORISE), $avant, ['etatStage' => 'ETAT_STAGE_AUTORISE']);
    }

    public function testLesReglesLegalesBloquentLeDepot(): void
    {
        $etu = new Etudiant();
        $avant = $this->stage($etu, EtatStageEnum::AUTORISE);
        $this->erreursLegales = [['champ' => 'gratificationMontant', 'message' => 'Gratification obligatoire']];

        try {
            $this->patch($this->processor($etu), (clone $avant)->setEtatStage(EtatStageEnum::DEPOSE), $avant, ['etatStage' => 'ETAT_STAGE_DEPOSE']);
            self::fail('Le dépôt aurait dû être refusé.');
        } catch (ValidationException $e) {
            self::assertSame('gratificationMontant', $e->getConstraintViolationList()->get(0)->getPropertyPath());
            self::assertSame(0, $this->persistances);
        }
    }

    public function testLeMotifDeRefusDisparaitAuRedepot(): void
    {
        $etu = new Etudiant();
        $avant = $this->stage($etu, EtatStageEnum::REFUS)->setMotifRefus('Sujet trop vague');
        $apres = (clone $avant)->setEtatStage(EtatStageEnum::DEPOSE);
        $this->patch($this->processor($etu), $apres, $avant, ['etatStage' => 'ETAT_STAGE_DEPOSE']);

        self::assertNull($apres->getMotifRefus());
    }

    public function testLEtudiantNeSaisitPasLeMotifDeRefus(): void
    {
        $etu = new Etudiant();
        $avant = $this->stage($etu, EtatStageEnum::DEPOSE);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException::class);
        $this->patch($this->processor($etu), clone $avant, $avant, ['motifRefus' => 'rien']);
    }

    public function testCreationParLEtudiant(): void
    {
        $etu = new Etudiant();
        $stage = $this->stage($etu, EtatStageEnum::DEPOSE);
        $request = new Request(content: json_encode(['etatStage' => 'ETAT_STAGE_DEPOSE', 'etudiant' => '/api/etudiants/1']));

        $this->processor($etu)->process($stage, new Post(), [], ['request' => $request]);
        self::assertSame(1, $this->persistances);
    }
}
