<?php

declare(strict_types=1);

namespace ParcautoBundle\Security;

use App\Security\UserPackageResolver;
use App\Entity\Users\Personnel;
use App\Security\UserEffectivePermissionService;
use ParcautoBundle\Entity\Reservation;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class ParcautoVoter extends Voter
{
    public const ACCES = 'PARCAUTO_ACCES';
    public const GESTION = 'PARCAUTO_GESTION';
    public const ENTRETIEN = 'PARCAUTO_ENTRETIEN';
    public const ARBITRAGE = 'PARCAUTO_ARBITRAGE';
    public const DECIDER = 'PARCAUTO_DECIDER';
    public const MISSION = 'PARCAUTO_MISSION';

    public function __construct(private readonly UserEffectivePermissionService $permissions, private readonly UserPackageResolver $packages)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::ACCES, self::GESTION, self::ENTRETIEN, self::ARBITRAGE], true)
            || (in_array($attribute, [self::MISSION, self::DECIDER], true) && $subject instanceof Reservation);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof Personnel) {
            return false;
        }

        if ($attribute === self::MISSION) {
            return $subject->conducteur->getId() === $user->getId();
        }

        if (!$this->packages->hasPackage($user, 'parcauto')) {
            return false;
        }

        if ($attribute === self::ARBITRAGE) {
            return $this->permissions->isSuperAdmin($user) || $this->permissions->hasAnyPermission($user, [
                'ROLE_DIRECTEUR_IUT', 'ROLE_CHEF_SERVICES_ADMINISTRATIFS',
            ]);
        }

        if ($attribute === self::DECIDER) {
            if ($subject->statut !== Reservation::DEMANDEE) {
                return false;
            }
            if ($this->permissions->isSuperAdmin($user)) {
                return true;
            }
            if ($this->permissions->isTeacher($subject->conducteur) && !$this->permissions->isBiatss($subject->conducteur)) {
                return $this->permissions->hasPermission($user, 'ROLE_DIRECTEUR_IUT');
            }

            return $this->permissions->hasPermission($user, 'ROLE_CHEF_SERVICES_ADMINISTRATIFS');
        }

        return $attribute === self::ACCES
            || $this->permissions->isSuperAdmin($user)
            || $this->permissions->hasPermission($user, 'ROLE_CHEF_SERVICES_ADMINISTRATIFS')
            || ($attribute === self::ENTRETIEN && $this->permissions->hasPermission($user, 'ROLE_CHEF_SERVICE_GENERAL'));
    }
}
