<?php

namespace App\Security;

use App\Entity\Structure\StructureDepartement;
use App\Entity\Users\Personnel;
use App\Entity\Users\Etudiant;
use App\Repository\Structure\StructureDepartementPersonnelRepository;

class DepartmentPermissionChecker
{
    public function __construct(
        private readonly StructureDepartementPersonnelRepository $departementPersonnelRepository,
        private readonly PermissionResolver $resolver
    ) {}

    public function checkPermission(Personnel|Etudiant $user, StructureDepartement $departement, string $permission): bool
    {
        if (in_array($permission, ['ROLE_PERSONNEL', 'ROLE_ETUDIANT'], true)) return false;

        // 1. Super Admins have global access
        if ($user instanceof Personnel) {
            $superAdminInDepartment = $this->departementPersonnelRepository->findOneBy([
                'personnel' => $user,
                'departement' => $departement,
            ]);

            if ($superAdminInDepartment) {
                $resolvedPermissions = $this->resolver->resolveForDepartment($superAdminInDepartment);

                if (in_array('SUPER_ADMIN', $resolvedPermissions, true)) {
                    return true;
                }
            }
        }

        // Identity is checked by UserTypeVoter; students have no personnel business roles.
        if ($user instanceof Etudiant) {
            return false;
        }

        // 3. Personnel have resolved rights within a department
        if ($user instanceof Personnel) {
            $dp = $this->departementPersonnelRepository->findOneBy([
                'personnel' => $user,
                'departement' => $departement
            ]);

            if (!$dp) {
                return false;
            }

            $resolvedPermissions = $this->resolver->resolveForDepartment($dp);

            return in_array($permission, $resolvedPermissions, true);
        }

        return false;
    }

    public function getStudentDepartment(Etudiant $etudiant): ?StructureDepartement
    {
        foreach ($etudiant->getScolarites() as $scolarite) {
            if ($scolarite->isActif()) {
                return $scolarite->getDepartement();
            }
        }
        return null;
    }
}
