<?php

namespace App\Email;

use App\Service\Email\AbstractEmailDefinition;

/** Réponse à « mot de passe oublié » pour un compte dont le mot de passe est géré par l'université. */
final class MotDePasseUniversitaireEmail extends AbstractEmailDefinition
{
    public function getKey(): string
    {
        return 'core.mot_de_passe_universitaire';
    }

    public function getLabel(): string
    {
        return 'Mot de passe géré par l’université';
    }

    public function getDefaultSubject(): string
    {
        return 'Votre mot de passe universitaire';
    }

    public function getHtmlTemplatePath(): string
    {
        return 'emails/core/mot_de_passe_universitaire.html.twig';
    }

    public function getAvailableVariables(): array
    {
        return [
            'user'   => 'Objet User (prenom, nom, email)',
            'casUrl' => 'Adresse de gestion du mot de passe universitaire',
        ];
    }

    public function getDescription(): string
    {
        return 'Envoyé à la place du lien de réinitialisation quand le compte n’a pas de mot de passe dans UniServices.';
    }

    public function getBundle(): string
    {
        return 'Core';
    }
}
