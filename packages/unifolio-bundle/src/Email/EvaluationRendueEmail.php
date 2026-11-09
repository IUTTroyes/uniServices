<?php

namespace UnifolioBundle\Email;

use App\Service\Email\AbstractEmailDefinition;

final class EvaluationRendueEmail extends AbstractEmailDefinition
{
    public function getKey(): string
    {
        return 'unifolio.evaluation_rendue';
    }

    public function getLabel(): string
    {
        return 'UniFolio — évaluation rendue';
    }

    public function getDescription(): string
    {
        return 'Envoyé à l\'étudiant lorsqu\'un enseignant lui communique une évaluation.';
    }

    public function getDefaultSubject(): string
    {
        return '[UniFolio] Votre travail a été évalué';
    }

    public function getHtmlTemplatePath(): string
    {
        return 'emails/unifolio/evaluation_rendue.html.twig';
    }

    public function getAvailableVariables(): array
    {
        return [
            'etudiant_nom' => 'Nom et prénom de l\'étudiant',
            'evaluateur_nom' => 'Nom et prénom de l\'enseignant',
            'cible' => 'Ce qui a été évalué (trace, compétence ou AC)',
            'trace_titre' => 'Titre de la trace (vide pour un bilan)',
            'score' => 'Positionnement en pourcentage (vide si non chiffré)',
            'appreciation' => 'Appréciation de l\'enseignant',
            'urlPortal' => 'Lien vers UniFolio',
        ];
    }

    /** Envoyé comme notification : réception réglée par le destinataire (préférences). */
    public function getCategorie(): string
    {
        return self::CATEGORIE_NOTIFICATION;
    }
}
