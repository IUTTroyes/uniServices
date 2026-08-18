<?php

namespace App\Security;

use App\Entity\Structure\StructureDepartementPersonnel;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;

class UserEffectivePermissionService
{
    public function __construct(
        private readonly PermissionResolver $resolver
    ) {}

    /**
     * @return string[]
     */
    public function getEffectivePermissions(Personnel|Etudiant $user): array
    {
        if ($user instanceof Etudiant) {
            return ['ROLE_ETUDIANT'];
        }

        $activeDepartementPersonnel = $this->getActiveDepartementPersonnel($user);
        if (null === $activeDepartementPersonnel) {
            return [];
        }

        return $this->resolver->resolve(
            $activeDepartementPersonnel->getPermissions(),
            $activeDepartementPersonnel->getPackages()
        );
    }

    public function hasPermission(Personnel|Etudiant $user, string $permission): bool
    {
        return in_array($permission, $this->getEffectivePermissions($user), true);
    }

    /**
     * @param string[] $permissions
     */
    public function hasAnyPermission(Personnel $user, array $permissions): bool
    {
        $effectivePermissions = $this->getEffectivePermissions($user);

        foreach ($permissions as $permission) {
            if (in_array($permission, $effectivePermissions, true)) {
                return true;
            }
        }

        return false;
    }

    public function isSuperAdmin(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasPermission($user, 'SUPER_ADMIN');
    }

    private function getActiveDepartementPersonnel(Personnel $user): ?StructureDepartementPersonnel
    {
        foreach ($user->getDepartementsPersonnel() as $departementPersonnel) {
            if ($departementPersonnel->isDefaut()) {
                return $departementPersonnel;
            }
        }

        $first = $user->getDepartementsPersonnel()->first();

        return false !== $first ? $first : null;
    }
}
