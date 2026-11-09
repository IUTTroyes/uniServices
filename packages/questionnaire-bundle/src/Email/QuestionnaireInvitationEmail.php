<?php

namespace QuestionnaireBundle\Email;

use App\Service\Email\AbstractEmailDefinition;

/**
 * Envoyé à chaque destinataire quand un questionnaire est publié ou quand de nouveaux participants sont invités. Le lien est personnel.
 * Contexte fourni par QuestionnaireMailer (valeurs simples).
 */
class QuestionnaireInvitationEmail extends AbstractEmailDefinition
{
    public function getKey(): string
    {
        return 'questionnaire.invitation';
    }

    public function getLabel(): string
    {
        return 'Questionnaires — invitation à répondre (adresse extérieure)';
    }

    public function getDescription(): string
    {
        return 'Envoyé à chaque destinataire quand un questionnaire est publié ou quand de nouveaux participants sont invités. Le lien est personnel.';
    }

    public function getDefaultSubject(): string
    {
        return 'Invitation à répondre au questionnaire « {{ questionnaire_titre }} »';
    }

    public function getHtmlTemplatePath(): string
    {
        return '@Questionnaire/emails/questionnaire/invitation.html.twig';
    }

    public function getTxtTemplatePath(): string
    {
        return '@Questionnaire/emails/questionnaire/invitation.txt.twig';
    }

    public function getAvailableVariables(): array
    {
        return [
            'questionnaire_titre' => 'Titre du questionnaire',
            'questionnaire_description' => 'Description du questionnaire (peut être vide)',
            'date_cloture' => 'Date de clôture (jj/mm/aaaa, vide si aucune)',
            'duree_estimee' => 'Durée estimée en minutes (0 si inconnue)',
            'anonyme' => 'Les réponses sont anonymes (booléen)',
            'lien' => 'Lien personnel pour répondre',
        ];
    }
}
