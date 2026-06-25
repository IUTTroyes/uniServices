<?php

namespace HelpdeskBundle\Security;

use HelpdeskBundle\Entity\HelpdeskCategorie;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter pour les opérations sur les entités diverses
 * Gère les droits d'accès pour : CATEGORIE
 */
class CategorieVoter extends Voter
{
    // Permissions CATEGORIE
    public const CAN_VIEW_CATEGORIE = 'CAN_VIEW_CATEGORIE';
    public const CAN_EDIT_CATEGORIE = 'CAN_EDIT_CATEGORIE';
    public const CAN_DELETE_CATEGORIE = 'CAN_DELETE_CATEGORIE';
    public const CAN_CREATE_CATEGORIE= 'CAN_CREATE_CATEGORIE';

    private const SUPPORTED_ATTRIBUTES = [
        self::CAN_VIEW_CATEGORIE,
        self::CAN_EDIT_CATEGORIE,
        self::CAN_DELETE_CATEGORIE,
        self::CAN_CREATE_CATEGORIE,
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
            self::CAN_VIEW_CATEGORIE => $this->canViewCategorie($user,$subject),
            self::CAN_CREATE_CATEGORIE => $this->canCreateCategorie($user),
            self::CAN_EDIT_CATEGORIE => $this->canEditCategorie($user,$subject),
            self::CAN_DELETE_CATEGORIE => $this->canDeleteCategorie($user,$subject),
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

    private function canViewCategorie(Personnel|Etudiant $user): bool
    {
        return  (!$user instanceof Etudiant);
    }

    private function canCreateCategorie(Personnel|Etudiant $user):bool
    {
        return (!$user instanceof Etudiant);
    }

    private function canEditCategorie(Personnel|Etudiant $user,HelpdeskCATEGORIE $subject): bool
    {
        if($user instanceof Etudiant){
            return false;
        }
        return $user->getId() === $subject->getAuteur()->getId();

    }

    private function canDeleteCategorie(Personnel|Etudiant $user,HelpdeskCATEGORIE $subject): bool
    {
        if($user instanceof Etudiant){
            return false;
        }
        return $user->getId() === $subject->getAuteur()->getId();
    }
}

