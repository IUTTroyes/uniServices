<?php

namespace QuestionnaireBundle\Email;

use App\Service\Email\AbstractEmailDefinition;

/**
 * Définition de l'email envoyé à l'auteur lors de la complétion / clôture d'un questionnaire.
 */
final class QuestionnaireCompletedEmail extends AbstractEmailDefinition
{
    public function getKey(): string
    {
        return 'questionnaire.completed';
    }

    public function getLabel(): string
    {
        return 'Notification de clôture / questionnaire complété';
    }

    public function getDefaultSubject(): string
    {
        return 'Votre questionnaire est terminé : {{ questionnaire.title }}';
    }

    public function getHtmlTemplatePath(): string
    {
        return '@Questionnaire/emails/questionnaire/completed.html.twig';
    }

    public function getTxtTemplatePath(): ?string
    {
        return '@Questionnaire/emails/questionnaire/completed.txt.twig';
    }

    public function getAvailableVariables(): array
    {
        return [
            'questionnaire' => 'Objet Questionnaire (titre, dates, etc.)',
            'authorName'    => 'Nom complet de l\'auteur ou null',
            'stats'         => 'Tableau de statistiques (total, submitted, pending, rate, isDateExpired, isFullyCompleted)',
            'analyticsUrl'  => 'URL directe vers la page des statistiques/résultats',
        ];
    }

    public function getDescription(): string
    {
        return 'Envoyé à l\'auteur du questionnaire lorsque celui-ci est clôturé ou que 100% des réponses ont été reçues.';
    }
}
