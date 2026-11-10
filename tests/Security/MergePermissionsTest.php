<?php

namespace App\Tests\Security;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Patch;
use App\Entity\Structure\StructureDepartement;
use App\Entity\Structure\StructureDepartementPersonnel;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Security\CorePermissionProvider;
use App\Security\DepartmentPermissionChecker;
use App\Security\PermissionRegistry;
use App\Security\PermissionResolver;
use App\Security\UserEffectivePermissionService;
use FinanceBundle\Entity\FinanceBonCommande;
use FinanceBundle\Security\FinanceAccess;
use FinanceBundle\Security\FinancePermissionProvider;
use FinanceBundle\Security\FinanceVoter;
use PHPUnit\Framework\TestCase;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Security\QuestionnaireVoter;
use StageBundle\Entity\Stages\StageEtudiant;
use StageBundle\Entity\Stages\StagePeriode;
use StageBundle\Security\StagePermissionProvider;
use StageBundle\Security\StageVoter;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/** Vérifie les décisions réelles avec le résolveur local, pas seulement la présence des attributs. */
final class MergePermissionsTest extends TestCase
{
    private UserEffectivePermissionService $permissions;
    private StructureDepartement $a;
    private StructureDepartement $b;

    protected function setUp(): void
    {
        $this->permissions = new UserEffectivePermissionService(new PermissionResolver(new PermissionRegistry([
            new CorePermissionProvider(), new FinancePermissionProvider(), new StagePermissionProvider(),
        ])));
        $this->a = new StructureDepartement();
        $this->b = new StructureDepartement();
        (new \ReflectionProperty($this->a, 'id'))->setValue($this->a, 1);
        (new \ReflectionProperty($this->b, 'id'))->setValue($this->b, 2);
    }

    private function user(array $roles, array $packages = ['intranet', 'finance', 'stages']): Personnel
    {
        $user = new Personnel();
        $user->addDepartementPersonnel((new StructureDepartementPersonnel())
            ->setDepartement($this->a)->setPermissions($roles)->setPackages($packages)->setDefaut(true));
        return $user;
    }

