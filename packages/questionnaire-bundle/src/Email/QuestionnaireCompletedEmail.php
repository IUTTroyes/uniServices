<?php

namespace QuestionnaireBundle\Email;

use App\Service\Email\AbstractEmailDefinition;

/**
 * Envoyé à l'auteur quand son questionnaire est clôturé (date limite atteinte ou tous les participants ont répondu).
 * Contexte fourni par QuestionnaireMailer (valeurs simples).
 */
final class QuestionnaireCompletedEmail extends AbstractEmailDefinition
{
    public function getKey(): string
    {
        return 'questionnaire.completed';
    }

    public function getLabel(): string
    {
        return 'Questionnaires — bilan de clôture';
    }

    public function getDescription(): string
    {
        return 'Envoyé à l\'auteur quand son questionnaire est clôturé (date limite atteinte ou tous les participants ont répondu).';
    }

    public function getDefaultSubject(): string
    {
        return 'Questionnaire « {{ questionnaire_titre }} » terminé : {{ reponses }} réponse(s) sur {{ invites }}';
    }

    public function getHtmlTemplatePath(): string
    {
        return '@Questionnaire/emails/questionnaire/completed.html.twig';
    }

    public function getTxtTemplatePath(): string
    {
        return '@Questionnaire/emails/questionnaire/completed.txt.twig';
    }

    public function getAvailableVariables(): array
    {
        return [
            'destinataire_nom' => 'Nom de l\'auteur (peut être vide)',
            'questionnaire_titre' => 'Titre du questionnaire',
            'questionnaire_description' => 'Description du questionnaire (peut être vide)',
            'date_cloture' => 'Date et heure de clôture (vide si aucune)',
            'reponses' => 'Nombre de réponses envoyées',
            'invites' => 'Nombre de participants invités',
            'taux' => 'Taux de participation (%)',
            'complet' => 'Tous les participants ont répondu (booléen)',
            'date_atteinte' => 'La date limite est atteinte (booléen)',
            'lien' => 'Lien vers les résultats',
        ];
    }

    /** Envoyé comme notification : réception réglée par le destinataire (préférences). */
    public function getCategorie(): string
    {
        return self::CATEGORIE_NOTIFICATION;
    }
}
