<?php

namespace App\Security;

use App\Entity\Structure\StructureDepartement;
use App\Entity\Users\Personnel;
use App\Entity\Users\Etudiant;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DepartmentPermissionVoter extends Voter
{
    public function __construct(
        private readonly DepartmentPermissionChecker $checker,
        private readonly PermissionRegistry $registry,
        private readonly UserEffectivePermissionService $permissions
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return ($subject === null || $subject instanceof StructureDepartement) &&
            ($this->registry->getPermissionByRole($attribute) !== null || str_starts_with($attribute, 'ROLE_'));
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof Personnel && !$user instanceof Etudiant) {
            return false;
        }

        if ($subject === null) {
            return $this->permissions->isSuperAdmin($user) || $this->permissions->hasPermission($user, $attribute);
        }

        return $this->checker->checkPermission($user, $subject, $attribute);
    }
}
