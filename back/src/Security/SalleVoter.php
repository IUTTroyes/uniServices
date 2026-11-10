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

    public function __construct(
        private readonly UserEffectivePermissionService $effectivePermissionService
    ) {}

    public function supportsAttribute(string $attribute): bool
    {
        return in_array($attribute, self::SUPPORTED_ATTRIBUTES, true);
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $this->supportsAttribute($attribute);
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
            self::CAN_EDIT_SALLE => false,
            self::CAN_DELETE_SALLE => false,
            default => false,
        };
    }

    private function isSuperAdmin(Personnel|Etudiant $user): bool
    {
        return $this->effectivePermissionService->isSuperAdmin($user);
    }

    private function canViewSalle(Personnel|Etudiant $user): bool
    {
        return true; // Tout le monde peut voir les salles
    }

}

