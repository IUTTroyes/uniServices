<?php

// php back/tests/Dashboard/WidgetPermissionsTest.php -- no database needed.
require dirname(__DIR__, 2).'/vendor/autoload.php';

use App\Controller\DashboardController;
use App\Domain\Dashboard\{DashboardContext, WidgetDefinition, WidgetDataProviderInterface, WidgetProviderInterface};
use App\Entity\Dashboard\DashboardPreference;
use App\Entity\Structure\{StructureDepartement, StructureDepartementPersonnel, StructureDiplome};
use App\Entity\Users\{Personnel, Etudiant};
use App\Repository\Dashboard\DashboardPreferenceRepository;
use App\Security\{CorePermissionProvider, PermissionRegistry, PermissionResolver, UserEffectivePermissionService};
use App\Service\Dashboard\Core\{WidgetAccessChecker, WidgetRegistry, WidgetDataRegistry, DashboardRegistry};
use IntranetBundle\Services\Dashboard\Provider\IntranetWidgetProvider;
use IntranetBundle\Services\Dashboard\Definition\IntranetDashboardDefinition;
use QuestionnaireBundle\Services\Dashboard\Provider\QuestionnaireWidgetProvider;
use QuestionnaireBundle\Security\QuestionnairePermissionProvider;
use UnifolioBundle\Services\Dashboard\Provider\PortfolioWidgetProvider;
use UnifolioBundle\Security\UnifolioPermissionProvider;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\{UsernamePasswordToken, Storage\TokenStorage};

$checks = 0;
function check(bool $condition, string $message): void {
    global $checks; ++$checks;
    if (!$condition) throw new RuntimeException($message);
}
function identify(object $entity, int $id): object {
    (new ReflectionProperty($entity, 'id'))->setValue($entity, $id); return $entity;
}
$department = identify(new StructureDepartement(), 1);
$assignment = identify(new StructureDepartementPersonnel(), 5)->setDepartement($department)->setDefaut(true)->setPackages(['intranet', 'questionnaire', 'unifolio']);
$user = identify(new Personnel(), 1)->addDepartementPersonnel($assignment);
$permissions = new UserEffectivePermissionService(new PermissionResolver(new PermissionRegistry([
    new CorePermissionProvider(), new QuestionnairePermissionProvider(), new UnifolioPermissionProvider(),
])));
$access = new WidgetAccessChecker($permissions);
$widgets = new WidgetRegistry([new IntranetWidgetProvider(), new QuestionnaireWidgetProvider(), new PortfolioWidgetProvider()]);
$actions = $widgets->get('intranet.actions_urgentes');
check($access->isGranted($widgets->get('intranet.emploi_du_temps'), $user), 'Teacher must retain timetable');
check($access->isGranted($widgets->get('intranet.listings_etudiants'), $user), 'Teacher must retain listings');
check(!$access->isGranted($actions, $user), 'Teacher must not see pilotage widget');
check(!$access->isGranted($widgets->get('questionnaire.stats'), $user), 'Teacher must not see questionnaire statistics');
check(!$access->isGranted($widgets->get('portfolio.to_correct'), $user), 'Teacher is not automatically a portfolio corrector');
$assignment->setPermissions(['ROLE_RESP_PARCOURS']);
check(!$access->isGranted($actions, $user), 'Parcours responsibility without diploma must be denied');
foreach ([10, 11] as $id) $assignment->addResponsableParcoursDiplome(identify(new StructureDiplome(), $id)->setDepartement($department));
check($access->isGranted($actions, $user), 'Valid parcours responsibility must see additional widget');
check($access->getDiplomeIds($actions, $user) === [10, 11], 'Widget scope must contain both assigned diplomas');
$foreign = identify(new StructureDiplome(), 12)->setDepartement(identify(new StructureDepartement(), 2));
$assignment->addResponsableParcoursDiplome($foreign);
check(!$access->isGranted($actions, $user), 'Foreign diploma must invalidate parcours-only widget access');
$assignment->removeResponsableParcoursDiplome($foreign);
$assignment->setPermissions(['ROLE_RESP_PARCOURS', 'ROLE_CHEF_DEPARTEMENT']);
check($access->getDiplomeIds($actions, $user) === null, 'Independent department-wide rights must not be reduced');
$assignment->setPermissions(['ROLE_QUESTIONNAIRE_MANAGER']);
check($access->isGranted($widgets->get('questionnaire.stats'), $user), 'Questionnaire manager must see statistics');
$assignment->setPermissions(['ROLE_PORTFOLIO_CORRECTOR']);
check($access->isGranted($widgets->get('portfolio.to_correct'), $user), 'Portfolio corrector must see correction widget');
$assignment->setPermissions(['SUPER_ADMIN']);
check($access->isGranted($actions, $user), 'Administrator can see personnel pilotage');
check(!$access->isGranted($widgets->get('portfolio.progress'), $user), 'Administrator must not become a student');
$assignment->setPackages(['intranet']);
check(!$access->isGranted($widgets->get('portfolio.to_correct'), $user), 'Disabled bundle must deny even an administrator');
$student = (new Etudiant())->setApplications(['Intranet', 'unifolio']);
check($access->isGranted($widgets->get('portfolio.progress'), $student), 'Student can see own progression');
check(!$access->isGranted($actions, $student), 'Student must not see personnel pilotage');
$andWidget = new WidgetDefinition('test.and', 'intranet', 'Test', '', '', requiredPermissions: ['ROLE_ASSISTANT', 'ROLE_ABSENCE'], requireAllPermissions: true);
$assignment->setPermissions(['ROLE_ASSISTANT']);
check(!$access->isGranted($andWidget, $user), 'AND rule must require every permission');
$assignment->setPermissions(['ROLE_ASSISTANT', 'ROLE_ABSENCE']);
check($access->isGranted($andWidget, $user), 'AND rule must accept all required permissions');

