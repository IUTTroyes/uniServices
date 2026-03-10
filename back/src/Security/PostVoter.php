<?php

namespace App\Security;

use App\Entity\Etudiant\EtudiantAbsence;
use App\Entity\Etudiant\EtudiantAbsenceJustificatif;
use App\Entity\Etudiant\EtudiantNote;
use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Etudiant\EtudiantScolariteSemestre;
use App\Entity\Scolarite\ScolEvaluation;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter pour les opérations POST/PATCH/DELETE sur les entités principales
 * Gère les droits d'accès pour : Etudiant, Scolarité, Evaluation, Notes, Absences
 */
class PostVoter extends Voter
{
    // Permissions Etudiant
    public const CAN_VIEW_ETUDIANT = 'CAN_VIEW_ETUDIANT';
    public const CAN_EDIT_ETUDIANT = 'CAN_EDIT_ETUDIANT';
    public const CAN_DELETE_ETUDIANT = 'CAN_DELETE_ETUDIANT';

    // Permissions Scolarité (EtudiantScolarite)
    public const CAN_VIEW_ETUDIANT_SCOLARITE = 'CAN_VIEW_ETUDIANT_SCOLARITE';
    public const CAN_EDIT_ETUDIANT_SCOLARITE = 'CAN_EDIT_ETUDIANT_SCOLARITE';
    public const CAN_DELETE_ETUDIANT_SCOLARITE = 'CAN_DELETE_ETUDIANT_SCOLARITE';

    // Permissions Scolarité Semestre
    public const CAN_VIEW_SCOL = 'CAN_VIEW_SCOL';
    public const CAN_EDIT_SCOL = 'CAN_EDIT_SCOL';
    public const CAN_DELETE_SCOL = 'CAN_DELETE_SCOL';

    // Permissions Evaluation
    public const CAN_VIEW_EVAL = 'CAN_VIEW_EVAL';
    public const CAN_EDIT_EVAL = 'CAN_EDIT_EVAL';
    public const CAN_DELETE_EVAL = 'CAN_DELETE_EVAL';

    // Permissions Notes
    public const CAN_VIEW_NOTES = 'CAN_VIEW_NOTES';
    public const CAN_EDIT_NOTES = 'CAN_EDIT_NOTES';
    public const CAN_DELETE_NOTES = 'CAN_DELETE_NOTES';

    // Permissions Absences
    public const CAN_VIEW_ABSENCE = 'CAN_VIEW_ABSENCE';
    public const CAN_EDIT_ABSENCE = 'CAN_EDIT_ABSENCE';
    public const CAN_DELETE_ABSENCE = 'CAN_DELETE_ABSENCE';

    // Permissions Justificatifs d'absence
    public const CAN_VIEW_JUSTIFICATIF = 'CAN_VIEW_JUSTIFICATIF';
    public const CAN_EDIT_JUSTIFICATIF = 'CAN_EDIT_JUSTIFICATIF';
    public const CAN_DELETE_JUSTIFICATIF = 'CAN_DELETE_JUSTIFICATIF';

    // Permissions Année Universitaire
    public const CAN_VIEW_ANNEE_UNIV = 'CAN_VIEW_ANNEE_UNIV';
    public const CAN_EDIT_ANNEE_UNIV = 'CAN_EDIT_ANNEE_UNIV';

