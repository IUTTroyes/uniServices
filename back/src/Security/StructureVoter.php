<?php

namespace App\Security;

use App\Entity\Structure\StructureAnnee;
use App\Entity\Structure\StructureCalendrier;
use App\Entity\Structure\StructureDepartement;
use App\Entity\Structure\StructureDepartementPersonnel;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Structure\StructureGroupe;
use App\Entity\Structure\StructurePn;
use App\Entity\Structure\StructureSemestre;
use App\Entity\Structure\StructureTypeDiplome;
use App\Entity\Structure\StructureUe;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter pour les opérations sur les entités de structure
 * Gère les droits d'accès pour : Département, Diplôme, Semestre, Groupe, UE, PN, Annee, Calendrier, TypeDiplome
 */
class StructureVoter extends Voter
{
    // Permissions Département
    public const CAN_VIEW_DEPARTEMENT = 'CAN_VIEW_DEPARTEMENT';
    public const CAN_EDIT_DEPARTEMENT = 'CAN_EDIT_DEPARTEMENT';
    public const CAN_DELETE_DEPARTEMENT = 'CAN_DELETE_DEPARTEMENT';

    // Permissions Diplôme
    public const CAN_VIEW_DIPLOME = 'CAN_VIEW_DIPLOME';
    public const CAN_EDIT_DIPLOME = 'CAN_EDIT_DIPLOME';
    public const CAN_DELETE_DIPLOME = 'CAN_DELETE_DIPLOME';

    // Permissions Semestre
    public const CAN_VIEW_SEMESTRE = 'CAN_VIEW_SEMESTRE';
    public const CAN_EDIT_SEMESTRE = 'CAN_EDIT_SEMESTRE';
    public const CAN_DELETE_SEMESTRE = 'CAN_DELETE_SEMESTRE';

    // Permissions Groupe
    public const CAN_VIEW_GROUPE = 'CAN_VIEW_GROUPE';
    public const CAN_EDIT_GROUPE = 'CAN_EDIT_GROUPE';
    public const CAN_DELETE_GROUPE = 'CAN_DELETE_GROUPE';

    // Permissions UE
    public const CAN_VIEW_UE = 'CAN_VIEW_UE';
    public const CAN_EDIT_UE = 'CAN_EDIT_UE';
    public const CAN_DELETE_UE = 'CAN_DELETE_UE';

    // Permissions PN (Programme National)
    public const CAN_VIEW_PN = 'CAN_VIEW_PN';
    public const CAN_EDIT_PN = 'CAN_EDIT_PN';
    public const CAN_DELETE_PN = 'CAN_DELETE_PN';

    // Permissions Annee
    public const CAN_VIEW_ANNEE = 'CAN_VIEW_ANNEE';
    public const CAN_EDIT_ANNEE = 'CAN_EDIT_ANNEE';
    public const CAN_DELETE_ANNEE = 'CAN_DELETE_ANNEE';

    // Permissions Calendrier
    public const CAN_VIEW_CALENDRIER = 'CAN_VIEW_CALENDRIER';
    public const CAN_EDIT_CALENDRIER = 'CAN_EDIT_CALENDRIER';
    public const CAN_DELETE_CALENDRIER = 'CAN_DELETE_CALENDRIER';

    // Permissions Type Diplôme
    public const CAN_VIEW_TYPE_DIPLOME = 'CAN_VIEW_TYPE_DIPLOME';
    public const CAN_EDIT_TYPE_DIPLOME = 'CAN_EDIT_TYPE_DIPLOME';
    public const CAN_DELETE_TYPE_DIPLOME = 'CAN_DELETE_TYPE_DIPLOME';

    // Permissions Département Personnel
    public const CAN_VIEW_DEPT_PERSONNEL = 'CAN_VIEW_DEPT_PERSONNEL';
    public const CAN_EDIT_DEPT_PERSONNEL = 'CAN_EDIT_DEPT_PERSONNEL';
    public const CAN_DELETE_DEPT_PERSONNEL = 'CAN_DELETE_DEPT_PERSONNEL';

