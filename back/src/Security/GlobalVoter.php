<?php

namespace App\Security;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;


class GlobalVoter extends Voter
{
    public const IS_SUPER_ADMIN = 'IS_SUPER_ADMIN';

    private const SUPPORTED_ATTRIBUTES = [
        self::IS_SUPER_ADMIN,
    ];

    public function __construct(
        private readonly UserEffectivePermissionService $effectivePermissionService
    ) {}

    /**
     * @inheritDoc
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, self::SUPPORTED_ATTRIBUTES);
    }

    /**
     * @inheritDoc
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        // L'utilisateur doit être connecté
        if (!($user instanceof Etudiant || $user instanceof Personnel)) {
            $vote?->addReason('L\'utilisateur n\'est pas connecté.');
            return false;
        }

        // SUPER_ADMIN a accès à tout
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return match($attribute) {
            self::IS_SUPER_ADMIN => $this->isSuperAdmin($user),
            default => false,
        };
    }

    // ========== Helpers ==========
    private function hasAnyRole(Personnel $user, array $roles): bool
    {
        return $this->effectivePermissionService->hasAnyPermission($user, $roles);
    }

    private function isSuperAdmin(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
                'SUPER_ADMIN',
            ]);
    }
}


