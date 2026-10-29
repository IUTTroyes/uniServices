<?php

namespace IntranetBundle\Service\Dashboard;

use App\Entity\Users\{Etudiant, Personnel};
use App\Security\UserEffectivePermissionService;
use Symfony\Component\HttpKernel\Exception\{AccessDeniedHttpException, NotFoundHttpException};

/** Data for independently mounted dashboard sections. UI visibility never grants data access. */
final class IntranetDashboardService
{
    public function __construct(
        private readonly UserEffectivePermissionService $permissions,
        private readonly IntranetScheduleService $schedule,
    ) {}

    public function getSection(Personnel|Etudiant $user, string $section): array
    {
        $assignment = $user instanceof Personnel ? $this->permissions->getActiveDepartementPersonnel($user) : null;
        if (!$user instanceof Personnel || !in_array('intranet', $assignment?->getPackages() ?? [], true)) {
            throw new AccessDeniedHttpException('Accès Intranet non autorisé.');
        }
        $roles = match ($section) {
            'activite' => ['ROLE_TEACHER'],
            'formations' => ['ROLE_CHEF_DEPARTEMENT', 'ROLE_CHEF_DEPT', 'ROLE_DIRECTEUR_ETUDES', 'ROLE_SCOLARITE', 'ROLE_ASSISTANT'],
            'iut' => UserEffectivePermissionService::IUT_DIRECTION_ROLES,
            'parcours' => ['ROLE_RESP_PARCOURS'],
            default => throw new NotFoundHttpException('Section inconnue.'),
        };
        $scope = $this->permissions->getScopedPermissions($user)['ROLE_RESP_PARCOURS']['diplomeIds'] ?? [];
        $allowed = $section === 'parcours' ? $scope !== [] : array_intersect($roles, $this->permissions->getEffectivePermissions($user)) !== [];
        if (!$allowed && !$this->permissions->isSuperAdmin($user)) throw new AccessDeniedHttpException('Section non autorisée.');

        if ($section === 'activite') return $this->schedule->getToday($user);
        if ($section === 'formations') return ['diplomes' => $this->departmentDiplomes($user)];
        if ($section === 'parcours') return ['diplomes' => $this->departmentDiplomes($user, $scope)];
        $departements = [];
        foreach ($user->getDepartementsPersonnel() as $departmentAssignment) {
            $department = $departmentAssignment->getDepartement();
            if ($department === null || !$department->isActif()
                || !in_array('intranet', $departmentAssignment->getPackages(), true)
                || array_intersect(UserEffectivePermissionService::IUT_DIRECTION_ROLES, $departmentAssignment->getPermissions()) === []) continue;
            $departements[$department->getId()] = [
                'id' => $department->getId(), 'libelle' => $department->getLibelle(),
                'formations' => $department->getStructureDiplomes()->count(),
            ];
        }
        return ['departements' => array_values($departements)];
    }

    private function departmentDiplomes(Personnel $user, ?array $ids = null): array
    {
        $assignment = $this->permissions->getActiveDepartementPersonnel($user);
        $diplomes = [];
        foreach ($assignment?->getDepartement()?->getStructureDiplomes() ?? [] as $diplome) {
            if ($ids !== null && !in_array($diplome->getId(), $ids, true)) continue;
            $diplomes[] = ['id' => $diplome->getId(), 'libelle' => $diplome->getLibelle(), 'sigle' => $diplome->getSigle()];
        }
        return $diplomes;
    }

}
