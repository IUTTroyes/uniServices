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

        if (!$user instanceof Personnel) {
            return false;
        }

        // Ce contrôle utilise l’affectation du département demandé.
        $assignment = $this->departementPersonnelRepository->findOneBy([
            'personnel' => $user,
            'departement' => $departement,
        ]);
        if ($assignment === null) {
            return false;
        }

        $permissions = $this->resolver->resolveForDepartment($assignment);

        return in_array('SUPER_ADMIN', $permissions, true) || in_array($permission, $permissions, true);
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
