<?php

namespace FinanceBundle\Security;

use App\Entity\Users\Personnel;
use App\Security\UserEffectivePermissionService;
use FinanceBundle\Entity\FinanceBonCommande;
use FinanceBundle\Entity\FinanceReception;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class FinanceVoter extends Voter
{
    public const ACCES = 'FINANCE_ACCES';
    public const REFERENTIEL_GERER = 'FINANCE_REFERENTIEL_GERER';
    public const BC_VOIR = 'FINANCE_BC_VOIR';
    public const BC_MODIFIER = 'FINANCE_BC_MODIFIER';
    public const BC_SUPPRIMER = 'FINANCE_BC_SUPPRIMER';
    public const BC_CREER = 'FINANCE_BC_CREER';
    public const BC_GERER = 'FINANCE_BC_GERER';
    public const BC_DEPOSER_DOCUMENT = 'FINANCE_BC_DEPOSER_DOCUMENT';

    private const SUR_COMMANDE = [self::BC_VOIR, self::BC_MODIFIER, self::BC_SUPPRIMER, self::BC_CREER, self::BC_GERER, self::BC_DEPOSER_DOCUMENT];

    public const VIEW = 'CAN_VIEW_FINANCE';
    public const CREATE = 'CAN_CREATE_FINANCE';
    public const EDIT = 'CAN_EDIT_FINANCE';
    public const MANAGE = 'CAN_MANAGE_FINANCE';

    public function __construct(private readonly UserEffectivePermissionService $permissions) {}

    public function supportsAttribute(string $attribute): bool
    {
        return in_array($attribute, [self::VIEW, self::CREATE, self::EDIT, self::MANAGE, self::ACCES, self::REFERENTIEL_GERER, ...self::SUR_COMMANDE], true);
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if ($attribute === self::ACCES || $attribute === self::REFERENTIEL_GERER) return true;
        return $this->supportsAttribute($attribute)
            && ($subject instanceof FinanceBonCommande || $subject instanceof FinanceReception);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        return $this->allows($attribute, $subject, $token->getUser());
    }

    /** Même décision pour les opérations API et les services métier. */
    public function allows(string $attribute, mixed $subject, mixed $user): bool
    {
        if ($attribute === self::ACCES || $attribute === self::REFERENTIEL_GERER) {
            return $user instanceof Personnel && ($this->permissions->isSuperAdmin($user)
                || $this->permissions->hasPermission($user, $attribute === self::ACCES ? 'ROLE_FINANCE_VIEW' : 'ROLE_FINANCE_MANAGER'));
        }
        if ($attribute === self::BC_CREER && $subject instanceof FinanceBonCommande && $user instanceof Personnel) {
            // Le nouveau processeur impose le demandeur connecté : autoriser cette même représentation.
            $subject = clone $subject;
            $subject->setDemandeur($user);
        }
        $attribute = match ($attribute) {
            self::BC_VOIR => self::VIEW,
            self::BC_CREER => self::CREATE,
            self::BC_MODIFIER, self::BC_DEPOSER_DOCUMENT => self::EDIT,
            self::BC_SUPPRIMER, self::BC_GERER => self::MANAGE,
            default => $attribute,
        };

        if (!$user instanceof Personnel) {
            return false;
        }

        if ($this->permissions->isSuperAdmin($user)) {
            return true;
        }

        $order = $subject instanceof FinanceReception ? $subject->getBonCommande() : $subject;
        $department = $order?->getDepartement();
        if ($attribute === self::MANAGE) {
            return $this->permissions->hasAnyPermissionInDepartment($user, ['ROLE_FINANCE_MANAGER'], $department);
        }

        if ($attribute === self::CREATE || $attribute === self::EDIT) {
            // Le demandeur ne peut intervenir que sur sa propre commande.
            return $subject instanceof FinanceBonCommande
                && ($this->permissions->hasAnyPermissionInDepartment($user, ['ROLE_FINANCE_MANAGER'], $department)
                    || ($order->getDemandeur() === $user && $this->permissions->hasAnyPermissionInDepartment(
                        $user, ['ROLE_FINANCE_DEMANDEUR'], $department,
                    )));
        }

        return $this->permissions->hasAnyPermissionInDepartment($user, ['ROLE_FINANCE_VIEW'], $department);
    }
}
