<?php

namespace UnifolioBundle\Security;

use App\Entity\Structure\StructureDiplome;
use App\Entity\Users\Personnel;
use App\Security\UserEffectivePermissionService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use UnifolioBundle\Entity\Campagne;

/**
 * Gestion d'une campagne (calendrier, affectations) : référent portfolio du département de la formation.
 *
 * @extends Voter<string, Campagne|StructureDiplome>
 */
class CampagneVoter extends Voter
{
    public const GERER = 'UNIFOLIO_CAMPAGNE_GERER';

    public function __construct(
        private readonly UserEffectivePermissionService $permissions,
        private readonly ReferentContexte $contexte,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::GERER && ($subject instanceof Campagne || $subject instanceof StructureDiplome);
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
        $diplome = $subject instanceof Campagne ? $subject->getDiplome() : $subject;

        return $this->contexte->administre($user, $diplome->getDepartement());
    }
}
