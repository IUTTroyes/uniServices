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
    public const VIEW = 'CAN_VIEW_FINANCE';
    public const CREATE = 'CAN_CREATE_FINANCE';
    public const EDIT = 'CAN_EDIT_FINANCE';
    public const MANAGE = 'CAN_MANAGE_FINANCE';

    public function __construct(private readonly UserEffectivePermissionService $permissions) {}

    public function supportsAttribute(string $attribute): bool
    {
        return in_array($attribute, [self::VIEW, self::CREATE, self::EDIT, self::MANAGE], true);
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $this->supportsAttribute($attribute)
            && ($subject instanceof FinanceBonCommande || $subject instanceof FinanceReception);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
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
