<?php

namespace UnifolioBundle\Security;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Security\UserEffectivePermissionService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use UnifolioBundle\Entity\Portfolio;
use UnifolioBundle\Service\Perimetre\UnifolioPerimetre;

/**
 * Droits sur un portfolio : l'étudiant gère le sien ; un référent du département le consulte.
 * La consultation par les évaluateurs passera par la publication soumise (lot 6), jamais par le brouillon.
 *
 * @extends Voter<string, Portfolio>
 */
class PortfolioVoter extends Voter
{
    public const VOIR = 'UNIFOLIO_PORTFOLIO_VOIR';
    public const MODIFIER = 'UNIFOLIO_PORTFOLIO_MODIFIER';

    public function __construct(
        private readonly UserEffectivePermissionService $permissions,
        private readonly ReferentContexte $contexte,
        private readonly UnifolioPerimetre $perimetre,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Portfolio && \in_array($attribute, [self::VOIR, self::MODIFIER], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        \assert($subject instanceof Portfolio);
        $user = $token->getUser();

        if ($user instanceof Etudiant) {
            if (!$subject->appartientA($user)) {
                $vote?->addReason('Ce portfolio appartient à un autre étudiant.');

                return false;
            }

            return true;
        }

        if ($user instanceof Personnel && $attribute === self::VOIR) {
            if ($this->contexte->isSuperAdmin($user)) {
                return true;
            }
            $diplome = $subject->getScolarite() !== null ? $this->perimetre->getDiplome($subject->getScolarite()) : null;

            return $diplome !== null
                && $this->permissions->hasAnyPermission($user, [UnifolioRoles::REFERENT])
                && $this->contexte->administre($user, $diplome->getDepartement());
        }

        return false;
    }
}
