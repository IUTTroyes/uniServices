<?php

namespace UnifolioBundle\Security;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Security\UserEffectivePermissionService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use UnifolioBundle\Entity\Trace;
use UnifolioBundle\Enum\StatutTraceEnum;

/**
 * Droits sur une trace.
 * Les correcteurs verront les traces de leurs étudiants affectés à partir du lot 4 (campagnes).
 *
 * @extends Voter<string, Trace>
 */
class TraceVoter extends Voter
{
    public const VOIR = 'UNIFOLIO_TRACE_VOIR';
    public const MODIFIER = 'UNIFOLIO_TRACE_MODIFIER';
    public const SUPPRIMER = 'UNIFOLIO_TRACE_SUPPRIMER';
    public const DEPOSER = 'UNIFOLIO_TRACE_DEPOSER';

    private const ATTRIBUTS = [self::VOIR, self::MODIFIER, self::SUPPRIMER, self::DEPOSER];

    public function __construct(
        private readonly UserEffectivePermissionService $permissions,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Trace && \in_array($attribute, self::ATTRIBUTS, true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        \assert($subject instanceof Trace);

        if ($user instanceof Etudiant) {
            if (!$subject->appartientA($user)) {
                $vote?->addReason('Cette trace appartient à un autre étudiant.');

                return false;
            }

            return match ($attribute) {
                self::VOIR => true,
                self::MODIFIER => $subject->isModifiable(),
                self::SUPPRIMER => $subject->getStatut() === StatutTraceEnum::BROUILLON,
                self::DEPOSER => $subject->getStatut()->peutPasserA(StatutTraceEnum::DEPOSEE),
                default => false,
            };
        }

        if ($user instanceof Personnel && $attribute === self::VOIR) {
            return $this->permissions->isSuperAdmin($user)
                || $this->permissions->hasAnyPermission($user, [UnifolioRoles::REFERENT]);
        }

        return false;
    }
}
