<?php

namespace App\Email;

use App\Service\Email\AbstractEmailDefinition;

final class NotificationImmediateEmail extends AbstractEmailDefinition
{
    public function getKey(): string
    {
        return 'notification.immediate';
    }

    public function getLabel(): string
    {
        return 'Notification immédiate';
    }

    public function getDescription(): string
    {
        return 'E-mail générique d\'une notification, pour les personnes qui ont choisi d\'être prévenues tout de suite. Un package peut fournir son propre gabarit.';
    }

    public function getDefaultSubject(): string
    {
        return '[{{ source }}] {{ titre }}';
    }

    public function getHtmlTemplatePath(): string
    {
        return 'emails/core/notification_immediate.html.twig';
    }

    public function getAvailableVariables(): array
    {
        return [
            'destinataire_nom' => 'Nom et prénom du destinataire',
            'source' => 'Package émetteur (« UniFolio »)',
            'titre' => 'Rubrique de la notification',
            'texte' => 'Ce qui s\'est passé',
            'urlNotification' => 'Chemin de l\'élément concerné dans l\'application',
        ];
    }

    /** Modèles communs à toutes les applications : regroupés avec le cœur dans l'éditeur. */
    public function getBundle(): string
    {
        return 'Core';
    }

    /** Envoyé comme notification : réception réglée par le destinataire (préférences). */
    public function getCategorie(): string
    {
        return self::CATEGORIE_NOTIFICATION;
    }
}
