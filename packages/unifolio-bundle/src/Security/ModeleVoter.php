<?php

namespace UnifolioBundle\Security;

use App\Entity\Structure\StructureDepartement;
use App\Entity\Users\Personnel;
use App\Security\UserEffectivePermissionService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use UnifolioBundle\Entity\PortfolioModele;

/**
 * Modèles de portfolio : visibles par les référents (nationaux + ceux de leur département),
 * modifiables seulement dans leur département (les nationaux par le super administrateur).
 *
 * @extends Voter<string, PortfolioModele>
 */
class ModeleVoter extends Voter
{
    public const VOIR = 'UNIFOLIO_MODELE_VOIR';
    public const MODIFIER = 'UNIFOLIO_MODELE_MODIFIER';

    public function __construct(
        private readonly UserEffectivePermissionService $permissions,
        private readonly ReferentContexte $contexte,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof PortfolioModele && \in_array($attribute, [self::VOIR, self::MODIFIER], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        \assert($subject instanceof PortfolioModele);
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
        $departement = $subject->getDepartement();

        return match ($attribute) {
            self::VOIR => $departement === null || $this->contexte->administre($user, $departement),
            default => $departement instanceof StructureDepartement && $this->contexte->administre($user, $departement),
        };
    }
}
