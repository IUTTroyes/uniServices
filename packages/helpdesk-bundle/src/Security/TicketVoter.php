<?php

namespace HelpdeskBundle\Security;

use HelpdeskBundle\Entity\HelpdeskTicket;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter pour les opérations sur les entités diverses
 * Gère les droits d'accès pour : TICKET
 */
class TicketVoter extends Voter
{
    // Permissions TICKET
    public const CAN_VIEW_TICKET = 'CAN_VIEW_TICKET';
    public const CAN_EDIT_TICKET = 'CAN_EDIT_TICKET';
    public const CAN_DELETE_TICKET = 'CAN_DELETE_TICKET';
    public const CAN_CREATE_TICKET= 'CAN_CREATE_TICKET';

    private const SUPPORTED_ATTRIBUTES = [
        self::CAN_VIEW_TICKET,
        self::CAN_EDIT_TICKET,
        self::CAN_DELETE_TICKET,
        self::CAN_CREATE_TICKET,
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
            self::CAN_VIEW_TICKET => $this->canViewTicket($user),
            self::CAN_CREATE_TICKET => $this->canCreateTicket($user),
            self::CAN_EDIT_TICKET => $this->canEditTicket($user,$subject),
            self::CAN_DELETE_TICKET => $this->canDeleteTicket($user,$subject),
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

    private function canViewTicket(Personnel|Etudiant $user): bool
    {
        return  (!$user instanceof Etudiant);
    }

    private function canCreateTicket(Personnel|Etudiant $user):bool
    {
        return (!$user instanceof Etudiant);
    }

    private function canEditTicket(Personnel|Etudiant $user,HelpdeskTicket $subject): bool
    {
        if($user instanceof Etudiant){
            return false;
        }
        return $user->getId() === $subject->getAuteur()->getId();

    }

    private function canDeleteTicket(Personnel|Etudiant $user,HelpdeskTicket $subject): bool
    {
        if($user instanceof Etudiant){
            return false;
        }
        return $user->getId() === $subject->getAuteur()->getId();
    }
}