    public function testFinanceLeServiceFinancierCouvreToutEtablissementLesAutresRolesRestentParDepartement(): void
    {
        $voter = new FinanceVoter($this->permissions);
        $access = new FinanceAccess($this->permissions, $this->createMock(DepartmentPermissionChecker::class), $voter);
        $user = $this->user(['ROLE_FINANCE_MANAGER']);
        $a = (new FinanceBonCommande())->setDepartement($this->a);
        $b = (new FinanceBonCommande())->setDepartement($this->b)->setResponsable($user);
        // Gestionnaire (service financier) : toutes les demandes de l'établissement, quel que soit leur département.
        foreach ([FinanceVoter::MANAGE, FinanceVoter::BC_GERER, FinanceVoter::BC_MODIFIER, FinanceVoter::BC_SUPPRIMER] as $attribute) {
            self::assertTrue($voter->supportsAttribute($attribute));
            self::assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote(new UsernamePasswordToken($user, 'main'), $a, [$attribute]));
            self::assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote(new UsernamePasswordToken($user, 'main'), $b, [$attribute]), 'service financier');
        }
        self::assertTrue($access->peutVoir($user, $b));
        self::assertTrue($access->peutModifier($user, $b));

        // Valideur et demandeur : limités à leur département.
        $valideur = $this->user(['ROLE_FINANCE_VALIDEUR']);
        self::assertTrue($access->estValideur($valideur, $a));
        self::assertTrue($access->peutVoir($valideur, $a));
        self::assertFalse($access->estValideur($valideur, $b));
        self::assertFalse($access->peutVoir($valideur, $b));
        self::assertFalse($access->peutModifier($valideur, $a), 'le valideur agit par les transitions, pas par PATCH');
        $demandeur = $this->user(['ROLE_FINANCE_DEMANDEUR']);
        self::assertTrue($voter->allows(FinanceVoter::BC_CREER, $a, $demandeur));
        self::assertFalse($voter->allows(FinanceVoter::BC_CREER, $b, $demandeur));
        self::assertFalse($voter->allows(FinanceVoter::BC_GERER, $a, $demandeur));
    }

    public function testFinanceDemandeDeServiceDeposeeParUnDemandeurInstruiteParLeServiceFinancier(): void
    {
        $voter = new FinanceVoter($this->permissions);
        $access = new FinanceAccess($this->permissions, $this->createMock(DepartmentPermissionChecker::class), $voter);
        $service = (new \App\Entity\Structure\StructureService())->setLibelle('Informatique');
        $demandeur = $this->user(['ROLE_FINANCE_DEMANDEUR']);
        $sansRole = $this->user([], ['intranet']);
        $valideur = $this->user(['ROLE_FINANCE_VALIDEUR']);
        $gestionnaire = $this->user(['ROLE_FINANCE_MANAGER']);
        $commande = (new FinanceBonCommande())->setService($service)->setDemandeur($demandeur);

        self::assertTrue($voter->allows(FinanceVoter::BC_CREER, $commande, $demandeur));
        self::assertFalse($voter->allows(FinanceVoter::BC_CREER, $commande, $sansRole));
        self::assertTrue($access->estValideur($gestionnaire, $commande), 'avis par le service financier');
        self::assertFalse($access->estValideur($valideur, $commande), 'un valideur de département n\'a pas d\'avis sur un service');
        self::assertFalse($access->peutVoir($valideur, $commande));
        self::assertTrue($access->peutVoir($gestionnaire, $commande));
    }

    public function testProprieteFinanceSansRoleNeDonnePasDEcriture(): void
    {
        $user = $this->user([], ['intranet']);
        $commande = (new FinanceBonCommande())->setDepartement($this->a)->setDemandeur($user);
        $voter = new FinanceVoter($this->permissions);
        $access = new FinanceAccess($this->permissions, $this->createMock(DepartmentPermissionChecker::class), $voter);
        self::assertFalse($access->peutModifier($user, $commande));
        self::assertFalse($access->estDemandeur($user, $commande));
        self::assertFalse($access->peutDeposerDocument($user, $commande));
        self::assertFalse($voter->allows(FinanceVoter::BC_CREER, $commande, $user));
    }

    public function testCreationFinanceAvecDemandeurImposeParLeServeur(): void
    {
        $user = $this->user(['ROLE_FINANCE_DEMANDEUR']);
        $commande = (new FinanceBonCommande())->setDepartement($this->a);
        $voter = new FinanceVoter($this->permissions);
        self::assertTrue($voter->allows(FinanceVoter::BC_CREER, $commande, $user));
        self::assertNull($commande->getDemandeur(), 'Le vote ne doit pas modifier la ressource.');
        $commande->setDepartement($this->b);
        self::assertFalse($voter->allows(FinanceVoter::BC_CREER, $commande, $user));
    }

    public function testQuestionnaireAuteurEtNouveauRoleNeContournentPasLesRolesLocaux(): void
    {
        $user = $this->user([]);
        $q = (new Questionnaire())->setDepartement($this->a)->setAuthor($user);
        $voter = new QuestionnaireVoter($this->permissions);
        self::assertFalse($voter->allows(QuestionnaireVoter::MANAGE, $q, $user));
        self::assertFalse($voter->allows(QuestionnaireVoter::SYNTHESE, $q, $user));
        $chef = $this->user(['ROLE_CHEF_DEPT']);
        self::assertTrue($voter->allows(QuestionnaireVoter::MANAGE, $q, $chef));
        self::assertFalse($voter->allows(QuestionnaireVoter::DELETE, $q, $chef));
        $q->setDepartement($this->b);
        self::assertFalse($voter->allows(QuestionnaireVoter::MANAGE, $q, $chef));
    }

    public function testStageTuteurSansHabilitationEtEtudiantProprietaire(): void
    {
        $user = $this->user([], ['intranet']);
        $periode = $this->createMock(StagePeriode::class);
        $periode->method('getDepartement')->willReturn($this->a);
        $etudiant = new Etudiant();
        $stage = (new StageEtudiant())->setStagePeriode($periode)->setEtudiant($etudiant)->setTuteurUniversitaire($user);
        $voter = new StageVoter($this->permissions);
        self::assertFalse($voter->allows(StageVoter::STAGE_ETUDIANT_EDIT, $stage, $user));
        self::assertTrue($voter->allows(StageVoter::STAGE_ETUDIANT_EDIT, $stage, $etudiant));
        self::assertFalse($voter->allows(StageVoter::STAGE_ETUDIANT_EDIT, $stage, new Etudiant()));
        self::assertFalse($voter->allows(StageVoter::STAGE_ETUDIANT_MANAGE, $stage, $etudiant));
        self::assertTrue($voter->allows(StageVoter::STAGE_ETUDIANT_MANAGE, $stage, $this->user(['ROLE_CHEF_DEPT'])));
    }

    public function testSuperAdminHorsDepartementActifConserveSesDroits(): void
    {
        $user = $this->user([]);
        $user->addDepartementPersonnel((new StructureDepartementPersonnel())->setDepartement($this->b)
            ->setPermissions(['SUPER_ADMIN'])->setPackages(['intranet'])->setDefaut(false));
        self::assertTrue((new FinanceVoter($this->permissions))->allows(FinanceVoter::BC_GERER, new FinanceBonCommande(), $user));
        self::assertTrue((new QuestionnaireVoter($this->permissions))->allows(QuestionnaireVoter::MANAGE, new Questionnaire(), $user));
    }

    public function testLesOperationsConserventLesGardesAvantEtApresDenormalisation(): void
    {
        foreach ([FinanceBonCommande::class, StageEtudiant::class, Questionnaire::class] as $class) {
            $patch = null;
            foreach ((new \ReflectionClass($class))->getAttributes(ApiResource::class) as $attribute) {
                foreach ($attribute->newInstance()->getOperations() as $operation) {
                    if ($operation instanceof Patch) $patch = $operation;
                }
            }
            self::assertNotNull($patch);
            self::assertStringContainsString('CAN_EDIT_', $patch->getSecurity());
            self::assertStringContainsString('CAN_EDIT_', $patch->getSecurityPostDenormalize());
            self::assertNotNull($patch->getProcessor(), 'Le processeur entrant est conservé.');
        }
    }

    public function testNouvellesTransitionsFinanceNeContournentPasLaValidationLocale(): void
    {
        $demandeur = $this->user(['ROLE_FINANCE_DEMANDEUR']);
        $gestionnaire = $this->user(['ROLE_FINANCE_MANAGER']);
        $commande = (new FinanceBonCommande())->setDepartement($this->a)->setDemandeur($demandeur);
        $access = new FinanceAccess($this->permissions, $this->createMock(DepartmentPermissionChecker::class), new FinanceVoter($this->permissions));
        $workflow = new \FinanceBundle\Workflow\FinanceWorkflow($access);
        self::assertTrue($workflow->peut($demandeur, $commande, \FinanceBundle\Enum\ActionWorkflowEnum::SOUMETTRE));
        // Le document signé déposé par le demandeur peut porter la décision (avis favorable ou refus).
        self::assertTrue($workflow->peutDecider($demandeur, $commande));
        self::assertTrue($workflow->peutDecider($gestionnaire, $commande));
        $commande->setStatut(\FinanceBundle\Enum\StatutCommandeEnum::SOUMIS);
        self::assertFalse($workflow->peut($demandeur, $commande, \FinanceBundle\Enum\ActionWorkflowEnum::AVIS_FAVORABLE));
        // L'avis de l'étape 1 est rendu par le valideur du département (le service financier passe par le document signé).
        self::assertFalse($workflow->peut($gestionnaire, $commande, \FinanceBundle\Enum\ActionWorkflowEnum::AVIS_FAVORABLE));
        self::assertTrue($workflow->peut($this->user(['ROLE_FINANCE_VALIDEUR']), $commande, \FinanceBundle\Enum\ActionWorkflowEnum::AVIS_FAVORABLE));
        $commande->setStatut(\FinanceBundle\Enum\StatutCommandeEnum::COMMANDE_TRANSMISE);
        // Service fait en deux temps : le demandeur constate (avec le bon de livraison), le service financier vise.
        self::assertTrue($workflow->peut($demandeur, $commande, \FinanceBundle\Enum\ActionWorkflowEnum::CONSTATER_SERVICE_FAIT));
        self::assertTrue($workflow->peut($gestionnaire, $commande, \FinanceBundle\Enum\ActionWorkflowEnum::CONSTATER_SERVICE_FAIT));
    }

    public function testCreationSectionControleLaPermissionLocaleApresAffectationDuParent(): void
    {
        $chef = $this->user(['ROLE_CHEF_DEPT']);
        $q = (new Questionnaire())->setDepartement($this->a);
        $voter = new QuestionnaireVoter($this->permissions);
        $security = $this->createMock(\Symfony\Bundle\SecurityBundle\Security::class);
        $security->method('isGranted')->willReturnCallback(fn ($attribute, $subject) => $voter->allows($attribute, $subject, $chef));
        $repository = $this->createMock(\QuestionnaireBundle\Repository\Questionnaires\QuestionnaireRepository::class);
        $repository->method('findOneBy')->willReturn($q);
        $resolver = new \QuestionnaireBundle\Security\QuestionnaireResolver($repository, $security);
        $persist = $this->createMock(\ApiPlatform\State\ProcessorInterface::class);
        $persist->expects(self::never())->method('process');
        $processor = new \QuestionnaireBundle\State\QuestionnaireStructureProcessor(
            $persist, $this->createMock(\ApiPlatform\State\ProcessorInterface::class), $resolver,
            $this->createMock(\Doctrine\ORM\EntityManagerInterface::class), $security,
        );
        // Le chef peut modifier le questionnaire mais le voter local exige d'autres rôles pour sa structure.
        self::assertTrue($voter->allows(QuestionnaireVoter::CAN_EDIT_QUESTIONNAIRE, $q, $chef));
        $this->expectException(\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException::class);
        $processor->process(new \QuestionnaireBundle\Entity\Questionnaires\QuestionnaireSection(), new \ApiPlatform\Metadata\Post(), ['questionnaireId' => (string) $q->getUuid()]);
    }
}
