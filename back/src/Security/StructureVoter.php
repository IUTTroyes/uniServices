<?php

namespace App\Security;

use App\Entity\DepartementActualite;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Structure\StructureGroupe;
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
    public const CAN_EDIT_GROUPE_STRUCTURE = 'CAN_EDIT_GROUPE_STRUCTURE';
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

    // Les affectations du personnel sont traitées par PersonnelVoter.
    public const CAN_VIEW_DEPT_PERSONNEL = PersonnelVoter::CAN_VIEW_DEPT_PERSONNEL;
    public const CAN_EDIT_DEPT_PERSONNEL = PersonnelVoter::CAN_EDIT_DEPT_PERSONNEL;
    public const CAN_DELETE_DEPT_PERSONNEL = PersonnelVoter::CAN_DELETE_DEPT_PERSONNEL;

    public const CAN_MANAGE_DEPARTEMENT_ACTUALITE = 'CAN_MANAGE_DEPARTEMENT_ACTUALITE';

    private const SUPPORTED_ATTRIBUTES = [
        self::CAN_MANAGE_DEPARTEMENT_ACTUALITE,
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
        self::CAN_EDIT_GROUPE_STRUCTURE,
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
    ];

    public function __construct(
        private readonly UserEffectivePermissionService $effectivePermissionService
    ) {}

    public function supportsAttribute(string $attribute): bool
    {
        return in_array($attribute, self::SUPPORTED_ATTRIBUTES, true);
    }

    /**
     * @inheritDoc
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $this->supportsAttribute($attribute);
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

        // SUPER_ADMIN a accès à tout
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return match ($attribute) {
            self::CAN_MANAGE_DEPARTEMENT_ACTUALITE => $user instanceof Personnel && $subject instanceof DepartementActualite
                && $this->effectivePermissionService->hasAnyPermissionInDepartment(
                    $user, ['ROLE_ADMIN', 'ROLE_CHEF_DEPT', 'ROLE_RELAI_COMM'], $subject->getDepartement(),
                ),
            // Département
            self::CAN_VIEW_DEPARTEMENT => $this->canViewDepartement($user),
            self::CAN_EDIT_DEPARTEMENT => $this->canEditDepartement($user, $subject),
            self::CAN_DELETE_DEPARTEMENT => $this->canDeleteDepartement($user, $subject),

            // Diplôme
            self::CAN_VIEW_DIPLOME => $this->canViewDiplome($user),
            self::CAN_EDIT_DIPLOME => $this->canEditDiplome($subject, $user),
            self::CAN_DELETE_DIPLOME => $this->canDeleteDiplome($user, $subject),

            // Semestre
            self::CAN_VIEW_SEMESTRE => $this->canViewSemestre($user),
            self::CAN_EDIT_SEMESTRE => $this->canEditSemestre($subject, $user),
            self::CAN_DELETE_SEMESTRE => $this->canDeleteSemestre($user, $subject),

            // Groupe
            self::CAN_VIEW_GROUPE => $this->canViewGroupe($user),
            self::CAN_EDIT_GROUPE => $this->canEditGroupe($user, $subject),
            self::CAN_EDIT_GROUPE_STRUCTURE => $this->canEditGroupeStructure($user),
            self::CAN_DELETE_GROUPE => $this->canDeleteGroupe($user, $subject),

            // UE
            self::CAN_VIEW_UE => $this->canViewUe($user),
            self::CAN_EDIT_UE => $this->canEditUe($user, $subject),
            self::CAN_DELETE_UE => $this->canDeleteUe($user, $subject),

            // PN
            self::CAN_VIEW_PN => $this->canViewPn($user),
            self::CAN_EDIT_PN => $this->canEditPn($user, $subject),
            self::CAN_DELETE_PN => $this->canDeletePn($user, $subject),

            // Annee
            self::CAN_VIEW_ANNEE => $this->canViewAnnee($user),
            self::CAN_EDIT_ANNEE => $this->canEditAnnee($user, $subject),
            self::CAN_DELETE_ANNEE => $this->canDeleteAnnee($user, $subject),

            // Calendrier
            self::CAN_VIEW_CALENDRIER => $this->canViewCalendrier($user),
            self::CAN_EDIT_CALENDRIER => false,
            self::CAN_DELETE_CALENDRIER => false,

            // Type Diplôme
            self::CAN_VIEW_TYPE_DIPLOME => $this->canViewTypeDiplome($user),
            self::CAN_EDIT_TYPE_DIPLOME => false,
            self::CAN_DELETE_TYPE_DIPLOME => false,


            default => false,
        };
    }

    // ========== Helpers ==========

    private function isSuperAdmin(Personnel|Etudiant $user): bool
    {
        return $this->effectivePermissionService->isSuperAdmin($user);
    }

    private function hasAnyRole(Personnel $user, array $roles, mixed $subject = null): bool
    {
        return $this->effectivePermissionService->hasAnyPermission($user, $roles, $subject);
    }

    // ========== Département ==========

    private function canViewDepartement(Personnel|Etudiant $user): bool
    {
        // Tout le monde peut voir les départements
        return true;
    }

    private function canEditDepartement(Personnel|Etudiant $user, mixed $subject): bool
    {
        return $user instanceof Personnel && $this->effectivePermissionService->hasAnyPermissionForResource($user, [
            'SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT'
        ], $subject);
    }

    private function canDeleteDepartement(Personnel|Etudiant $user, mixed $subject): bool
    {
        return $user instanceof Personnel && $this->effectivePermissionService->hasAnyPermissionForResource($user, [
            'SUPER_ADMIN',
            'ROLE_ADMIN'
        ], $subject);
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
        if ($this->effectivePermissionService->hasAnyPermissionForResource($user, [
            'SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT'
        ], $subject)) {
            return true;
        }

        if ($this->effectivePermissionService->hasAnyPermissionForResource($user, ['ROLE_RESP_PARCOURS'], $subject)) {
            return true;
        }

        // Le responsable du diplôme peut le modifier
        if ($subject instanceof StructureDiplome) {
            return $subject->getResponsableDiplome() === $user ||
                $subject->getAssistantDiplome() === $user;
        }

        return false;
    }

    private function canDeleteDiplome(Personnel|Etudiant $user, mixed $subject): bool
    {
        return $user instanceof Personnel && $this->effectivePermissionService->hasAnyPermissionForResource($user, [
            'SUPER_ADMIN',
            'ROLE_ADMIN'
        ], $subject);
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
        if ($this->effectivePermissionService->hasAnyPermissionForResource($user, [
            'SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT',
            'ROLE_RESP_PARCOURS',
            'ROLE_DIRECTEUR_ETUDES'
        ], $subject)) {
            return true;
        }

        return false;
    }

    private function canDeleteSemestre(Personnel|Etudiant $user, mixed $subject): bool
    {
        return $user instanceof Personnel && $this->effectivePermissionService->hasAnyPermissionForResource($user, [
            'SUPER_ADMIN',
            'ROLE_ADMIN'
        ], $subject);
    }

    // ========== Groupe ==========

    private function canViewGroupe(Personnel|Etudiant $user): bool
    {
        // Tout le monde peut voir les groupes
        return true;
    }

    private function canEditGroupe(Personnel|Etudiant $user, mixed $subject): bool
    {
        return $user instanceof Personnel && $subject instanceof StructureGroupe
            && $this->hasGroupPermissions($user, $subject, [
            'SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT',
            'ROLE_SCOLARITE',
            'ROLE_ASSISTANT',
            'ROLE_DIRECTEUR_ETUDES'
        ]);
    }

    private function canEditGroupeStructure(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'SUPER_ADMIN'
        ]);
    }

    private function canDeleteGroupe(Personnel|Etudiant $user, mixed $subject): bool
    {
        return $user instanceof Personnel && $subject instanceof StructureGroupe
            && $this->hasGroupPermissions($user, $subject, [
            'SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT'
        ]);
    }

    /** @param string[] $roles */
    private function hasGroupPermissions(Personnel $user, StructureGroupe $group, array $roles): bool
    {
        // Un groupe peut être rattaché à des semestres de plusieurs départements.
        $semesters = $group->getSemestres();
        if ($semesters->isEmpty()) {
            return $group->getId() === null && $this->effectivePermissionService->hasAnyPermissionInDepartment(
                $user, $roles, $this->effectivePermissionService->getActiveDepartementPersonnel($user)?->getDepartement(),
            );
        }

        foreach ($semesters as $semester) {
            if (!$this->effectivePermissionService->hasAnyPermissionInDepartment(
                $user, $roles, $semester->getDepartement(), $semester,
            )) {
                return false;
            }
        }

        return true;
    }

    // ========== UE ==========

    private function canViewUe(Personnel|Etudiant $user): bool
    {
        // Tout le monde peut voir les UE
        return true;
    }

    private function canEditUe(Personnel|Etudiant $user, mixed $subject = null): bool
    {
        return $user instanceof Personnel && $this->effectivePermissionService->hasAnyPermissionForResource($user, [
            'SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT',
            'ROLE_RESP_PARCOURS'
        ], $subject);
    }

    private function canDeleteUe(Personnel|Etudiant $user, mixed $subject): bool
    {
        return $user instanceof Personnel && $this->effectivePermissionService->hasAnyPermissionForResource($user, [
            'SUPER_ADMIN',
            'ROLE_ADMIN'
        ], $subject);
    }

    // ========== PN (Programme National) ==========

    private function canViewPn(Personnel|Etudiant $user): bool
    {
        // Tout le monde peut voir les PN
        return true;
    }

    private function canEditPn(Personnel|Etudiant $user, mixed $subject): bool
    {
        return $user instanceof Personnel && $this->effectivePermissionService->hasAnyPermissionForResource($user, [
            'SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT'
        ], $subject);
    }

    private function canDeletePn(Personnel|Etudiant $user, mixed $subject): bool
    {
        return $user instanceof Personnel && $this->effectivePermissionService->hasAnyPermissionForResource($user, [
            'SUPER_ADMIN',
            'ROLE_ADMIN'
        ], $subject);
    }

    // ========== Annee ==========

    private function canViewAnnee(Personnel|Etudiant $user): bool
    {
        return true;
    }

    private function canEditAnnee(Personnel|Etudiant $user, mixed $subject): bool
    {
        return $user instanceof Personnel && $this->effectivePermissionService->hasAnyPermissionForResource($user, [
            'SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT',
            'ROLE_DIRECTEUR_ETUDES'
        ], $subject);
    }

    private function canDeleteAnnee(Personnel|Etudiant $user, mixed $subject): bool
    {
        return $user instanceof Personnel && $this->effectivePermissionService->hasAnyPermissionForResource($user, [
            'SUPER_ADMIN',
            'ROLE_ADMIN'
        ], $subject);
    }

    // ========== Calendrier ==========

    private function canViewCalendrier(Personnel|Etudiant $user): bool
    {
        return true;
    }

    // ========== Type Diplôme ==========

    private function canViewTypeDiplome(Personnel|Etudiant $user): bool
    {
        return true;
    }

}
