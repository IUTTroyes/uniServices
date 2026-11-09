<?php

namespace UnifolioBundle\Security;

use App\Entity\Structure\StructureDepartement;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Security\UserEffectivePermissionService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use UnifolioBundle\Entity\PortfolioPublication;
use UnifolioBundle\Service\Perimetre\UnifolioPerimetre;

/** Modération d'une publication : référents et publicateurs du département de l'étudiant, ou super administrateur. */
class PublicationVoter extends Voter
{
    public const MODERER = 'UNIFOLIO_PUBLICATION_MODERER';

    public function __construct(
        private readonly UserEffectivePermissionService $permissions,
        private readonly ReferentContexte $contexte,
        private readonly UnifolioPerimetre $perimetre,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::MODERER && $subject instanceof PortfolioPublication;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        \assert($subject instanceof PortfolioPublication);
        $user = $token->getUser();
        if (!$user instanceof Personnel) {
            return false;
        }
        if ($this->contexte->isSuperAdmin($user)) {
            return true;
        }
        if (!$this->permissions->hasAnyPermission($user, [UnifolioRoles::REFERENT, UnifolioRoles::PUBLICATION])) {
            return false;
        }
        foreach ($this->departementsDe($subject->getPortfolio()->getEtudiant()) as $departement) {
            if ($this->contexte->administre($user, $departement)) {
                return true;
            }
        }
        $vote?->addReason('Cet étudiant n\'appartient pas à votre département.');

        return false;
    }

    /** @return list<StructureDepartement> */
    private function departementsDe(Etudiant $etudiant): array
    {
        $departements = [];
        foreach ($etudiant->getScolarites() as $scolarite) {
            $d = $this->perimetre->getDiplome($scolarite)?->getDepartement();
            if ($d !== null) {
                $departements[spl_object_id($d)] = $d;
            }
        }

        return array_values($departements);
    }
}
