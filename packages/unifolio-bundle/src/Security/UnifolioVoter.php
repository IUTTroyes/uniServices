<?php

namespace UnifolioBundle\Security;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Security\UserEffectivePermissionService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use UnifolioBundle\Service\Perimetre\UnifolioPerimetre;

/**
 * Droits globaux d'UniFolio (accès aux espaces). Les droits sur une ressource
 * précise (trace, portfolio, évaluation) sont portés par des voters dédiés.
 *
 * @extends Voter<string, mixed>
 */
class UnifolioVoter extends Voter
{
    /** Accès à l'application (menu, tableau de bord). */
    public const ACCES = 'UNIFOLIO_ACCES';
    /** Espace étudiant : bibliothèque, auto-évaluation, portfolios. */
    public const ESPACE_ETUDIANT = 'UNIFOLIO_ESPACE_ETUDIANT';
    /** Évaluer et commenter les travaux des étudiants. */
    public const CORRIGER = 'UNIFOLIO_CORRIGER';
    /** Paramétrage : grilles, campagnes, affectations, modèles. */
    public const GERER = 'UNIFOLIO_GERER';
    /** Modération des portfolios publiés. */
    public const MODERER = 'UNIFOLIO_MODERER';

    private const ATTRIBUTS = [self::ACCES, self::ESPACE_ETUDIANT, self::CORRIGER, self::GERER, self::MODERER];

    public function __construct(
        private readonly UserEffectivePermissionService $permissions,
        private readonly UnifolioPerimetre $perimetre,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, self::ATTRIBUTS, true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if ($user instanceof Etudiant) {
            $eligible = \in_array($attribute, [self::ACCES, self::ESPACE_ETUDIANT], true)
                && $this->perimetre->isEtudiantEligible($user);
            if (!$eligible) {
                $vote?->addReason('UniFolio est réservé aux étudiants de BUT.');
            }

            return $eligible;
        }

        if (!$user instanceof Personnel) {
            $vote?->addReason('L\'utilisateur n\'est pas connecté.');

            return false;
        }

        if ($attribute === self::ESPACE_ETUDIANT) {
            return false;
        }

        if ($this->permissions->isSuperAdmin($user)) {
            return true;
        }

        return match ($attribute) {
            self::ACCES => $this->a($user, [UnifolioRoles::ENSEIGNANT, UnifolioRoles::CORRECTEUR, UnifolioRoles::REFERENT, UnifolioRoles::PUBLICATION]),
            self::CORRIGER => $this->a($user, [UnifolioRoles::ENSEIGNANT, UnifolioRoles::CORRECTEUR, UnifolioRoles::REFERENT]),
            self::GERER => $this->a($user, [UnifolioRoles::REFERENT]),
            self::MODERER => $this->a($user, [UnifolioRoles::PUBLICATION, UnifolioRoles::REFERENT]),
            default => false,
        };
    }

    /** @param string[] $roles */
    private function a(Personnel $user, array $roles): bool
    {
        return $this->permissions->hasAnyPermission($user, $roles);
    }
}
