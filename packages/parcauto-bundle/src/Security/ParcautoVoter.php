<?php

declare(strict_types=1);

namespace ParcautoBundle\Security;

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
    public const MISSION = 'PARCAUTO_MISSION';

    public function __construct(private readonly UserEffectivePermissionService $permissions)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::ACCES, self::GESTION], true)
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

        $role = $attribute === self::ACCES ? ParcautoPermissionProvider::DEMANDE : ParcautoPermissionProvider::GESTION;

        return $this->permissions->isSuperAdmin($user) || $this->permissions->hasPermission($user, $role);
    }
}
