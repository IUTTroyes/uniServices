<?php

namespace App\Security;

use App\Entity\Stages\StagePeriode;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter pour les opérations sur les entités Stage
 * Gère les droits d'accès pour : StagePeriode et gestion des stages
 */
class StageVoter extends Voter
{
    // Permissions StagePeriode
    public const CAN_VIEW_STAGE_PERIODE = 'CAN_VIEW_STAGE_PERIODE';
    public const CAN_EDIT_STAGE_PERIODE = 'CAN_EDIT_STAGE_PERIODE';
    public const CAN_DELETE_STAGE_PERIODE = 'CAN_DELETE_STAGE_PERIODE';

    // Permissions Stage (général)
    public const CAN_VIEW_STAGE = 'CAN_VIEW_STAGE';
    public const CAN_EDIT_STAGE = 'CAN_EDIT_STAGE';
    public const CAN_DELETE_STAGE = 'CAN_DELETE_STAGE';
    public const CAN_VALIDATE_STAGE = 'CAN_VALIDATE_STAGE';

    private const SUPPORTED_ATTRIBUTES = [
        self::CAN_VIEW_STAGE_PERIODE,
        self::CAN_EDIT_STAGE_PERIODE,
        self::CAN_DELETE_STAGE_PERIODE,
        self::CAN_VIEW_STAGE,
        self::CAN_EDIT_STAGE,
        self::CAN_DELETE_STAGE,
        self::CAN_VALIDATE_STAGE,
    ];

    /**
     * @inheritDoc
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, self::SUPPORTED_ATTRIBUTES);
    }

    /**
     * @inheritDoc
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        // L'utilisateur doit être connecté
        if (!($user instanceof Etudiant || $user instanceof Personnel)) {
            $vote?->addReason('L\'utilisateur n\'est pas connecté.');
            return false;
        }

        // ROLE_SUPER_ADMIN a accès à tout
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return match($attribute) {
            // StagePeriode
            self::CAN_VIEW_STAGE_PERIODE => $this->canViewStagePeriode($user),
            self::CAN_EDIT_STAGE_PERIODE => $this->canEditStagePeriode($user),
            self::CAN_DELETE_STAGE_PERIODE => $this->canDeleteStagePeriode($user),

            // Stage
            self::CAN_VIEW_STAGE => $this->canViewStage($subject, $user),
            self::CAN_EDIT_STAGE => $this->canEditStage($subject, $user),
            self::CAN_DELETE_STAGE => $this->canDeleteStage($user),
            self::CAN_VALIDATE_STAGE => $this->canValidateStage($user),

            default => false,
        };
    }

    // ========== Helpers ==========

    private function isSuperAdmin(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && in_array('ROLE_SUPER_ADMIN', $user->getRoles());
    }

    private function hasAnyRole(Personnel $user, array $roles): bool
    {
        foreach ($roles as $role) {
            if (in_array($role, $user->getRoles())) {
                return true;
            }
        }
        return false;
    }

    // ========== StagePeriode ==========

    private function canViewStagePeriode(Personnel|Etudiant $user): bool
    {
        // Tout le monde peut voir les périodes de stage
        return true;
    }

    private function canEditStagePeriode(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT',
            'ROLE_RESP_STAGES',
            'ROLE_SCOLARITE'
        ]);
    }

    private function canDeleteStagePeriode(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_RESP_STAGES'
        ]);
    }

    // ========== Stage ==========

    private function canViewStage(mixed $subject, Personnel|Etudiant $user): bool
    {
        if ($user instanceof Personnel) {
            return $this->hasAnyRole($user, [
                'ROLE_SUPER_ADMIN',
                'ROLE_ADMIN',
                'ROLE_CHEF_DEPT',
                'ROLE_RESP_STAGES',
                'ROLE_SCOLARITE',
                'ROLE_PERMANENT'
            ]);
        }

        // Un étudiant peut voir ses propres stages
        return $user instanceof Etudiant;
    }

    private function canEditStage(mixed $subject, Personnel|Etudiant $user): bool
    {
        if ($user instanceof Personnel) {
            return $this->hasAnyRole($user, [
                'ROLE_SUPER_ADMIN',
                'ROLE_ADMIN',
                'ROLE_CHEF_DEPT',
                'ROLE_RESP_STAGES',
                'ROLE_SCOLARITE'
            ]);
        }

        // Un étudiant peut modifier son propre stage (dans certaines limites)
        return $user instanceof Etudiant;
    }

    private function canDeleteStage(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_RESP_STAGES'
        ]);
    }

    private function canValidateStage(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT',
            'ROLE_RESP_STAGES',
            'ROLE_DIRECTEUR_ETUDES'
        ]);
    }
}

