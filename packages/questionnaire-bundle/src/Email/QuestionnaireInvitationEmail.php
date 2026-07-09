<?php

namespace QuestionnaireBundle\Email;

use App\Services\Email\AbstractEmailDefinition;

/**
 * Définition de l'email d'invitation à répondre à un questionnaire.
 */
final class QuestionnaireInvitationEmail extends AbstractEmailDefinition
{
    public function getKey(): string
    {
        return 'questionnaire.invitation';
    }

    public function getLabel(): string
    {
        return 'Invitation à répondre à un questionnaire';
    }

    public function getDefaultSubject(): string
    {
        return 'Vous avez été invité(e) à répondre au questionnaire : {{ questionnaire.titre }}';
    }

    public function getHtmlTemplatePath(): string
    {
        return 'emails/questionnaire/invitation.html.twig';
    }

    public function getAvailableVariables(): array
    {
        return [
            'invitation'    => 'Objet QuestionnaireInvitation (token, email)',
            'questionnaire' => 'Objet Questionnaire (titre, description)',
            'surveyUrl'     => 'URL directe pour accéder au questionnaire',
            'expiresAt'     => 'Date d\'expiration de l\'invitation (si définie)',
        ];
    }

    public function getDescription(): string
    {
        return 'Envoyé lorsqu\'un utilisateur est invité à répondre à un questionnaire.';
    }
}
