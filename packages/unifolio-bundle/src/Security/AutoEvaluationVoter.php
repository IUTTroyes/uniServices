<?php

namespace UnifolioBundle\Security;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Security\UserEffectivePermissionService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use UnifolioBundle\Entity\AutoEvaluation;

/**
 * Droits sur une auto-évaluation. Les correcteurs la verront à partir du lot 4 (affectations).
 *
 * @extends Voter<string, AutoEvaluation>
 */
class AutoEvaluationVoter extends Voter
{
    public const VOIR = 'UNIFOLIO_AUTOEVAL_VOIR';
    public const MODIFIER = 'UNIFOLIO_AUTOEVAL_MODIFIER';
    public const REPRENDRE = 'UNIFOLIO_AUTOEVAL_REPRENDRE';
    public const SUPPRIMER = 'UNIFOLIO_AUTOEVAL_SUPPRIMER';

    public function __construct(
        private readonly UserEffectivePermissionService $permissions,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof AutoEvaluation
            && \in_array($attribute, [self::VOIR, self::MODIFIER, self::REPRENDRE, self::SUPPRIMER], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        \assert($subject instanceof AutoEvaluation);
        $user = $token->getUser();

        if ($user instanceof Etudiant) {
            if (!$subject->appartientA($user)) {
                $vote?->addReason('Cette auto-évaluation appartient à un autre étudiant.');

                return false;
            }

            return match ($attribute) {
                self::VOIR => true,
                self::MODIFIER, self::SUPPRIMER => $subject->isModifiable(),
                self::REPRENDRE => !$subject->isModifiable(),
                default => false,
            };
        }

        return $user instanceof Personnel && $attribute === self::VOIR
            && ($this->permissions->isSuperAdmin($user) || $this->permissions->hasAnyPermission($user, [UnifolioRoles::REFERENT]));
    }
}
