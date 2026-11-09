<?php

namespace QuestionnaireBundle\Security;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireInvitation;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireQuestion;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireSection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Droits sur un questionnaire et ce qui en dépend (section, question, invitation : le questionnaire parent fait foi).
 * Les règles sont dans QuestionnaireAccess.
 */
class QuestionnaireVoter extends Voter
{
    /** Consulter le questionnaire, sa structure, ses invitations et ses résultats. */
    public const VIEW = 'QUESTIONNAIRE_VIEW';
    /** Modifier les paramètres et la structure, publier, inviter, relancer. */
    public const MANAGE = 'QUESTIONNAIRE_MANAGE';
    public const DELETE = 'QUESTIONNAIRE_DELETE';
    /** Lire les résultats agrégés (statistiques, exports) : y compris les destinataires de la synthèse partagée. */
    public const SYNTHESE = 'QUESTIONNAIRE_SYNTHESE';
    /** Créer le questionnaire dans son département (ou l'y déplacer). */
    public const CREATE = 'QUESTIONNAIRE_CREATE';

    private const ATTRIBUTS = [self::VIEW, self::MANAGE, self::DELETE, self::CREATE, self::SYNTHESE];

    public function __construct(private readonly QuestionnaireAccess $access)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, self::ATTRIBUTS, true) && self::questionnaire($subject) !== null;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof Personnel && !$user instanceof Etudiant) {
            $vote?->addReason('Utilisateur non connecté.');

            return false;
        }
        $questionnaire = self::questionnaire($subject);

        return match ($attribute) {
            self::VIEW => $this->access->peutVoir($user, $questionnaire),
            self::SYNTHESE => $this->access->peutVoirSynthese($user, $questionnaire),
            self::MANAGE => $this->access->peutGerer($user, $questionnaire),
            self::DELETE => $this->access->peutSupprimer($user, $questionnaire),
            self::CREATE => $this->access->peutCreer($user, $questionnaire->getDepartement()),
            default => false,
        };
    }

    public static function questionnaire(mixed $subject): ?Questionnaire
    {
        return match (true) {
            $subject instanceof Questionnaire => $subject,
            $subject instanceof QuestionnaireSection => $subject->getQuestionnaire(),
            $subject instanceof QuestionnaireQuestion => $subject->getSection()?->getQuestionnaire(),
            $subject instanceof QuestionnaireInvitation => $subject->getQuestionnaire(),
            default => null,
        };
    }
}
