<?php

namespace App\Notification;

use App\Service\Email\AbstractEmailDefinition;

/**
 * E-mail « tout de suite » d'un type de notification, créé automatiquement par l'EmailRegistry pour chaque type qui
 * n'a pas d'e-mail dédié (même clé). Il rend chaque notification paramétrable dans l'éditeur des e-mails.
 *
 * Tant qu'il n'est pas personnalisé, il hérite du modèle générique « notification.immediate » (personnalisé ou non).
 * Ce n'est pas un service : il est construit à partir du NotificationType.
 */
final class NotificationEmailDefinition extends AbstractEmailDefinition
{
    public const CLE_GENERIQUE = 'notification.immediate';

    public function __construct(private readonly NotificationType $type)
    {
    }

    public function getKey(): string
    {
        return $this->type->cle;
    }

    public function getLabel(): string
    {
        return $this->type->libelle;
    }

    public function getDescription(): string
    {
        return $this->type->description.' — Notification envoyée par e-mail aux personnes qui ont choisi « tout de suite ».';
    }

    public function getDefaultSubject(): string
    {
        return '[{{ source }}] {{ titre }}';
    }

    public function getHtmlTemplatePath(): string
    {
        return 'emails/core/notification_immediate.html.twig';
    }

    public function getCategorie(): string
    {
        return self::CATEGORIE_NOTIFICATION;
    }

    public function getParentKey(): string
    {
        return self::CLE_GENERIQUE;
    }

    public function getAvailableVariables(): array
    {
        return [
            'destinataire_nom' => 'Nom et prénom du destinataire',
            'source' => sprintf('Application émettrice (« %s »)', $this->type->sourceLibelle),
            'titre' => sprintf('Rubrique de la notification (« %s »)', $this->type->rubrique),
            'texte' => 'Ce qui s\'est passé (phrase complète)',
            'urlNotification' => 'Lien vers l\'élément concerné dans l\'application',
        ];
    }
}
