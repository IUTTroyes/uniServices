<?php

namespace QuestionnaireBundle\Email;

use App\Service\Email\AbstractEmailDefinition;
use QuestionnaireBundle\Enum\TypeNotificationEnum;

/**
 * Envoyé à chaque collègue destinataire quand les résultats d'un questionnaire sont publiés (après relecture),
 * avec le lien vers sa restitution (seulement les sections qui le concernent).
 */
final class QuestionnaireResultatsEmail extends AbstractEmailDefinition
{
    public function getKey(): string
    {
        return TypeNotificationEnum::RESULTATS->cle();
    }

    public function getLabel(): string
    {
        return 'Questionnaires — résultats disponibles';
    }

    public function getDescription(): string
    {
        return 'Envoyé aux collègues destinataires à la publication des résultats (diffusion validée par l\'équipe).';
    }

    public function getDefaultSubject(): string
    {
        return 'Vos résultats du questionnaire « {{ questionnaire_titre }} »';
    }

    public function getHtmlTemplatePath(): string
    {
        return '@Questionnaire/emails/questionnaire/resultats.html.twig';
    }

    public function getTxtTemplatePath(): string
    {
        return '@Questionnaire/emails/questionnaire/resultats.txt.twig';
    }

    public function getAvailableVariables(): array
    {
        return [
            'destinataire_nom' => 'Nom du destinataire',
            'questionnaire_titre' => 'Titre du questionnaire',
            'sections' => 'Sections qui le concernent (titres séparés par des virgules)',
            'nb_sections' => 'Nombre de sections qui le concernent',
            'anonyme' => 'Les réponses sont anonymes (booléen)',
            'lien' => 'Lien vers ses résultats',
        ];
    }

    /** Envoyé comme notification : réception réglée par le destinataire (préférences). */
    public function getCategorie(): string
    {
        return self::CATEGORIE_NOTIFICATION;
    }
}