    private const SUPPORTED_ATTRIBUTES = [
        self::CAN_VIEW_ETUDIANT,
        self::CAN_EDIT_ETUDIANT,
        self::CAN_DELETE_ETUDIANT,
        self::CAN_VIEW_ETUDIANT_SCOLARITE,
        self::CAN_EDIT_ETUDIANT_SCOLARITE,
        self::CAN_DELETE_ETUDIANT_SCOLARITE,
        self::CAN_VIEW_SCOL,
        self::CAN_EDIT_SCOL,
        self::CAN_DELETE_SCOL,
        self::CAN_VIEW_EVAL,
        self::CAN_EDIT_EVAL,
        self::CAN_DELETE_EVAL,
        self::CAN_VIEW_NOTES,
        self::CAN_EDIT_NOTES,
        self::CAN_DELETE_NOTES,
        self::CAN_VIEW_ABSENCE,
        self::CAN_EDIT_ABSENCE,
        self::CAN_DELETE_ABSENCE,
        self::CAN_VIEW_JUSTIFICATIF,
        self::CAN_EDIT_JUSTIFICATIF,
        self::CAN_DELETE_JUSTIFICATIF,
        self::CAN_VIEW_ANNEE_UNIV,
        self::CAN_EDIT_ANNEE_UNIV,
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
            // Etudiant
            self::CAN_VIEW_ETUDIANT => $this->canViewEtudiant($subject, $user),
            self::CAN_EDIT_ETUDIANT => $this->canEditEtudiant($subject, $user),
            self::CAN_DELETE_ETUDIANT => $this->canDeleteEtudiant($user),

            // Scolarité
            self::CAN_VIEW_SCOL => $this->canViewScolarite($subject, $user),
            self::CAN_EDIT_SCOL => $this->canEditScolarite($user),
            self::CAN_DELETE_SCOL => $this->canDeleteScolarite($user),

            // Evaluation
            self::CAN_VIEW_EVAL => $this->canViewEvaluation($subject, $user),
            self::CAN_EDIT_EVAL => $this->canEditEvaluation($subject, $user),
            self::CAN_DELETE_EVAL => $this->canDeleteEvaluation($subject, $user),

            // Notes
            self::CAN_VIEW_NOTES => $this->canViewNotes($subject, $user),
            self::CAN_EDIT_NOTES => $this->canEditNotes($subject, $user),
            self::CAN_DELETE_NOTES => $this->canDeleteNotes($subject, $user),

            // Absences
            self::CAN_VIEW_ABSENCE => $this->canViewAbsence($subject, $user),
            self::CAN_EDIT_ABSENCE => $this->canEditAbsence($subject, $user),
            self::CAN_DELETE_ABSENCE => $this->canDeleteAbsence($user),

            // Justificatifs d'absence
            self::CAN_VIEW_JUSTIFICATIF => $this->canViewJustificatif($subject, $user),
            self::CAN_EDIT_JUSTIFICATIF => $this->canEditJustificatif($subject, $user),
            self::CAN_DELETE_JUSTIFICATIF => $this->canDeleteJustificatif($user),

            // Scolarité Etudiant
            self::CAN_VIEW_ETUDIANT_SCOLARITE => $this->canViewEtudiantScolarite($subject, $user),
            self::CAN_EDIT_ETUDIANT_SCOLARITE => $this->canEditEtudiantScolarite($user),
            self::CAN_DELETE_ETUDIANT_SCOLARITE => $this->canDeleteEtudiantScolarite($user),

            // Année Universitaire
            self::CAN_VIEW_ANNEE_UNIV => $this->canViewAnneeUniv($user),
            self::CAN_EDIT_ANNEE_UNIV => $this->canEditAnneeUniv($user),

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

    // ========== Etudiant ==========

    private function canViewEtudiant(mixed $subject, Personnel|Etudiant $user): bool
    {
        // Tout personnel connecté peut voir les étudiants
        if ($user instanceof Personnel) {
            return true;
        }

        // Un étudiant peut voir son propre profil
        return $user instanceof Etudiant && $user === $subject;
    }

    private function canEditEtudiant(mixed $subject, Personnel|Etudiant $user): bool
    {
        if ($user instanceof Personnel) {
            return $this->hasAnyRole($user, [
                'ROLE_SUPER_ADMIN',
                'ROLE_ADMIN',
                'ROLE_SCOLARITE',
                'ROLE_CHEF_DEPT'
            ]);
        }

        // Un étudiant peut modifier son propre profil (certains champs uniquement)
        return $user instanceof Etudiant && $user === $subject;
    }

    private function canDeleteEtudiant(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN'
        ]);
    }

    // ========== Scolarité ==========

    private function canViewScolarite(mixed $subject, Personnel|Etudiant $user): bool
    {
        // Personnel avec rôles scolarité peut voir
        if ($user instanceof Personnel) {
            return $this->hasAnyRole($user, [
                'ROLE_SUPER_ADMIN',
                'ROLE_ADMIN',
                'ROLE_SCOLARITE',
                'ROLE_CHEF_DEPT',
                'ROLE_ASSISTANT',
                'ROLE_DIRECTEUR_ETUDES',
                'ROLE_RESP_PARCOURS',
                'ROLE_PERMANENT'
            ]);
        }

        // Un étudiant peut voir sa propre scolarité
        if ($user instanceof Etudiant && $subject instanceof EtudiantScolariteSemestre) {
            $scolarite = $subject->getScolarite();
            return $scolarite?->getEtudiant() === $user;
        }

        return false;
    }

    private function canEditScolarite(Personnel|Etudiant $user): bool
    {
        if ($user instanceof Personnel) {
            return $this->hasAnyRole($user, [
                'ROLE_SUPER_ADMIN',
                'ROLE_ADMIN',
                'ROLE_SCOLARITE',
                'ROLE_CHEF_DEPT',
                'ROLE_ASSISTANT',
                'ROLE_DIRECTEUR_ETUDES',
                'ROLE_RESP_PARCOURS'
            ]);
        }

        return false;
    }

    private function canDeleteScolarite(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_SCOLARITE'
        ]);
    }

    // ========== Evaluation ==========

    private function canViewEvaluation(mixed $subject, Personnel|Etudiant $user): bool
    {
        // Personnel autorisé ou avec rôles appropriés peut voir
        if ($user instanceof Personnel) {
            if ($this->hasAnyRole($user, [
                'ROLE_SUPER_ADMIN',
                'ROLE_ADMIN',
                'ROLE_CHEF_DEPT',
                'ROLE_RESP_PARCOURS',
                'ROLE_RESP_NOTES',
                'ROLE_PERMANENT'
            ])) {
                return true;
            }

            // Vérifier si le personnel est autorisé sur cette évaluation
            if ($subject instanceof ScolEvaluation) {
                return $subject->getPersonnelAutorise()->contains($user);
            }
        }

        // Un étudiant peut voir les évaluations où il est concerné
        if ($user instanceof Etudiant && $subject instanceof ScolEvaluation) {
            return $subject->isVisible() === true;
        }

        return false;
    }

    private function canEditEvaluation(mixed $subject, Personnel|Etudiant $user): bool
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
            'ROLE_RESP_NOTES'
        ])) {
            return true;
        }

        // Personnel autorisé sur cette évaluation
        if ($subject instanceof ScolEvaluation) {
            return $subject->getPersonnelAutorise()->contains($user);
        }

        return false;
    }

    private function canDeleteEvaluation(mixed $subject, Personnel|Etudiant $user): bool
    {
        if (!$user instanceof Personnel) {
            return false;
        }

        return $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT',
            'ROLE_RESP_NOTES'
        ]);
    }

    // ========== Notes ==========

    private function canViewNotes(mixed $subject, Personnel|Etudiant $user): bool
    {
        if ($user instanceof Personnel) {
            if ($this->hasAnyRole($user, [
                'ROLE_SUPER_ADMIN',
                'ROLE_ADMIN',
                'ROLE_CHEF_DEPT',
                'ROLE_RESP_PARCOURS',
                'ROLE_RESP_NOTES',
                'ROLE_PERMANENT'
            ])) {
                return true;
            }

            // Vérifier si le personnel est autorisé sur l'évaluation liée
            if ($subject instanceof EtudiantNote) {
                $evaluation = $subject->getEvaluation();
                if ($evaluation instanceof ScolEvaluation) {
                    return $evaluation->getPersonnelAutorise()->contains($user);
                }
            }
        }

        // Un étudiant peut voir ses propres notes si l'évaluation est visible
        if ($user instanceof Etudiant && $subject instanceof EtudiantNote) {
            $scolariteSemestre = $subject->getScolariteSemestre();
            $evaluation = $subject->getEvaluation();
            if ($scolariteSemestre && $evaluation) {
                $etudiant = $scolariteSemestre->getScolarite()?->getEtudiant();
                return $etudiant === $user && $evaluation->isVisible() === true;
            }
        }

        return false;
    }

    private function canEditNotes(mixed $subject, Personnel|Etudiant $user): bool
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
            'ROLE_RESP_NOTES'
        ])) {
            return true;
        }

        // Vérifier si le personnel est autorisé sur l'évaluation liée
        if ($subject instanceof EtudiantNote) {
            $evaluation = $subject->getEvaluation();
            if ($evaluation instanceof ScolEvaluation) {
                return $evaluation->getPersonnelAutorise()->contains($user);
            }
        }

        return false;
    }

    private function canDeleteNotes(mixed $subject, Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_RESP_NOTES'
        ]);
    }

    // ========== Absences ==========

    private function canViewAbsence(mixed $subject, Personnel|Etudiant $user): bool
    {
        if ($user instanceof Personnel) {
            return $this->hasAnyRole($user, [
                'ROLE_SUPER_ADMIN',
                'ROLE_ADMIN',
                'ROLE_SCOLARITE',
                'ROLE_CHEF_DEPT',
                'ROLE_ASSISTANT',
                'ROLE_DIRECTEUR_ETUDES',
                'ROLE_PERMANENT'
            ]);
        }

        // Un étudiant peut voir ses propres absences
        if ($user instanceof Etudiant && $subject instanceof EtudiantAbsence) {
            $scolariteSemestre = $subject->getScolariteSemestre();
            if ($scolariteSemestre) {
                $etudiant = $scolariteSemestre->getScolarite()?->getEtudiant();
                return $etudiant === $user;
            }
        }

        return false;
    }

    private function canEditAbsence(mixed $subject, Personnel|Etudiant $user): bool
    {
        if (!$user instanceof Personnel) {
            return false;
        }

        // Rôles avec accès complet aux absences
        if ($this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_SCOLARITE',
            'ROLE_CHEF_DEPT',
            'ROLE_DIRECTEUR_ETUDES'
        ])) {
            return true;
        }

        // Personnel peut saisir des absences
        if ($this->hasAnyRole($user, ['ROLE_PERMANENT'])) {
            return true;
        }

        return false;
    }

    private function canDeleteAbsence(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_SCOLARITE'
        ]);
    }

    // ========== Justificatifs d'absence ==========

    private function canViewJustificatif(mixed $subject, Personnel|Etudiant $user): bool
    {
        if ($user instanceof Personnel) {
            return $this->hasAnyRole($user, [
                'ROLE_SUPER_ADMIN',
                'ROLE_ADMIN',
                'ROLE_SCOLARITE',
                'ROLE_CHEF_DEPT',
                'ROLE_ASSISTANT',
                'ROLE_DIRECTEUR_ETUDES'
            ]);
        }

        // Un étudiant peut voir ses propres justificatifs
        if ($user instanceof Etudiant && $subject instanceof EtudiantAbsenceJustificatif) {
            $absences = $subject->getAbsence();
            foreach ($absences as $absence) {
                $scolariteSemestre = $absence->getScolariteSemestre();
                if ($scolariteSemestre) {
                    $etudiant = $scolariteSemestre->getScolarite()?->getEtudiant();
                    if ($etudiant === $user) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function canEditJustificatif(mixed $subject, Personnel|Etudiant $user): bool
    {
        if ($user instanceof Personnel) {
            return $this->hasAnyRole($user, [
                'ROLE_SUPER_ADMIN',
                'ROLE_ADMIN',
                'ROLE_SCOLARITE',
                'ROLE_CHEF_DEPT',
                'ROLE_DIRECTEUR_ETUDES'
            ]);
        }

        // Un étudiant peut soumettre un justificatif pour ses propres absences
        return $user instanceof Etudiant;
    }

    private function canDeleteJustificatif(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_SCOLARITE'
        ]);
    }

    // ========== Scolarité Etudiant ==========

    private function canViewEtudiantScolarite(mixed $subject, Personnel|Etudiant $user): bool
    {
        if ($user instanceof Personnel) {
            return $this->hasAnyRole($user, [
                'ROLE_SUPER_ADMIN',
                'ROLE_ADMIN',
                'ROLE_SCOLARITE',
                'ROLE_CHEF_DEPT',
                'ROLE_ASSISTANT',
                'ROLE_DIRECTEUR_ETUDES',
                'ROLE_RESP_PARCOURS',
                'ROLE_PERMANENT'
            ]);
        }

        // Un étudiant peut voir sa propre scolarité
        if ($user instanceof Etudiant && $subject instanceof EtudiantScolarite) {
            return $subject->getEtudiant() === $user;
        }

        return false;
    }

    private function canEditEtudiantScolarite(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_SCOLARITE',
            'ROLE_CHEF_DEPT'
        ]);
    }

    private function canDeleteEtudiantScolarite(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_SCOLARITE'
        ]);
    }

    // ========== Année Universitaire ==========

    private function canViewAnneeUniv(Personnel|Etudiant $user): bool
    {
        // Tout le monde peut voir les années universitaires
        return true;
    }

    private function canEditAnneeUniv(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN'
        ]);
    }
}
