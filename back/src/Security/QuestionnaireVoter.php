<?php

namespace App\Security;

use App\Entity\Questionnaires\Questionnaire;
use App\Entity\Questionnaires\QuestionnaireInvitation;
use App\Entity\Questionnaires\QuestionnaireQuestion;
use App\Entity\Questionnaires\QuestionnaireSection;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter pour les opérations sur les entités Questionnaire
 * Gère les droits d'accès pour : Questionnaire, Section, Question, Invitation, Réponses
 */
class QuestionnaireVoter extends Voter
{
    // Permissions Questionnaire
    public const CAN_VIEW_QUESTIONNAIRE = 'CAN_VIEW_QUESTIONNAIRE';
    public const CAN_EDIT_QUESTIONNAIRE = 'CAN_EDIT_QUESTIONNAIRE';
    public const CAN_DELETE_QUESTIONNAIRE = 'CAN_DELETE_QUESTIONNAIRE';
    public const CAN_PUBLISH_QUESTIONNAIRE = 'CAN_PUBLISH_QUESTIONNAIRE';

    // Permissions Section
    public const CAN_VIEW_QUESTION_SECTION = 'CAN_VIEW_QUESTION_SECTION';
    public const CAN_EDIT_QUESTION_SECTION = 'CAN_EDIT_QUESTION_SECTION';
    public const CAN_DELETE_QUESTION_SECTION = 'CAN_DELETE_QUESTION_SECTION';

    // Permissions Question
    public const CAN_VIEW_QUESTION = 'CAN_VIEW_QUESTION';
    public const CAN_EDIT_QUESTION = 'CAN_EDIT_QUESTION';
    public const CAN_DELETE_QUESTION = 'CAN_DELETE_QUESTION';

    // Permissions Invitation
    public const CAN_VIEW_INVITATION = 'CAN_VIEW_INVITATION';
    public const CAN_EDIT_INVITATION = 'CAN_EDIT_INVITATION';
    public const CAN_DELETE_INVITATION = 'CAN_DELETE_INVITATION';

    // Permissions Réponses
    public const CAN_VIEW_ANSWERS = 'CAN_VIEW_ANSWERS';
    public const CAN_SUBMIT_ANSWERS = 'CAN_SUBMIT_ANSWERS';

