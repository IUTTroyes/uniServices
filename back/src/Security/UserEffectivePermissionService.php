<?php

namespace App\Security;

use App\Entity\Scolarite\ScolEnseignement;
use App\Entity\Structure\StructureDepartement;
use App\Entity\Structure\StructureDepartementPersonnel;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;

class UserEffectivePermissionService
{
    public const IUT_DIRECTION_ROLES = ['ROLE_DIRECTEUR_IUT', 'ROLE_DIRECTEUR_ADJOINT_IUT', 'ROLE_CHEF_SERVICES_ADMINISTRATIFS'];

    public function __construct(
        private readonly PermissionResolver $resolver
    ) {}

    /**
     * @return string[]
     */
    public function getEffectivePermissions(Personnel|Etudiant $user, mixed $subject = null): array
    {
        if ($user instanceof Etudiant) {
            return [];
        }

        $assignment = $this->getActiveDepartementPersonnel($user);
        if ($assignment === null) {
            return [];
        }

        $permissions = $this->resolver->resolveForDepartment($assignment, DiplomeScope::fromSubject($subject));
        if ($subject === null && !in_array('SUPER_ADMIN', $permissions, true) && $this->isSuperAdmin($user)) {
            $permissions[] = 'SUPER_ADMIN';
        }

        return $permissions;
    }

    public function hasPermission(Personnel|Etudiant $user, string $permission, mixed $subject = null): bool
    {
        return $permission === 'SUPER_ADMIN'
            ? $this->isSuperAdmin($user)
            : in_array($permission, $this->getEffectivePermissions($user, $subject), true);
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

    public function isTeacher(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasPermission($user, 'ROLE_TEACHER');
    }

    public function isBiatss(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasPermission($user, 'ROLE_BIATSS');
    }

    public function isDirectionIut(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyPermission($user, self::IUT_DIRECTION_ROLES);
    }

    public function isSuperAdmin(Personnel|Etudiant $user): bool
    {
        if (!$user instanceof Personnel) {
            return false;
        }

        // Une affectation qui donne ce droit l’ouvre dans toute l’application.
        foreach ($user->getDepartementsPersonnel() as $assignment) {
            if (in_array('SUPER_ADMIN', $this->resolver->resolveForDepartment($assignment), true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Pour une écriture locale, l’affectation doit appartenir au département de la ressource.
     *
     * @param string[] $roles
     */
    public function hasAnyPermissionInDepartment(
        Personnel $user,
        array $roles,
        ?StructureDepartement $department,
        mixed $subject = null,
    ): bool {
        if ($department === null) {
            return false;
        }

        $diploma = DiplomeScope::fromSubject($subject);
        foreach ($user->getDepartementsPersonnel() as $assignment) {
            $assignedDepartment = $assignment->getDepartement();
            if ($assignedDepartment !== $department
                && ($department->getId() === null || $assignedDepartment?->getId() !== $department->getId())) {
                continue;
            }

            if (array_intersect($roles, $this->resolver->resolveForDepartment($assignment, $diploma)) !== []) {
                return true;
            }
        }

        return false;
    }

    /** @param string[] $roles */
    public function hasAnyPermissionForResource(
        Personnel $user,
        array $roles,
        mixed $subject,
    ): bool {
        $department = DepartmentScope::fromSubject($subject);

        // Une nouvelle entité locale sans rattachement utilise l’affectation active.
        // Une entité persistée sans périmètre reste réservée au super administrateur.
        if ($department === null && is_object($subject) && is_callable([$subject, 'getId']) && $subject->getId() === null) {
            $department = $this->getActiveDepartementPersonnel($user)?->getDepartement();
        }

        return $this->hasAnyPermissionInDepartment($user, $roles, $department, $subject);
    }

    /** @param string[] $roles */
    public function hasAnyPermissionForTeaching(Personnel $user, ScolEnseignement $teaching, array $roles): bool
    {
        // getDiplome() sur l’enseignement ne représente que sa première UE.
        $links = $teaching->getEnseignementUes();
        if ($links->isEmpty()) {
            return $teaching->getId() === null && $this->hasAnyPermissionInDepartment(
                $user, $roles, $this->getActiveDepartementPersonnel($user)?->getDepartement(),
            );
        }

        foreach ($links as $link) {
            if (!$this->hasAnyPermissionInDepartment(
                $user, $roles, DepartmentScope::fromSubject($link), $link,
            )) {
                return false;
            }
        }

        return true;
    }

    public function getActiveDepartementPersonnel(Personnel $user): ?StructureDepartementPersonnel
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
