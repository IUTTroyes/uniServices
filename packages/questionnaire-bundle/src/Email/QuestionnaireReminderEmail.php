<?php

namespace QuestionnaireBundle\Email;

use App\Service\Email\AbstractEmailDefinition;

/**
 * Envoyé aux participants qui n'ont pas encore envoyé leurs réponses (relance manuelle de l'équipe ou relance automatique).
 * Contexte fourni par QuestionnaireMailer (valeurs simples).
 */
class QuestionnaireReminderEmail extends AbstractEmailDefinition
{
    public function getKey(): string
    {
        return 'questionnaire.reminder';
    }

    public function getLabel(): string
    {
        return 'Questionnaires — relance d\'un participant (adresse extérieure)';
    }

    public function getDescription(): string
    {
        return 'Envoyé aux participants qui n\'ont pas encore envoyé leurs réponses (relance manuelle de l\'équipe ou relance automatique).';
    }

    public function getDefaultSubject(): string
    {
        return '{% if objet %}{{ objet }}{% else %}Rappel : questionnaire « {{ questionnaire_titre }} » en attente{% endif %}';
    }

    public function getHtmlTemplatePath(): string
    {
        return '@Questionnaire/emails/questionnaire/reminder.html.twig';
    }

    public function getTxtTemplatePath(): string
    {
        return '@Questionnaire/emails/questionnaire/reminder.txt.twig';
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
            'objet' => 'Objet saisi par l\'équipe pour une relance manuelle (vide sinon)',
            'message' => 'Message saisi par l\'équipe pour une relance manuelle (vide sinon)',
        ];
    }
}
