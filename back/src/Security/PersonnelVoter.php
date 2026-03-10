<?php

namespace App\Security;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter pour les opérations sur les entités Personnel
 * Gère les droits d'accès pour : Personnel, DepartementPersonnel, HRS
 */
class PersonnelVoter extends Voter
{
    // Permissions Personnel
    public const CAN_VIEW_PERSONNEL = 'CAN_VIEW_PERSONNEL';
    public const CAN_EDIT_PERSONNEL = 'CAN_EDIT_PERSONNEL';
    public const CAN_DELETE_PERSONNEL = 'CAN_DELETE_PERSONNEL';

    // Permissions Département-Personnel
    public const CAN_VIEW_DEPT_PERSONNEL = 'CAN_VIEW_DEPT_PERSONNEL';
    public const CAN_EDIT_DEPT_PERSONNEL = 'CAN_EDIT_DEPT_PERSONNEL';
    public const CAN_DELETE_DEPT_PERSONNEL = 'CAN_DELETE_DEPT_PERSONNEL';

    // Permissions pour gérer les rôles
    public const CAN_ASSIGN_ROLES = 'CAN_ASSIGN_ROLES';

    private const SUPPORTED_ATTRIBUTES = [
        self::CAN_VIEW_PERSONNEL,
        self::CAN_EDIT_PERSONNEL,
        self::CAN_DELETE_PERSONNEL,
        self::CAN_VIEW_DEPT_PERSONNEL,
        self::CAN_EDIT_DEPT_PERSONNEL,
        self::CAN_DELETE_DEPT_PERSONNEL,
        self::CAN_ASSIGN_ROLES,
    ];

    /**
     * @inheritDoc
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, self::SUPPORTED_ATTRIBUTES);
    }

    /**
     * @inheritDoc
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        // L'utilisateur doit être connecté
        if (!($user instanceof Etudiant || $user instanceof Personnel)) {
            $vote?->addReason('L\'utilisateur n\'est pas connecté.');
            return false;
        }

        // ROLE_SUPER_ADMIN a accès à tout
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return match($attribute) {
            // Personnel
            self::CAN_VIEW_PERSONNEL => $this->canViewPersonnel($subject, $user),
            self::CAN_EDIT_PERSONNEL => $this->canEditPersonnel($subject, $user),
            self::CAN_DELETE_PERSONNEL => $this->canDeletePersonnel($user),

            // Département-Personnel
            self::CAN_VIEW_DEPT_PERSONNEL => $this->canViewDeptPersonnel($user),
            self::CAN_EDIT_DEPT_PERSONNEL => $this->canEditDeptPersonnel($user),
            self::CAN_DELETE_DEPT_PERSONNEL => $this->canDeleteDeptPersonnel($user),

            // Rôles
            self::CAN_ASSIGN_ROLES => $this->canAssignRoles($user),

            default => false,
        };
    }

    // ========== Helpers ==========

    private function isSuperAdmin(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && in_array('ROLE_SUPER_ADMIN', $user->getRoles());
    }

    private function hasAnyRole(Personnel $user, array $roles): bool
    {
        foreach ($roles as $role) {
            if (in_array($role, $user->getRoles())) {
                return true;
            }
        }
        return false;
    }

    // ========== Personnel ==========

    private function canViewPersonnel(mixed $subject, Personnel|Etudiant $user): bool
    {
        // Le personnel peut voir les autres membres du personnel
        // Un étudiant ne peut pas voir le détail d'un personnel (sauf light)
        return $user instanceof Personnel;
    }

    private function canEditPersonnel(mixed $subject, Personnel|Etudiant $user): bool
    {
        if (!$user instanceof Personnel) {
            return false;
        }

        // Rôles avec accès complet
        if ($this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT'
        ])) {
            return true;
        }

        // Un personnel peut modifier son propre profil
        if ($subject instanceof Personnel && $subject === $user) {
            return true;
        }

        return false;
    }

    private function canDeletePersonnel(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN'
        ]);
    }

    // ========== Département-Personnel ==========

    private function canViewDeptPersonnel(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel;
    }

    private function canEditDeptPersonnel(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT'
        ]);
    }

    private function canDeleteDeptPersonnel(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT'
        ]);
    }

    // ========== Rôles ==========

    private function canAssignRoles(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN'
        ]);
    }
}


