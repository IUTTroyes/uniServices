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
    public const MISSION = 'PARCAUTO_MISSION';

    public function __construct(private readonly UserEffectivePermissionService $permissions, private readonly UserPackageResolver $packages)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::ACCES, self::GESTION, self::ENTRETIEN], true)
            || ($attribute === self::MISSION && $subject instanceof Reservation);
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

        return $attribute === self::ACCES
            || $this->permissions->isSuperAdmin($user)
            || $this->permissions->hasPermission($user, 'ROLE_CHEF_SERVICES_ADMINISTRATIFS')
            || ($attribute === self::ENTRETIEN && $this->permissions->hasPermission($user, 'ROLE_CHEF_SERVICE_GENERAL'));
    }
}
