<?php

namespace UnifolioBundle\Security;

use App\Entity\Structure\StructureDiplome;
use App\Entity\Users\Personnel;
use App\Security\UserEffectivePermissionService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use UnifolioBundle\Entity\Grille;

/**
 * Administration des grilles et du paramétrage des formations.
 * - grilles nationales (type de diplôme) : visibles par tous les référents, modifiables par le super administrateur ;
 * - grilles de département / diplôme et paramétrage : référents du département.
 *
 * @extends Voter<string, Grille|StructureDiplome>
 */
class GrilleVoter extends Voter
{
    public const VOIR = 'UNIFOLIO_GRILLE_VOIR';
    public const MODIFIER = 'UNIFOLIO_GRILLE_MODIFIER';
    public const PARAMETRER_FORMATION = 'UNIFOLIO_FORMATION_PARAMETRER';

    public function __construct(
        private readonly UserEffectivePermissionService $permissions,
        private readonly ReferentContexte $contexte,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return match ($attribute) {
            self::VOIR, self::MODIFIER => $subject instanceof Grille,
            self::PARAMETRER_FORMATION => $subject instanceof StructureDiplome,
            default => false,
        };
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof Personnel) {
            return false;
        }
        if ($this->contexte->isSuperAdmin($user)) {
            return true;
        }
        if (!$this->permissions->hasAnyPermission($user, [UnifolioRoles::REFERENT])) {
            $vote?->addReason('Réservé aux référents portfolio.');

            return false;
        }

        return match ($attribute) {
            self::VOIR => $subject->getDepartement() === null || $this->contexte->administre($user, $subject->getDepartement()),
            self::MODIFIER => $subject->getDepartement() !== null && $this->contexte->administre($user, $subject->getDepartement()),
            self::PARAMETRER_FORMATION => $this->contexte->administre($user, $subject->getDepartement()),
            default => false,
        };
    }
}
