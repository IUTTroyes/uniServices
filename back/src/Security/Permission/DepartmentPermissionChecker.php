<?php

namespace App\Security\Permission;

use App\Entity\Structure\StructureDepartement;
use App\Entity\Users\Personnel;
use App\Repository\Structure\StructureDepartementPersonnelRepository;

final readonly class DepartmentPermissionChecker
{
    public function __construct(
        private StructureDepartementPersonnelRepository $personnelDepartementRepository,
        private PermissionResolver $permissionResolver,
    ) {}

    public function hasPermission(
        Personnel $personnel,
        StructureDepartement $structureDepartement,
        string $role,
    ): bool {
        $personnelDepartement = $this->personnelDepartementRepository->findOneForPersonnelAndDepartement($personnel, $structureDepartement);

        if (!$personnelDepartement) {
            return false;
        }

        $roles = $personnelDepartement->getPermissions();

        $resolvedRoles = $this->permissionResolver->resolve($roles); // permet de calculer les rôles hérités

        return in_array($role, $resolvedRoles, true);
    }
}