    private const SUPPORTED_ATTRIBUTES = [
        self::CAN_VIEW_DEPARTEMENT,
        self::CAN_EDIT_DEPARTEMENT,
        self::CAN_DELETE_DEPARTEMENT,
        self::CAN_VIEW_DIPLOME,
        self::CAN_EDIT_DIPLOME,
        self::CAN_DELETE_DIPLOME,
        self::CAN_VIEW_SEMESTRE,
        self::CAN_EDIT_SEMESTRE,
        self::CAN_DELETE_SEMESTRE,
        self::CAN_VIEW_GROUPE,
        self::CAN_EDIT_GROUPE,
        self::CAN_DELETE_GROUPE,
        self::CAN_VIEW_UE,
        self::CAN_EDIT_UE,
        self::CAN_DELETE_UE,
        self::CAN_VIEW_PN,
        self::CAN_EDIT_PN,
        self::CAN_DELETE_PN,
        self::CAN_VIEW_ANNEE,
        self::CAN_EDIT_ANNEE,
        self::CAN_DELETE_ANNEE,
        self::CAN_VIEW_CALENDRIER,
        self::CAN_EDIT_CALENDRIER,
        self::CAN_DELETE_CALENDRIER,
        self::CAN_VIEW_TYPE_DIPLOME,
        self::CAN_EDIT_TYPE_DIPLOME,
        self::CAN_DELETE_TYPE_DIPLOME,
        self::CAN_VIEW_DEPT_PERSONNEL,
        self::CAN_EDIT_DEPT_PERSONNEL,
        self::CAN_DELETE_DEPT_PERSONNEL,
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
            // Département
            self::CAN_VIEW_DEPARTEMENT => $this->canViewDepartement($user),
            self::CAN_EDIT_DEPARTEMENT => $this->canEditDepartement($user),
            self::CAN_DELETE_DEPARTEMENT => $this->canDeleteDepartement($user),

            // Diplôme
            self::CAN_VIEW_DIPLOME => $this->canViewDiplome($user),
            self::CAN_EDIT_DIPLOME => $this->canEditDiplome($subject, $user),
            self::CAN_DELETE_DIPLOME => $this->canDeleteDiplome($user),

            // Semestre
            self::CAN_VIEW_SEMESTRE => $this->canViewSemestre($user),
            self::CAN_EDIT_SEMESTRE => $this->canEditSemestre($subject, $user),
            self::CAN_DELETE_SEMESTRE => $this->canDeleteSemestre($user),

            // Groupe
            self::CAN_VIEW_GROUPE => $this->canViewGroupe($user),
            self::CAN_EDIT_GROUPE => $this->canEditGroupe($user),
            self::CAN_DELETE_GROUPE => $this->canDeleteGroupe($user),

            // UE
            self::CAN_VIEW_UE => $this->canViewUe($user),
            self::CAN_EDIT_UE => $this->canEditUe($user),
            self::CAN_DELETE_UE => $this->canDeleteUe($user),

            // PN
            self::CAN_VIEW_PN => $this->canViewPn($user),
            self::CAN_EDIT_PN => $this->canEditPn($user),
            self::CAN_DELETE_PN => $this->canDeletePn($user),

            // Annee
            self::CAN_VIEW_ANNEE => $this->canViewAnnee($user),
            self::CAN_EDIT_ANNEE => $this->canEditAnnee($user),
            self::CAN_DELETE_ANNEE => $this->canDeleteAnnee($user),

            // Calendrier
            self::CAN_VIEW_CALENDRIER => $this->canViewCalendrier($user),
            self::CAN_EDIT_CALENDRIER => $this->canEditCalendrier($user),
            self::CAN_DELETE_CALENDRIER => $this->canDeleteCalendrier($user),

            // Type Diplôme
            self::CAN_VIEW_TYPE_DIPLOME => $this->canViewTypeDiplome($user),
            self::CAN_EDIT_TYPE_DIPLOME => $this->canEditTypeDiplome($user),
            self::CAN_DELETE_TYPE_DIPLOME => $this->canDeleteTypeDiplome($user),

            // Département Personnel
            self::CAN_VIEW_DEPT_PERSONNEL => $this->canViewDeptPersonnel($user),
            self::CAN_EDIT_DEPT_PERSONNEL => $this->canEditDeptPersonnel($user),
            self::CAN_DELETE_DEPT_PERSONNEL => $this->canDeleteDeptPersonnel($user),

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

    // ========== Département ==========

    private function canViewDepartement(Personnel|Etudiant $user): bool
    {
        // Tout le monde peut voir les départements
        return true;
    }

    private function canEditDepartement(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT'
        ]);
    }

    private function canDeleteDepartement(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN'
        ]);
    }

    // ========== Diplôme ==========

    private function canViewDiplome(Personnel|Etudiant $user): bool
    {
        // Tout le monde peut voir les diplômes
        return true;
    }

    private function canEditDiplome(mixed $subject, Personnel|Etudiant $user): bool
    {
        if (!$user instanceof Personnel) {
            return false;
        }

        // Rôles avec accès complet
        if ($this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT'
        ])) {
            return true;
        }

        // Le responsable du diplôme peut le modifier
        if ($subject instanceof StructureDiplome) {
            return $subject->getResponsableDiplome() === $user ||
                   $subject->getAssistantDiplome() === $user;
        }

        return false;
    }

    private function canDeleteDiplome(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN'
        ]);
    }

    // ========== Semestre ==========

    private function canViewSemestre(Personnel|Etudiant $user): bool
    {
        // Tout le monde peut voir les semestres
        return true;
    }

    private function canEditSemestre(mixed $subject, Personnel|Etudiant $user): bool
    {
        if (!$user instanceof Personnel) {
            return false;
        }

        // Rôles avec accès complet
        if ($this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT',
            'ROLE_RESP_PARCOURS',
            'ROLE_DIRECTEUR_ETUDES'
        ])) {
            return true;
        }

        return false;
    }

    private function canDeleteSemestre(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN'
        ]);
    }

    // ========== Groupe ==========

    private function canViewGroupe(Personnel|Etudiant $user): bool
    {
        // Tout le monde peut voir les groupes
        return true;
    }

    private function canEditGroupe(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT',
            'ROLE_SCOLARITE',
            'ROLE_ASSISTANT',
            'ROLE_DIRECTEUR_ETUDES'
        ]);
    }

    private function canDeleteGroupe(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT'
        ]);
    }

    // ========== UE ==========

    private function canViewUe(Personnel|Etudiant $user): bool
    {
        // Tout le monde peut voir les UE
        return true;
    }

    private function canEditUe(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT',
            'ROLE_RESP_PARCOURS'
        ]);
    }

    private function canDeleteUe(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN'
        ]);
    }

    // ========== PN (Programme National) ==========

    private function canViewPn(Personnel|Etudiant $user): bool
    {
        // Tout le monde peut voir les PN
        return true;
    }

    private function canEditPn(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT'
        ]);
    }

    private function canDeletePn(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN'
        ]);
    }

    // ========== Annee ==========

    private function canViewAnnee(Personnel|Etudiant $user): bool
    {
        return true;
    }

    private function canEditAnnee(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT',
            'ROLE_DIRECTEUR_ETUDES'
        ]);
    }

    private function canDeleteAnnee(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN'
        ]);
    }

    // ========== Calendrier ==========

    private function canViewCalendrier(Personnel|Etudiant $user): bool
    {
        return true;
    }

    private function canEditCalendrier(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT',
            'ROLE_SCOLARITE',
            'ROLE_DIRECTEUR_ETUDES'
        ]);
    }

    private function canDeleteCalendrier(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN'
        ]);
    }

    // ========== Type Diplôme ==========

    private function canViewTypeDiplome(Personnel|Etudiant $user): bool
    {
        return true;
    }

    private function canEditTypeDiplome(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN'
        ]);
    }

    private function canDeleteTypeDiplome(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN'
        ]);
    }

    // ========== Département Personnel ==========

    private function canViewDeptPersonnel(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel;
    }

    private function canEditDeptPersonnel(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT'
        ]);
    }

    private function canDeleteDeptPersonnel(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT'
        ]);
    }
}
