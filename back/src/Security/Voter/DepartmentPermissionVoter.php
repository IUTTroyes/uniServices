<?php

namespace App\Security\Voter;

use App\Entity\Users\Personnel;
use App\Entity\Structure\StructureDepartement;
use App\Security\Permission\DepartmentPermissionChecker;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class DepartmentPermissionVoter extends Voter
{
    public function __construct(
        private readonly DepartmentPermissionChecker $checker,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return str_starts_with($attribute, 'ROLE_')
            && $subject instanceof StructureDepartement;
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
    ): bool {
        $user = $token->getUser();

        if (!$user instanceof Personnel) {
            return false;
        }

        return $this->checker->hasPermission(
            personnel: $user,
            structureDepartement: $subject,
            role: $attribute,
        );
    }
}