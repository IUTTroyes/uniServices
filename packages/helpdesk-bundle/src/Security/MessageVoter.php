<?php

namespace HelpdeskBundle\Security;

use HelpdeskBundle\Entity\HelpdeskMessage;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter pour les opérations sur les entités diverses
 * Gère les droits d'accès pour : MESSAGE
 */
class MessageVoter extends Voter
{
    // Permissions Message
    public const CAN_VIEW_MESSAGE = 'CAN_VIEW_MESSAGE';
    public const CAN_EDIT_MESSAGE = 'CAN_EDIT_MESSAGE';
    public const CAN_DELETE_MESSAGE = 'CAN_DELETE_MESSAGE';
    public const CAN_CREATE_MESSAGE= 'CAN_CREATE_MESSAGE';

    private const SUPPORTED_ATTRIBUTES = [
        self::CAN_VIEW_MESSAGE,
        self::CAN_EDIT_MESSAGE,
        self::CAN_DELETE_MESSAGE,
        self::CAN_CREATE_MESSAGE,
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
            self::CAN_VIEW_MESSAGE => $this->canViewMessage($user,$subject),
            self::CAN_CREATE_MESSAGE => $this->canCreateMessage($user),
            self::CAN_EDIT_MESSAGE => $this->canEditMessage($user,$subject),
            self::CAN_DELETE_MESSAGE => $this->canDeleteMessage($user,$subject),
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

    private function canViewMessage(Personnel|Etudiant $user, mixed $subject): bool
    {
        if (!$subject instanceof HelpdeskMessage) {
            return $user instanceof Personnel;
        }
        if ($subject->getAuteur() && $user->getId() === $subject->getAuteur()->getId()) {
            return true;
        }

        return (!$user instanceof Etudiant);
    }

    private function canCreateMessage(Personnel|Etudiant $user):bool
    {
        return (!$user instanceof Etudiant);
    }

    private function canEditMessage(Personnel|Etudiant $user,HelpdeskMESSAGE $subject): bool
    {
        if($user instanceof Etudiant){
            return false;
        }
        return $user->getId() === $subject->getAuteur()->getId();

    }

    private function canDeleteMessage(Personnel|Etudiant $user, mixed $subject): bool
    {
        if (!$subject instanceof HelpdeskMessage) {
            return $user instanceof Personnel;
        }

        if ($user instanceof Etudiant) {
            return false;
        }
        return $subject->getAuteur() && $user->getId() === $subject->getAuteur()->getId();
    }
}

