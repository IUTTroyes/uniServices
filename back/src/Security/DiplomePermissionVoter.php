<?php

namespace App\Security;

use App\Entity\Users\Personnel;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class DiplomePermissionVoter extends Voter
{
    public function __construct(private readonly UserEffectivePermissionService $permissions) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === 'ROLE_RESP_PARCOURS';
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        return $user instanceof Personnel && (
            $this->permissions->isSuperAdmin($user)
            || $this->permissions->hasPermission($user, $attribute, $subject)
        );
    }
}
