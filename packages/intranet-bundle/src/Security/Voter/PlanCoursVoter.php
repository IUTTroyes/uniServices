<?php

namespace IntranetBundle\Security\Voter;

use App\Entity\Users\Personnel;
use App\Security\UserEffectivePermissionService;
use IntranetBundle\Entity\PlanCours\PlanCours;
use IntranetBundle\Entity\PlanCours\PlanCoursRealise;
use IntranetBundle\Entity\PlanCours\PlanCoursSequence;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class PlanCoursVoter extends Voter
{
    public const MANAGE = 'CAN_MANAGE_PLAN_COURS';

    public function __construct(private readonly UserEffectivePermissionService $permissions) {}

    public function supportsAttribute(string $attribute): bool
    {
        return $attribute === self::MANAGE;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $this->supportsAttribute($attribute)
            && ($subject instanceof PlanCours || $subject instanceof PlanCoursRealise || $subject instanceof PlanCoursSequence);
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

        $plan = $subject instanceof PlanCours ? $subject : $subject->getPlanCours();
        $teaching = $plan?->getEnseignement();
        if ($teaching === null) {
            return false;
        }

        if ($this->permissions->hasAnyPermissionForTeaching(
            $user, $teaching, ['ROLE_ADMIN', 'ROLE_CHEF_DEPT', 'ROLE_DIRECTEUR_ETUDES', 'ROLE_RESP_PARCOURS'],
        )) {
            return true;
        }

        // Un nouveau plan doit être lié à un enseignement réellement affecté au personnel.
        if ($plan->getId() !== null && $plan->getResponsable() === $user) {
            return true;
        }

        foreach ($teaching->getPrevisionnels() as $assignment) {
            if ($assignment->getPersonnel() === $user && $assignment->getAnneeUniversitaire() === $plan->getAnneeUniversitaire()) {
                return true;
            }
        }

        return false;
    }
}
