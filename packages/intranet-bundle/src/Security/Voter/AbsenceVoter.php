<?php

namespace IntranetBundle\Security\Voter;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Security\UserEffectivePermissionService;
use IntranetBundle\Entity\Etudiant\EtudiantAbsence;
use IntranetBundle\Entity\Etudiant\EtudiantAbsenceJustificatif;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/** Droits sur les absences et leurs justificatifs. */
class AbsenceVoter extends Voter
{
    // Permissions Absences
    public const CAN_VIEW_ABSENCE = 'CAN_VIEW_ABSENCE';
    public const CAN_EDIT_ABSENCE = 'CAN_EDIT_ABSENCE';
    public const CAN_DELETE_ABSENCE = 'CAN_DELETE_ABSENCE';

    // Permissions Justificatifs d'absence
    public const CAN_VIEW_JUSTIFICATIF = 'CAN_VIEW_JUSTIFICATIF';
    public const CAN_EDIT_JUSTIFICATIF = 'CAN_EDIT_JUSTIFICATIF';
    public const CAN_DELETE_JUSTIFICATIF = 'CAN_DELETE_JUSTIFICATIF';

    private const SUPPORTED_ATTRIBUTES = [
        self::CAN_VIEW_ABSENCE,
        self::CAN_EDIT_ABSENCE,
        self::CAN_DELETE_ABSENCE,
        self::CAN_VIEW_JUSTIFICATIF,
        self::CAN_EDIT_JUSTIFICATIF,
        self::CAN_DELETE_JUSTIFICATIF,
    ];

    public function __construct(
        private readonly UserEffectivePermissionService $effectivePermissionService,
    ) {}

    public function supportsAttribute(string $attribute): bool
    {
        return in_array($attribute, self::SUPPORTED_ATTRIBUTES, true);
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $this->supportsAttribute($attribute);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!($user instanceof Etudiant || $user instanceof Personnel)) {
            $vote?->addReason('L\'utilisateur n\'est pas connecté.');
            return false;
        }

        if ($this->effectivePermissionService->isSuperAdmin($user)) {
            return true;
        }

        return match($attribute) {
            // Absences
            self::CAN_VIEW_ABSENCE => $this->canViewAbsence($subject, $user),
            self::CAN_EDIT_ABSENCE => $this->canEditAbsence($subject, $user),
            self::CAN_DELETE_ABSENCE => $this->canDeleteAbsence($subject, $user),

            // Justificatifs d'absence
            self::CAN_VIEW_JUSTIFICATIF => $this->canViewJustificatif($subject, $user),
            self::CAN_EDIT_JUSTIFICATIF => $this->canEditJustificatif($subject, $user),
            self::CAN_DELETE_JUSTIFICATIF => $this->canDeleteJustificatif($subject, $user),
            default => false,
        };
    }

    private function hasAnyRole(Personnel $user, array $roles, mixed $subject = null): bool
    {
        return $this->effectivePermissionService->hasAnyPermission($user, $roles, $subject);
    }

    // ========== Absences ==========

    private function canViewAbsence(mixed $subject, Personnel|Etudiant $user): bool
    {
        if ($user instanceof Personnel) {
            return $this->hasAnyRole($user, [
                'SUPER_ADMIN',
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
        return $user instanceof Personnel && $subject instanceof EtudiantAbsence
            && $this->effectivePermissionService->hasAnyPermissionForResource($user, [
                'ROLE_ADMIN', 'ROLE_SCOLARITE', 'ROLE_CHEF_DEPT', 'ROLE_DIRECTEUR_ETUDES', 'ROLE_PERMANENT',
            ], $subject);
    }

    private function canDeleteAbsence(mixed $subject, Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $subject instanceof EtudiantAbsence
            && $this->effectivePermissionService->hasAnyPermissionForResource(
                $user, ['ROLE_ADMIN', 'ROLE_SCOLARITE'], $subject,
            );
    }

    // ========== Justificatifs d'absence ==========

    private function canViewJustificatif(mixed $subject, Personnel|Etudiant $user): bool
    {
        if ($user instanceof Personnel) {
            return $this->hasAnyRole($user, [
                'SUPER_ADMIN',
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
        if (!$subject instanceof EtudiantAbsenceJustificatif) {
            return false;
        }

        if ($user instanceof Personnel) {
            return $this->effectivePermissionService->hasAnyPermissionForResource($user, [
                'ROLE_ADMIN', 'ROLE_SCOLARITE', 'ROLE_CHEF_DEPT', 'ROLE_DIRECTEUR_ETUDES',
            ], $subject);
        }

        // L’étudiant soumet un justificatif pour sa propre scolarité.
        return $subject->getId() === null && $subject->getScolariteSemestre()?->getScolarite()?->getEtudiant() === $user;
    }

    private function canDeleteJustificatif(mixed $subject, Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $subject instanceof EtudiantAbsenceJustificatif
            && $this->effectivePermissionService->hasAnyPermissionForResource(
                $user, ['ROLE_ADMIN', 'ROLE_SCOLARITE'], $subject,
            );
    }
}
