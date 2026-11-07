<?php

namespace UnifolioBundle\Email;

use App\Service\Email\AbstractEmailDefinition;

final class NotificationEmail extends AbstractEmailDefinition
{
    public function getKey(): string
    {
        return 'unifolio.notification';
    }

    public function getLabel(): string
    {
        return 'UniFolio — notification';
    }

    public function getDescription(): string
    {
        return 'Notification immédiate d\'un événement (commentaire, dépôt de trace, modération) pour les personnes qui l\'ont choisie.';
    }

    public function getDefaultSubject(): string
    {
        return '[UniFolio] {{ titre }}';
    }

    public function getHtmlTemplatePath(): string
    {
        return 'emails/unifolio/notification.html.twig';
    }

    public function getAvailableVariables(): array
    {
        return [
            'destinataire_nom' => 'Nom et prénom du destinataire',
            'titre' => 'Rubrique de la notification',
            'texte' => 'Ce qui s\'est passé',
            'urlPortal' => 'Lien vers l\'élément concerné',
        ];
    }
}
