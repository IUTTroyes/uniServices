<?php

namespace UnifolioBundle\Email;

use App\Service\Email\AbstractEmailDefinition;

final class RelanceEvaluateurEmail extends AbstractEmailDefinition
{
    public function getKey(): string
    {
        return 'unifolio.relance_evaluateur';
    }

    public function getLabel(): string
    {
        return 'UniFolio — relance d\'un évaluateur';
    }

    public function getDescription(): string
    {
        return 'Envoyé à un enseignant qui a des évaluations en attente avant (ou après) la limite de la campagne.';
    }

    public function getDefaultSubject(): string
    {
        return '[UniFolio] {{ nb_a_evaluer }} évaluation(s) en attente — {{ campagne_libelle }}';
    }

    public function getHtmlTemplatePath(): string
    {
        return 'emails/unifolio/relance_evaluateur.html.twig';
    }

    public function getAvailableVariables(): array
    {
        return [
            'evaluateur_nom' => 'Nom et prénom de l\'enseignant',
            'campagne_libelle' => 'Libellé de la campagne',
            'nb_a_evaluer' => 'Nombre d\'éléments en attente d\'évaluation',
            'limite' => 'Date limite d\'évaluation (vide si aucune)',
            'en_retard' => 'La limite est dépassée (booléen)',
            'urlPortal' => 'Lien vers la file d\'évaluation',
        ];
    }
}
