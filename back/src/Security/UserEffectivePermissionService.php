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
    public function getEffectivePermissions(Personnel|Etudiant $user, mixed $subject = null): array
    {
        if ($user instanceof Etudiant) {
            return ['ROLE_ETUDIANT'];
        }

        $activeDepartementPersonnel = $this->getActiveDepartementPersonnel($user);
        if (null === $activeDepartementPersonnel) {
            return [];
        }

        return $this->resolver->resolveForDepartment($activeDepartementPersonnel, DiplomeScope::fromSubject($subject));
    }

    public function hasPermission(Personnel|Etudiant $user, string $permission, mixed $subject = null): bool
    {
        return in_array($permission, $this->getEffectivePermissions($user, $subject), true);
    }

    /**
     * @param string[] $permissions
     */
    public function hasAnyPermission(Personnel $user, array $permissions, mixed $subject = null): bool
    {
        $effectivePermissions = $this->getEffectivePermissions($user, $subject);

        foreach ($permissions as $permission) {
            if (in_array($permission, $effectivePermissions, true)) {
                return true;
            }
        }

        return false;
    }

    /** Responsibilities are metadata for navigation, never unscoped authorization. */
    public function getScopedPermissions(Personnel|Etudiant $user): array
    {
        if (!$user instanceof Personnel) return [];
        $assignment = $this->getActiveDepartementPersonnel($user);
        if ($assignment === null || !$assignment->hasValidResponsableParcoursDiplomes()
            || !in_array('ROLE_RESP_PARCOURS', $assignment->getPermissions(), true)
            || !in_array('intranet', $assignment->getPackages(), true)) {
            return [];
        }
        return ['ROLE_RESP_PARCOURS' => ['diplomeIds' => array_values(array_map(
            static fn ($diplome) => $diplome->getId(),
            $assignment->getResponsableParcoursDiplomes()->toArray()
        ))]];
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
