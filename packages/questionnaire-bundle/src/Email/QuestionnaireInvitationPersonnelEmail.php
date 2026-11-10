<?php

namespace QuestionnaireBundle\Email;

use QuestionnaireBundle\Enum\TypeNotificationEnum;

/**
 * Invitation d'un personnel (compte de l'application).
 * Même gabarit et mêmes variables que « questionnaire.invitation », dont il reprend la personnalisation tant qu'il n'est pas
 * personnalisé lui-même. Envoyé comme notification (préférences du destinataire).
 */
final class QuestionnaireInvitationPersonnelEmail extends QuestionnaireInvitationEmail
{
    public function getKey(): string
    {
        return TypeNotificationEnum::INVITATION_PERSONNEL->cle();
    }

    public function getLabel(): string
    {
        return 'Questionnaires — invitation (personnel)';
    }

    public function getParentKey(): string
    {
        return 'questionnaire.invitation';
    }

    public function getCategorie(): string
    {
        return self::CATEGORIE_NOTIFICATION;
    }
}
