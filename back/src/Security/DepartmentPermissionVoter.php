<?php

namespace App\Security;

use App\Entity\Structure\StructureDepartement;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DepartmentPermissionVoter extends Voter
{
    public function __construct(
        private readonly DepartmentPermissionChecker $checker,
        private readonly PermissionRegistry $registry
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof StructureDepartement && 
            ($this->registry->getPermissionByRole($attribute) !== null || str_starts_with($attribute, 'ROLE_'));
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user) {
            return false;
        }

        return $this->checker->checkPermission($user, $subject, $attribute);
    }
}
