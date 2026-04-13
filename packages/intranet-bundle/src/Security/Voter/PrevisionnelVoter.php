<?php

namespace IntranetBundle\Security\Voter;

use IntranetBundle\Entity\Previsionnel\Previsionnel;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter pour les opérations sur les entités Prévisionnel
 * Gère les droits d'accès pour la gestion des services prévisionnels des enseignants
 */
class PrevisionnelVoter extends Voter
{
    // Permissions Prévisionnel
    public const CAN_VIEW_PREVISIONNEL = 'CAN_VIEW_PREVISIONNEL';
    public const CAN_EDIT_PREVISIONNEL = 'CAN_EDIT_PREVISIONNEL';
    public const CAN_DELETE_PREVISIONNEL = 'CAN_DELETE_PREVISIONNEL';

    // Permissions pour gérer les heures complémentaires (HRS)
    public const CAN_VIEW_HRS = 'CAN_VIEW_HRS';
    public const CAN_EDIT_HRS = 'CAN_EDIT_HRS';
    public const CAN_DELETE_HRS = 'CAN_DELETE_HRS';

    private const SUPPORTED_ATTRIBUTES = [
        self::CAN_VIEW_PREVISIONNEL,
        self::CAN_EDIT_PREVISIONNEL,
        self::CAN_DELETE_PREVISIONNEL,
        self::CAN_VIEW_HRS,
        self::CAN_EDIT_HRS,
        self::CAN_DELETE_HRS,
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
            // Prévisionnel
            self::CAN_VIEW_PREVISIONNEL => $this->canViewPrevisionnel($subject, $user),
            self::CAN_EDIT_PREVISIONNEL => $this->canEditPrevisionnel($subject, $user),
            self::CAN_DELETE_PREVISIONNEL => $this->canDeletePrevisionnel($user),

            // Heures complémentaires
            self::CAN_VIEW_HRS => $this->canViewHrs($user),
            self::CAN_EDIT_HRS => $this->canEditHrs($user),
            self::CAN_DELETE_HRS => $this->canDeleteHrs($user),

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

    // ========== Prévisionnel ==========

    private function canViewPrevisionnel(mixed $subject, Personnel|Etudiant $user): bool
    {
        if (!$user instanceof Personnel) {
            return false;
        }

        // Rôles avec accès complet
        if ($this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT',
            'ROLE_DIRECTEUR_ETUDES',
            'ROLE_RESP_PARCOURS',
            'ROLE_ASSISTANT'
        ])) {
            return true;
        }

        // Un personnel peut voir son propre prévisionnel
        if ($subject instanceof Previsionnel && $subject->getPersonnel() === $user) {
            return true;
        }

        // Tous les permanents peuvent voir les prévisionnels
        if ($this->hasAnyRole($user, ['ROLE_PERMANENT'])) {
            return true;
        }

        return false;
    }

    private function canEditPrevisionnel(mixed $subject, Personnel|Etudiant $user): bool
    {
        if (!$user instanceof Personnel) {
            return false;
        }

        // Rôles avec accès complet
        if ($this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT',
            'ROLE_DIRECTEUR_ETUDES'
        ])) {
            return true;
        }

        // Responsable de parcours peut modifier les prévisionnels de son parcours
        if ($this->hasAnyRole($user, ['ROLE_RESP_PARCOURS'])) {
            return true;
        }

        return false;
    }

    private function canDeletePrevisionnel(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT',
            'ROLE_DIRECTEUR_ETUDES'
        ]);
    }

    // ========== Heures Complémentaires (HRS) ==========

    private function canViewHrs(Personnel|Etudiant $user): bool
    {
        if (!$user instanceof Personnel) {
            return false;
        }

        // Rôles avec accès complet
        if ($this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT',
            'ROLE_DIRECTEUR_ETUDES',
            'ROLE_RESP_PARCOURS',
            'ROLE_ASSISTANT',
            'ROLE_COMPTABILITE'
        ])) {
            return true;
        }

        // Un personnel peut voir ses propres heures
        return $this->hasAnyRole($user, ['ROLE_PERMANENT']);
    }

    private function canEditHrs(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT',
            'ROLE_DIRECTEUR_ETUDES',
            'ROLE_COMPTABILITE'
        ]);
    }

    private function canDeleteHrs(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT'
        ]);
    }
}

