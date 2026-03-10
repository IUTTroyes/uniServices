<?php

namespace App\Security;

use App\Entity\Edt\EdtContraintesSemestre;
use App\Entity\Edt\EdtCreneauxInterditsSemaine;
use App\Entity\Edt\EdtEvent;
use App\Entity\Edt\EdtProgression;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter pour les opérations sur les entités EDT (Emploi du Temps)
 * Gère les droits d'accès pour : EdtEvent, Contraintes, Créneaux interdits, Progression
 */
class EdtVoter extends Voter
{
    // Permissions EdtEvent
    public const CAN_VIEW_EDT = 'CAN_VIEW_EDT';
    public const CAN_EDIT_EDT = 'CAN_EDIT_EDT';
    public const CAN_DELETE_EDT = 'CAN_DELETE_EDT';

    // Permissions Contraintes
    public const CAN_VIEW_EDT_CONTRAINTES = 'CAN_VIEW_EDT_CONTRAINTES';
    public const CAN_EDIT_EDT_CONTRAINTES = 'CAN_EDIT_EDT_CONTRAINTES';
    public const CAN_DELETE_EDT_CONTRAINTES = 'CAN_DELETE_EDT_CONTRAINTES';

    // Permissions Créneaux Interdits
    public const CAN_VIEW_EDT_CRENEAUX = 'CAN_VIEW_EDT_CRENEAUX';
    public const CAN_EDIT_EDT_CRENEAUX = 'CAN_EDIT_EDT_CRENEAUX';
    public const CAN_DELETE_EDT_CRENEAUX = 'CAN_DELETE_EDT_CRENEAUX';

    // Permissions Progression
    public const CAN_VIEW_EDT_PROGRESSION = 'CAN_VIEW_EDT_PROGRESSION';
    public const CAN_EDIT_EDT_PROGRESSION = 'CAN_EDIT_EDT_PROGRESSION';
    public const CAN_DELETE_EDT_PROGRESSION = 'CAN_DELETE_EDT_PROGRESSION';

    private const SUPPORTED_ATTRIBUTES = [
        self::CAN_VIEW_EDT,
        self::CAN_EDIT_EDT,
        self::CAN_DELETE_EDT,
        self::CAN_VIEW_EDT_CONTRAINTES,
        self::CAN_EDIT_EDT_CONTRAINTES,
        self::CAN_DELETE_EDT_CONTRAINTES,
        self::CAN_VIEW_EDT_CRENEAUX,
        self::CAN_EDIT_EDT_CRENEAUX,
        self::CAN_DELETE_EDT_CRENEAUX,
        self::CAN_VIEW_EDT_PROGRESSION,
        self::CAN_EDIT_EDT_PROGRESSION,
        self::CAN_DELETE_EDT_PROGRESSION,
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
            // EdtEvent
            self::CAN_VIEW_EDT => $this->canViewEdt($subject, $user),
            self::CAN_EDIT_EDT => $this->canEditEdt($subject, $user),
            self::CAN_DELETE_EDT => $this->canDeleteEdt($user),

            // Contraintes
            self::CAN_VIEW_EDT_CONTRAINTES => $this->canViewContraintes($user),
            self::CAN_EDIT_EDT_CONTRAINTES => $this->canEditContraintes($user),
            self::CAN_DELETE_EDT_CONTRAINTES => $this->canDeleteContraintes($user),

            // Créneaux Interdits
            self::CAN_VIEW_EDT_CRENEAUX => $this->canViewCreneaux($user),
            self::CAN_EDIT_EDT_CRENEAUX => $this->canEditCreneaux($user),
            self::CAN_DELETE_EDT_CRENEAUX => $this->canDeleteCreneaux($user),

            // Progression
            self::CAN_VIEW_EDT_PROGRESSION => $this->canViewProgression($subject, $user),
            self::CAN_EDIT_EDT_PROGRESSION => $this->canEditProgression($subject, $user),
            self::CAN_DELETE_EDT_PROGRESSION => $this->canDeleteProgression($user),

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

    // ========== EdtEvent ==========

    private function canViewEdt(mixed $subject, Personnel|Etudiant $user): bool
    {
        // Tout le monde peut voir l'EDT (son propre EDT)
        return true;
    }

    private function canEditEdt(mixed $subject, Personnel|Etudiant $user): bool
    {
        if (!$user instanceof Personnel) {
            return false;
        }

        // Rôles avec accès complet à l'EDT
        if ($this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT',
            'ROLE_EDT',
            'ROLE_ASSISTANT'
        ])) {
            return true;
        }

        // Un personnel peut modifier ses propres événements
        if ($subject instanceof EdtEvent && $subject->getPersonnel() === $user) {
            return true;
        }

        return false;
    }

    private function canDeleteEdt(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT',
            'ROLE_EDT'
        ]);
    }

    // ========== Contraintes ==========

    private function canViewContraintes(Personnel|Etudiant $user): bool
    {
        // Le personnel peut voir les contraintes
        return $user instanceof Personnel;
    }

    private function canEditContraintes(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT',
            'ROLE_EDT',
            'ROLE_DIRECTEUR_ETUDES'
        ]);
    }

    private function canDeleteContraintes(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_EDT'
        ]);
    }

    // ========== Créneaux Interdits ==========

    private function canViewCreneaux(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel;
    }

    private function canEditCreneaux(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT',
            'ROLE_EDT'
        ]);
    }

    private function canDeleteCreneaux(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_EDT'
        ]);
    }

    // ========== Progression ==========

    private function canViewProgression(mixed $subject, Personnel|Etudiant $user): bool
    {
        if ($user instanceof Personnel) {
            // Rôles avec accès complet
            if ($this->hasAnyRole($user, [
                'ROLE_SUPER_ADMIN',
                'ROLE_ADMIN',
                'ROLE_CHEF_DEPT',
                'ROLE_DIRECTEUR_ETUDES',
                'ROLE_PERMANENT'
            ])) {
                return true;
            }
        }

        return false;
    }

    private function canEditProgression(mixed $subject, Personnel|Etudiant $user): bool
    {
        if (!$user instanceof Personnel) {
            return false;
        }

        // Rôles avec accès complet
        if ($this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT',
            'ROLE_DIRECTEUR_ETUDES',
            'ROLE_PERMANENT'
        ])) {
            return true;
        }

        return false;
    }

    private function canDeleteProgression(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT'
        ]);
    }
}


