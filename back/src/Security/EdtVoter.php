<?php

namespace App\Security;


use App\Entity\Edt\EdtEvent;
use App\Enum\TypeEvenementEdt;
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

        return match($attribute) {
            // EdtEvent
            self::CAN_VIEW_EDT => $this->canViewEdt($subject, $user),
            self::CAN_EDIT_EDT => $this->canEditEdt($subject, $user),
            self::CAN_DELETE_EDT => $this->canDeleteEdt($subject, $user),

            // Contraintes
            self::CAN_VIEW_EDT_CONTRAINTES => $this->canViewContraintes($user),
            self::CAN_EDIT_EDT_CONTRAINTES => false,
            self::CAN_DELETE_EDT_CONTRAINTES => false,

            // Créneaux Interdits
            self::CAN_VIEW_EDT_CRENEAUX => $this->canViewCreneaux($user),
            self::CAN_EDIT_EDT_CRENEAUX => false,
            self::CAN_DELETE_EDT_CRENEAUX => false,

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
        return $this->effectivePermissionService->isSuperAdmin($user);
    }

    private function hasAnyRole(Personnel $user, array $roles): bool
    {
        return $this->effectivePermissionService->hasAnyPermission($user, $roles);
    }

    // ========== EdtEvent ==========

    private function canViewEdt(mixed $subject, Personnel|Etudiant $user): bool
    {
        if (!$subject instanceof EdtEvent) {
            return false;
        }

        if ($subject->getTypeEvenement() === TypeEvenementEdt::Personnel) {
            return $user instanceof Personnel && $subject->getPersonnel() === $user;
        }

        $group = $subject->getGroupe();

        // La collection applique le même critère dans EdtEventAccessExtension.
        return $user instanceof Personnel || ($group !== null && $user->getGroupes()->contains($group));
    }

    private function canEditEdt(mixed $subject, Personnel|Etudiant $user): bool
    {
        if (!$user instanceof Personnel || !$subject instanceof EdtEvent) {
            return false;
        }

        if ($subject->getTypeEvenement() === TypeEvenementEdt::Personnel) {
            // Le type personnel ne peut pas servir à contourner les droits sur un cours.
            return $subject->getPersonnel() === $user
                && $subject->getEnseignement() === null
                && $subject->getSemestre() === null
                && $subject->getGroupe() === null;
        }

        return $this->hasSchedulePermission($user, $subject);
    }

    private function canDeleteEdt(mixed $subject, Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $subject instanceof EdtEvent
            && $subject->getTypeEvenement() !== TypeEvenementEdt::Personnel
            && $this->hasSchedulePermission($user, $subject);
    }

    private function hasSchedulePermission(Personnel $user, EdtEvent $event): bool
    {
        $department = $event->getSemestre()?->getDepartement();
        $roles = ['ROLE_EDT'];
        $groupSemesters = $event->getGroupe()?->getSemestres();
        if ($groupSemesters !== null && !$groupSemesters->isEmpty()) {
            foreach ($groupSemesters as $semester) {
                if (!$this->effectivePermissionService->hasAnyPermissionInDepartment($user, $roles, $semester->getDepartement())) {
                    return false;
                }
            }

            if ($department === null) {
                return true;
            }
        }

        if ($department !== null) {
            return $this->effectivePermissionService->hasAnyPermissionInDepartment($user, $roles, $department);
        }

        if ($event->getEnseignement() !== null) {
            return $this->effectivePermissionService->hasAnyPermissionForTeaching($user, $event->getEnseignement(), $roles);
        }

        // Sans rattachement, seule une création locale peut utiliser le département actif.
        return $event->getId() === null && $this->effectivePermissionService->hasAnyPermissionInDepartment(
            $user, $roles, $this->effectivePermissionService->getActiveDepartementPersonnel($user)?->getDepartement(),
        );
    }

    // ========== Contraintes ==========

    private function canViewContraintes(Personnel|Etudiant $user): bool
    {
        // Le personnel peut voir les contraintes
        return $user instanceof Personnel;
    }

    // ========== Créneaux Interdits ==========

    private function canViewCreneaux(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel;
    }

    // ========== Progression ==========

    private function canViewProgression(mixed $subject, Personnel|Etudiant $user): bool
    {
        if ($user instanceof Personnel) {
            // Rôles avec accès complet
            if ($this->hasAnyRole($user, [
                'SUPER_ADMIN',
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
            'SUPER_ADMIN',
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
            'SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT'
        ]);
    }
}


