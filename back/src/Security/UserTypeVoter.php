<?php

namespace App\Security;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/** Account types are identity predicates, never assignable roles. */
final class UserTypeVoter extends Voter
{
    public const IS_PERSONNEL = 'IS_PERSONNEL';
    public const IS_ETUDIANT = 'IS_ETUDIANT';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::IS_PERSONNEL, self::IS_ETUDIANT], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        return match ($attribute) {
            self::IS_PERSONNEL => $token->getUser() instanceof Personnel,
            self::IS_ETUDIANT => $token->getUser() instanceof Etudiant,
            default => false,
        };
    }
}
