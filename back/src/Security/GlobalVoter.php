<?php

namespace App\Security;

use App\Entity\Users\Personnel;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;


class GlobalVoter extends Voter
{
    public const IS_SUPER_ADMIN = 'IS_SUPER_ADMIN';
    public const SUPER_ADMIN = 'SUPER_ADMIN';
    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    private const SUPPORTED_ATTRIBUTES = [
        self::IS_SUPER_ADMIN,
        self::SUPER_ADMIN,
        self::ROLE_SUPER_ADMIN,
    ];

    public function __construct(
        private readonly UserEffectivePermissionService $effectivePermissionService
    ) {}

    public function supportsAttribute(string $attribute): bool
    {
        return in_array($attribute, self::SUPPORTED_ATTRIBUTES, true);
    }

    /**
     * @inheritDoc
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $this->supportsAttribute($attribute);
    }

    /**
     * @inheritDoc
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        return $user instanceof Personnel && $this->effectivePermissionService->isSuperAdmin($user);
    }
}
