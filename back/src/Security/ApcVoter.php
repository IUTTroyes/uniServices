<?php

namespace App\Security;

use App\Entity\Apc\ApcApprentissageCritique;
use App\Entity\Apc\ApcCompetence;
use App\Entity\Apc\ApcNiveau;
use App\Entity\Apc\ApcParcours;
use App\Entity\Apc\ApcReferentiel;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter pour les opérations sur les entités APC (Approche Par Compétences)
 * Gère les droits d'accès pour : Référentiel, Compétences, Niveaux, Parcours, AC
 */
class ApcVoter extends Voter
{
    // Permissions Référentiel APC
    public const CAN_VIEW_APC_REFERENTIEL = 'CAN_VIEW_APC_REFERENTIEL';
    public const CAN_EDIT_APC_REFERENTIEL = 'CAN_EDIT_APC_REFERENTIEL';
    public const CAN_DELETE_APC_REFERENTIEL = 'CAN_DELETE_APC_REFERENTIEL';

    // Permissions Compétences
    public const CAN_VIEW_APC_COMPETENCE = 'CAN_VIEW_APC_COMPETENCE';
    public const CAN_EDIT_APC_COMPETENCE = 'CAN_EDIT_APC_COMPETENCE';
    public const CAN_DELETE_APC_COMPETENCE = 'CAN_DELETE_APC_COMPETENCE';

    // Permissions Niveaux
    public const CAN_VIEW_APC_NIVEAU = 'CAN_VIEW_APC_NIVEAU';
    public const CAN_EDIT_APC_NIVEAU = 'CAN_EDIT_APC_NIVEAU';
    public const CAN_DELETE_APC_NIVEAU = 'CAN_DELETE_APC_NIVEAU';

    // Permissions Parcours
    public const CAN_VIEW_APC_PARCOURS = 'CAN_VIEW_APC_PARCOURS';
    public const CAN_EDIT_APC_PARCOURS = 'CAN_EDIT_APC_PARCOURS';
    public const CAN_DELETE_APC_PARCOURS = 'CAN_DELETE_APC_PARCOURS';

    // Permissions Apprentissages Critiques
    public const CAN_VIEW_APC_AC = 'CAN_VIEW_APC_AC';
    public const CAN_EDIT_APC_AC = 'CAN_EDIT_APC_AC';
    public const CAN_DELETE_APC_AC = 'CAN_DELETE_APC_AC';

    private const SUPPORTED_ATTRIBUTES = [
        self::CAN_VIEW_APC_REFERENTIEL,
        self::CAN_EDIT_APC_REFERENTIEL,
        self::CAN_DELETE_APC_REFERENTIEL,
        self::CAN_VIEW_APC_COMPETENCE,
        self::CAN_EDIT_APC_COMPETENCE,
        self::CAN_DELETE_APC_COMPETENCE,
        self::CAN_VIEW_APC_NIVEAU,
        self::CAN_EDIT_APC_NIVEAU,
        self::CAN_DELETE_APC_NIVEAU,
        self::CAN_VIEW_APC_PARCOURS,
        self::CAN_EDIT_APC_PARCOURS,
        self::CAN_DELETE_APC_PARCOURS,
        self::CAN_VIEW_APC_AC,
        self::CAN_EDIT_APC_AC,
        self::CAN_DELETE_APC_AC,
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
            // Référentiel
            self::CAN_VIEW_APC_REFERENTIEL => $this->canViewReferentiel($user),
            self::CAN_EDIT_APC_REFERENTIEL => $this->canEditReferentiel($user),
            self::CAN_DELETE_APC_REFERENTIEL => $this->canDeleteReferentiel($user),

            // Compétences
            self::CAN_VIEW_APC_COMPETENCE => $this->canViewCompetence($user),
            self::CAN_EDIT_APC_COMPETENCE => $this->canEditCompetence($user),
            self::CAN_DELETE_APC_COMPETENCE => $this->canDeleteCompetence($user),

            // Niveaux
            self::CAN_VIEW_APC_NIVEAU => $this->canViewNiveau($user),
            self::CAN_EDIT_APC_NIVEAU => $this->canEditNiveau($user),
            self::CAN_DELETE_APC_NIVEAU => $this->canDeleteNiveau($user),

            // Parcours
            self::CAN_VIEW_APC_PARCOURS => $this->canViewParcours($user),
            self::CAN_EDIT_APC_PARCOURS => $this->canEditParcours($user),
            self::CAN_DELETE_APC_PARCOURS => $this->canDeleteParcours($user),

            // Apprentissages Critiques
            self::CAN_VIEW_APC_AC => $this->canViewAc($user),
            self::CAN_EDIT_APC_AC => $this->canEditAc($user),
            self::CAN_DELETE_APC_AC => $this->canDeleteAc($user),

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

    // ========== Référentiel ==========

    private function canViewReferentiel(Personnel|Etudiant $user): bool
    {
        // Tout le monde peut voir les référentiels
        return true;
    }

    private function canEditReferentiel(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT',
            'ROLE_RESP_PARCOURS'
        ]);
    }

    private function canDeleteReferentiel(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN'
        ]);
    }

    // ========== Compétences ==========

    private function canViewCompetence(Personnel|Etudiant $user): bool
    {
        // Tout le monde peut voir les compétences
        return true;
    }

    private function canEditCompetence(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT',
            'ROLE_RESP_PARCOURS'
        ]);
    }

    private function canDeleteCompetence(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN'
        ]);
    }

    // ========== Niveaux ==========

    private function canViewNiveau(Personnel|Etudiant $user): bool
    {
        // Tout le monde peut voir les niveaux
        return true;
    }

    private function canEditNiveau(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT',
            'ROLE_RESP_PARCOURS'
        ]);
    }

    private function canDeleteNiveau(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN'
        ]);
    }

    // ========== Parcours ==========

    private function canViewParcours(Personnel|Etudiant $user): bool
    {
        // Tout le monde peut voir les parcours
        return true;
    }

    private function canEditParcours(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT',
            'ROLE_RESP_PARCOURS'
        ]);
    }

    private function canDeleteParcours(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN'
        ]);
    }

    // ========== Apprentissages Critiques ==========

    private function canViewAc(Personnel|Etudiant $user): bool
    {
        // Tout le monde peut voir les AC
        return true;
    }

    private function canEditAc(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT',
            'ROLE_RESP_PARCOURS'
        ]);
    }

    private function canDeleteAc(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN'
        ]);
    }
}

