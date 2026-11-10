<?php

namespace App\Security;

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
        if ($this->effectivePermissionService->isSuperAdmin($user)) {
            return true;
        }

        // Les ressources APC partagent la même règle par action.
        // Pour l’écriture, le sujet permet de résoudre le diplôme du responsable de parcours.
        return match ($attribute) {
            self::CAN_VIEW_APC_REFERENTIEL,
            self::CAN_VIEW_APC_COMPETENCE,
            self::CAN_VIEW_APC_NIVEAU,
            self::CAN_VIEW_APC_PARCOURS,
            self::CAN_VIEW_APC_AC => true,

            self::CAN_EDIT_APC_REFERENTIEL,
            self::CAN_EDIT_APC_COMPETENCE,
            self::CAN_EDIT_APC_NIVEAU,
            self::CAN_EDIT_APC_PARCOURS,
            self::CAN_EDIT_APC_AC => $user instanceof Personnel
                && $this->effectivePermissionService->hasAnyPermissionForResource($user, [
                    'SUPER_ADMIN',
                    'ROLE_ADMIN',
                    'ROLE_CHEF_DEPT',
                    'ROLE_RESP_PARCOURS',
                ], $subject),

            self::CAN_DELETE_APC_REFERENTIEL,
            self::CAN_DELETE_APC_COMPETENCE,
            self::CAN_DELETE_APC_NIVEAU,
            self::CAN_DELETE_APC_PARCOURS,
            self::CAN_DELETE_APC_AC => $user instanceof Personnel
                && $this->effectivePermissionService->hasAnyPermissionForResource($user, ['ROLE_ADMIN'], $subject),

            default => false,
        };
    }
}
