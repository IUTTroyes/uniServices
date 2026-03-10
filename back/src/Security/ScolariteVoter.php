<?php

namespace App\Security;

use App\Entity\Scolarite\ScolBac;
use App\Entity\Scolarite\ScolEnseignement;
use App\Entity\Scolarite\ScolEnseignementUe;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter pour les opérations sur les entités Scolarité (hors évaluations/notes)
 * Gère les droits d'accès pour : Enseignement, EnseignementUe, Bac
 */
class ScolariteVoter extends Voter
{
    // Permissions Enseignement
    public const CAN_VIEW_ENSEIGNEMENT = 'CAN_VIEW_ENSEIGNEMENT';
    public const CAN_EDIT_ENSEIGNEMENT = 'CAN_EDIT_ENSEIGNEMENT';
    public const CAN_DELETE_ENSEIGNEMENT = 'CAN_DELETE_ENSEIGNEMENT';

    // Permissions EnseignementUe
    public const CAN_VIEW_ENSEIGNEMENT_UE = 'CAN_VIEW_ENSEIGNEMENT_UE';
    public const CAN_EDIT_ENSEIGNEMENT_UE = 'CAN_EDIT_ENSEIGNEMENT_UE';
    public const CAN_DELETE_ENSEIGNEMENT_UE = 'CAN_DELETE_ENSEIGNEMENT_UE';

    // Permissions Bac
    public const CAN_VIEW_BAC = 'CAN_VIEW_BAC';
    public const CAN_EDIT_BAC = 'CAN_EDIT_BAC';
    public const CAN_DELETE_BAC = 'CAN_DELETE_BAC';

    private const SUPPORTED_ATTRIBUTES = [
        self::CAN_VIEW_ENSEIGNEMENT,
        self::CAN_EDIT_ENSEIGNEMENT,
        self::CAN_DELETE_ENSEIGNEMENT,
        self::CAN_VIEW_ENSEIGNEMENT_UE,
        self::CAN_EDIT_ENSEIGNEMENT_UE,
        self::CAN_DELETE_ENSEIGNEMENT_UE,
        self::CAN_VIEW_BAC,
        self::CAN_EDIT_BAC,
        self::CAN_DELETE_BAC,
    ];

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, self::SUPPORTED_ATTRIBUTES);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!($user instanceof Etudiant || $user instanceof Personnel)) {
            $vote?->addReason('L\'utilisateur n\'est pas connecté.');
            return false;
        }

        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return match($attribute) {
            // Enseignement
            self::CAN_VIEW_ENSEIGNEMENT => $this->canViewEnseignement($user),
            self::CAN_EDIT_ENSEIGNEMENT => $this->canEditEnseignement($user),
            self::CAN_DELETE_ENSEIGNEMENT => $this->canDeleteEnseignement($user),

            // EnseignementUe
            self::CAN_VIEW_ENSEIGNEMENT_UE => $this->canViewEnseignementUe($user),
            self::CAN_EDIT_ENSEIGNEMENT_UE => $this->canEditEnseignementUe($user),
            self::CAN_DELETE_ENSEIGNEMENT_UE => $this->canDeleteEnseignementUe($user),

            // Bac
            self::CAN_VIEW_BAC => $this->canViewBac($user),
            self::CAN_EDIT_BAC => $this->canEditBac($user),
            self::CAN_DELETE_BAC => $this->canDeleteBac($user),

            default => false,
        };
    }

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

    // ========== Enseignement ==========

    private function canViewEnseignement(Personnel|Etudiant $user): bool
    {
        return true; // Tout le monde peut voir les enseignements
    }

    private function canEditEnseignement(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT',
            'ROLE_RESP_PARCOURS',
            'ROLE_DIRECTEUR_ETUDES'
        ]);
    }

    private function canDeleteEnseignement(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN'
        ]);
    }

    // ========== EnseignementUe ==========

    private function canViewEnseignementUe(Personnel|Etudiant $user): bool
    {
        return true;
    }

    private function canEditEnseignementUe(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT',
            'ROLE_RESP_PARCOURS'
        ]);
    }

    private function canDeleteEnseignementUe(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN'
        ]);
    }

    // ========== Bac ==========

    private function canViewBac(Personnel|Etudiant $user): bool
    {
        return true;
    }

    private function canEditBac(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_SCOLARITE'
        ]);
    }

    private function canDeleteBac(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN'
        ]);
    }
}

