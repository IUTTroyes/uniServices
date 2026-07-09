<?php

namespace App\Email;

use App\Services\Email\AbstractEmailDefinition;

/**
 * Définition de l'email de réinitialisation de mot de passe.
 * Email envoyé depuis le core de l'application (pas un package).
 */
final class ResetPasswordEmail extends AbstractEmailDefinition
{
    public function getKey(): string
    {
        return 'core.reset_password';
    }

    public function getLabel(): string
    {
        return 'Réinitialisation de mot de passe';
    }

    public function getDefaultSubject(): string
    {
        return 'Réinitialisation de votre mot de passe UniServices';
    }

    public function getHtmlTemplatePath(): string
    {
        return 'emails/core/reset_password.html.twig';
    }

    public function getAvailableVariables(): array
    {
        return [
            'user'     => 'Objet User (prenom, nom, email)',
            'resetUrl' => 'URL de réinitialisation du mot de passe',
        ];
    }

    public function getDescription(): string
    {
        return 'Envoyé lorsqu\'un utilisateur demande la réinitialisation de son mot de passe.';
    }

    public function getBundle(): string
    {
        return 'Core';
    }
}
