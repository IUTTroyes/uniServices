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
        // 1. Super Admins have global access
        if ($user instanceof Personnel) {
            $superAdminInDepartment = $this->departementPersonnelRepository->findOneBy([
                'personnel' => $user,
                'departement' => $departement,
            ]);

            if ($superAdminInDepartment) {
                $resolvedPermissions = $this->resolver->resolve(
                    $superAdminInDepartment->getPermissions(),
                    $superAdminInDepartment->getPackages()
                );

                if (in_array('SUPER_ADMIN', $resolvedPermissions, true)) {
                    return true;
                }
            }
        }

        // 2. Etudiants have a generic role within their active department
        if ($user instanceof Etudiant) {
            $studentDept = $this->getStudentDepartment($user);
            if ($studentDept && $studentDept->getId() === $departement->getId()) {
                return $permission === 'ROLE_ETUDIANT';
            }
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

            $resolvedPermissions = $this->resolver->resolve($dp->getPermissions(), $dp->getPackages());

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
