<?php

namespace DocumentBundle\Security;

use App\Entity\Users\Personnel;
use App\Security\UserEffectivePermissionService;
use DocumentBundle\Entity\Document;
use DocumentBundle\Entity\DocumentCategory;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class DocumentVoter extends Voter
{
    public const MANAGE = 'CAN_MANAGE_DOCUMENT';

    public function __construct(private readonly UserEffectivePermissionService $permissions) {}

    public function supportsAttribute(string $attribute): bool
    {
        return $attribute === self::MANAGE;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $this->supportsAttribute($attribute)
            && ($subject instanceof Document || $subject instanceof DocumentCategory);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof Personnel) {
            return false;
        }

        if ($this->permissions->isSuperAdmin($user)) {
            return true;
        }

        // Un document ou une catégorie sans département appartient au référentiel institutionnel.
        $roles = ['ROLE_ADMIN', 'ROLE_CHEF_DEPT'];
        if (!$this->permissions->hasAnyPermissionInDepartment($user, $roles, $subject->getDepartement())) {
            return false;
        }

        $category = $subject instanceof Document ? $subject->getCategory() : $subject->getParent();

        // Un rattachement à une catégorie d’un autre département exige aussi ses droits.
        return $category?->getDepartement() === null
            || $this->permissions->hasAnyPermissionInDepartment($user, $roles, $category->getDepartement());
    }
}
