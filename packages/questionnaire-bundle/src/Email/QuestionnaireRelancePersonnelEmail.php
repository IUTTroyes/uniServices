<?php

namespace QuestionnaireBundle\Email;

use QuestionnaireBundle\Enum\TypeNotificationEnum;

/**
 * Relance d'un personnel (compte de l'application).
 * Même gabarit et mêmes variables que « questionnaire.reminder », dont il reprend la personnalisation tant qu'il n'est pas
 * personnalisé lui-même. Envoyé comme notification (préférences du destinataire).
 */
final class QuestionnaireRelancePersonnelEmail extends QuestionnaireReminderEmail
{
    public function getKey(): string
    {
        return TypeNotificationEnum::RELANCE_PERSONNEL->cle();
    }

    public function getLabel(): string
    {
        return 'Questionnaires — relance (personnel)';
    }

    public function getParentKey(): string
    {
        return 'questionnaire.reminder';
    }

    public function getCategorie(): string
    {
        return self::CATEGORIE_NOTIFICATION;
    }
}