    private const SUPPORTED_ATTRIBUTES = [
        self::CAN_VIEW_QUESTIONNAIRE,
        self::CAN_EDIT_QUESTIONNAIRE,
        self::CAN_DELETE_QUESTIONNAIRE,
        self::CAN_PUBLISH_QUESTIONNAIRE,
        self::CAN_VIEW_QUESTION_SECTION,
        self::CAN_EDIT_QUESTION_SECTION,
        self::CAN_DELETE_QUESTION_SECTION,
        self::CAN_VIEW_QUESTION,
        self::CAN_EDIT_QUESTION,
        self::CAN_DELETE_QUESTION,
        self::CAN_VIEW_INVITATION,
        self::CAN_EDIT_INVITATION,
        self::CAN_DELETE_INVITATION,
        self::CAN_VIEW_ANSWERS,
        self::CAN_SUBMIT_ANSWERS,
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
            // Questionnaire
            self::CAN_VIEW_QUESTIONNAIRE => $this->canViewQuestionnaire($subject, $user),
            self::CAN_EDIT_QUESTIONNAIRE => $this->canEditQuestionnaire($user),
            self::CAN_DELETE_QUESTIONNAIRE => $this->canDeleteQuestionnaire($user),
            self::CAN_PUBLISH_QUESTIONNAIRE => $this->canPublishQuestionnaire($user),

            // Section
            self::CAN_VIEW_QUESTION_SECTION => $this->canViewSection($user),
            self::CAN_EDIT_QUESTION_SECTION => $this->canEditSection($user),
            self::CAN_DELETE_QUESTION_SECTION => $this->canDeleteSection($user),

            // Question
            self::CAN_VIEW_QUESTION => $this->canViewQuestion($user),
            self::CAN_EDIT_QUESTION => $this->canEditQuestion($user),
            self::CAN_DELETE_QUESTION => $this->canDeleteQuestion($user),

            // Invitation
            self::CAN_VIEW_INVITATION => $this->canViewInvitation($subject, $user),
            self::CAN_EDIT_INVITATION => $this->canEditInvitation($user),
            self::CAN_DELETE_INVITATION => $this->canDeleteInvitation($user),

            // Réponses
            self::CAN_VIEW_ANSWERS => $this->canViewAnswers($user),
            self::CAN_SUBMIT_ANSWERS => $this->canSubmitAnswers($subject, $user),

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

    // ========== Questionnaire ==========

    private function canViewQuestionnaire(mixed $subject, Personnel|Etudiant $user): bool
    {
        // Le personnel peut voir tous les questionnaires
        if ($user instanceof Personnel) {
            return $this->hasAnyRole($user, [
                'ROLE_SUPER_ADMIN',
                'ROLE_ADMIN',
                'ROLE_CHEF_DEPT',
                'ROLE_QUALITE',
                'ROLE_RESP_PARCOURS',
                'ROLE_DIRECTEUR_ETUDES',
                'ROLE_PERMANENT'
            ]);
        }

        // Un étudiant peut voir les questionnaires auxquels il est invité
        // Cette vérification plus fine serait faite avec l'invitation
        return true;
    }

    private function canEditQuestionnaire(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT',
            'ROLE_QUALITE',
            'ROLE_RESP_PARCOURS'
        ]);
    }

    private function canDeleteQuestionnaire(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_QUALITE'
        ]);
    }

    private function canPublishQuestionnaire(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_CHEF_DEPT',
            'ROLE_QUALITE'
        ]);
    }

    // ========== Section ==========

    private function canViewSection(Personnel|Etudiant $user): bool
    {
        // Même logique que questionnaire
        return true;
    }

    private function canEditSection(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_QUALITE',
            'ROLE_RESP_PARCOURS'
        ]);
    }

    private function canDeleteSection(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_QUALITE'
        ]);
    }

    // ========== Question ==========

    private function canViewQuestion(Personnel|Etudiant $user): bool
    {
        return true;
    }

    private function canEditQuestion(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_QUALITE',
            'ROLE_RESP_PARCOURS'
        ]);
    }

    private function canDeleteQuestion(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_QUALITE'
        ]);
    }

    // ========== Invitation ==========

    private function canViewInvitation(mixed $subject, Personnel|Etudiant $user): bool
    {
        if ($user instanceof Personnel) {
            return $this->hasAnyRole($user, [
                'ROLE_SUPER_ADMIN',
                'ROLE_ADMIN',
                'ROLE_QUALITE',
                'ROLE_CHEF_DEPT'
            ]);
        }

        // Un étudiant peut voir ses propres invitations (via token)
        // La vérification se fait au niveau du token dans le contrôleur
        return $user instanceof Etudiant;
    }

    private function canEditInvitation(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_QUALITE'
        ]);
    }

    private function canDeleteInvitation(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_QUALITE'
        ]);
    }

    // ========== Réponses ==========

    private function canViewAnswers(Personnel|Etudiant $user): bool
    {
        return $user instanceof Personnel && $this->hasAnyRole($user, [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_QUALITE',
            'ROLE_CHEF_DEPT',
            'ROLE_RESP_PARCOURS',
            'ROLE_DIRECTEUR_ETUDES'
        ]);
    }

    private function canSubmitAnswers(mixed $subject, Personnel|Etudiant $user): bool
    {
        // Les étudiants peuvent soumettre des réponses aux questionnaires auxquels ils sont invités
        if ($user instanceof Etudiant) {
            return true; // La vérification plus fine se fait au niveau de l'invitation
        }

        // Le personnel peut aussi répondre aux questionnaires s'ils y sont invités
        return $user instanceof Personnel;
    }
}


