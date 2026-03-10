<?php

namespace App\Security;

use App\Entity\Salle;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter pour les opérations sur les entités diverses
 * Gère les droits d'accès pour : Salle
 */
class SalleVoter extends Voter
{
    // Permissions Salle
    public const CAN_VIEW_SALLE = 'CAN_VIEW_SALLE';
    public const CAN_EDIT_SALLE = 'CAN_EDIT_SALLE';
    public const CAN_DELETE_SALLE = 'CAN_DELETE_SALLE';

    private const SUPPORTED_ATTRIBUTES = [
        self::CAN_VIEW_SALLE,
        self::CAN_EDIT_SALLE,
        self::CAN_DELETE_SALLE,
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
            self::CAN_VIEW_SALLE => $this->canViewSalle($user),
            self::CAN_EDIT_SALLE => $this->canEditSalle($user),
            self::CAN_DELETE_SALLE => $this->canDeleteSalle($user),
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

    private function canViewSalle(Personnel|Etudiant $user): bool
    {
        return true; // Tout le monde peut voir les salles
    }

    private function canEditSalle(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT',
            'ROLE_EDT',
            'ROLE_ASSISTANT'
        ]);
    }

    private function canDeleteSalle(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN'
        ]);
    }
}

