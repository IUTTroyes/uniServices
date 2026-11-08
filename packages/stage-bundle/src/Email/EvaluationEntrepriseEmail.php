<?php

namespace StageBundle\Email;

use App\Service\Email\AbstractEmailDefinition;

/** Lien d'évaluation du stagiaire envoyé au tuteur en entreprise (sans compte). */
final class EvaluationEntrepriseEmail extends AbstractEmailDefinition
{
    public function getKey(): string
    {
        return 'stage.evaluation_entreprise';
    }

    public function getLabel(): string
    {
        return 'Stages — évaluation par le tuteur en entreprise';
    }

    public function getDescription(): string
    {
        return 'Envoyé au tuteur en entreprise avec le lien (à usage unique) du formulaire d\'évaluation du stagiaire.';
    }

    public function getDefaultSubject(): string
    {
        return 'Évaluation de votre stagiaire';
    }

    public function getHtmlTemplatePath(): string
    {
        return '@Stage/emails/evaluation_entreprise.html.twig';
    }

    public function getAvailableVariables(): array
    {
        return [
            'tuteur_nom' => 'Nom du tuteur en entreprise',
            'etudiant_nom' => 'Nom et prénom du stagiaire',
            'periode' => 'Période de stage',
            'entreprise' => 'Organisme d\'accueil',
            'etablissement' => 'Établissement (IUT)',
            'expediteur' => 'Personne qui envoie la demande',
            'url' => 'Lien du formulaire (à usage unique)',
            'expire_le' => 'Date limite d\'utilisation du lien',
        ];
    }
}