$provider = new class implements WidgetDataProviderInterface {
    public int $calls = 0;
    public function supports(string $code): bool { return true; }
    public function getData(string $code, Personnel|Etudiant $user, ?DashboardContext $context = null): array {
        ++$this->calls; return ['diplomeIds' => $context?->getDiplomeIds()];
    }
};
$data = new WidgetDataRegistry([$provider], $widgets, $access, $permissions);
$assignment->setPermissions([]);
try { $data->get('intranet.actions_urgentes', $user); check(false, 'Direct data access must be denied'); }
catch (AccessDeniedHttpException) { check($provider->calls === 0, 'Unauthorized data provider must never execute'); }
$assignment->setPermissions(['ROLE_RESP_PARCOURS']);
check($data->get('intranet.actions_urgentes', $user)['diplomeIds'] === [10, 11], 'Data provider must receive actual diplôme scope');

$preferences = new class extends DashboardPreferenceRepository {
    public array $items = [];
    public int $saves = 0;
    public function __construct() {}
    public function findByUser(Personnel|Etudiant $user, ?StructureDepartementPersonnel $structureDepartementPersonnel = null, string $dashboardCode = 'intranet'): array { return $this->items; }
    public function save(DashboardPreference $entity, bool $flush = false): void { ++$this->saves; }
};
$controller = new DashboardController($widgets, $data, $preferences, new DashboardRegistry([new IntranetDashboardDefinition()]), $access, $permissions);
$storage = new TokenStorage();
$storage->setToken(new UsernamePasswordToken($user, 'main', $user->getRoles()));
$container = new Container(); $container->set('security.token_storage', $storage); $controller->setContainer($container);
$request = Request::create('/api/widgets/catalog', 'GET', ['dashboardCode' => 'intranet']);
$codes = fn ($response) => array_column(json_decode($response->getContent(), true)['widgets'], 'code');
check(in_array('intranet.actions_urgentes', $codes($controller->getWidgetsCatalog($request)), true), 'Parcours widget must appear in catalog');
$assignment->setPermissions([]);
check(!in_array('intranet.actions_urgentes', $codes($controller->getWidgetsCatalog($request)), true), 'Revoked role must immediately disappear from catalog');
$available = Request::create('/api/widgets/available/intranet');
check(!in_array('intranet.actions_urgentes', $codes($controller->getWidgetsAvailable($available, 'intranet')), true), 'Teacher must not be offered pilotage configuration');
$preferences->items = [(new DashboardPreference())->setWidgetKey('intranet.actions_urgentes')->setEnabled(true)];
check($codes($controller->getWidgetsCatalog($request)) === [], 'Saved enabled preferences must not bypass permissions');
$patch = Request::create('/api/dashboard/widgets/intranet.actions_urgentes/layout', 'PATCH', ['dashboardCode' => 'intranet'], content: '{"enabled":true}');
check($controller->updateDashboardWidgetLayout($patch, 'intranet.actions_urgentes')->getStatusCode() === 403 && $preferences->saves === 0, 'Forbidden activation must not save preferences');
check($controller->getWidgetData('intranet.actions_urgentes')->getStatusCode() === 403, 'Direct endpoint must deny data');
try {
    $controller->getWidgetsCatalog(Request::create('/api/widgets/catalog', 'GET', ['dashboardCode' => 'intranet', 'structureDepartementPersonnelId' => 99]));
    check(false, 'Forged assignment must be rejected');
} catch (Symfony\Component\Security\Core\Exception\AccessDeniedException) { check(true, 'Forged assignment rejected'); }
echo "$checks widget access checks passed.\n";
