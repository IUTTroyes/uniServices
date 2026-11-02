<?php

namespace MessageBundle\Email;

use App\Service\Email\AbstractEmailDefinition;

class MessageNotificationEmail extends AbstractEmailDefinition
{
    public function getKey(): string
    {
        return 'message.new_message';
    }

    public function getLabel(): string
    {
        return 'Nouveau message interne';
    }

    public function getDescription(): string
    {
        return 'Envoyé aux étudiants lorsqu\'un enseignant ou personnel publie un nouveau message avec option copie email.';
    }

    public function getDefaultSubject(): string
    {
        return '[Messagerie] {{ message.sujet }}';
    }

    public function getHtmlTemplatePath(): string
    {
        return 'emails/message/new_message.html.twig';
    }

    public function getAvailableVariables(): array
    {
        return [
            'destinataire_nom' => 'Nom et prénom du destinataire',
            'expediteur_nom' => 'Nom et prénom de l\'expéditeur',
            'sujet' => 'Sujet du message',
            'contenu' => 'Contenu du message',
            'date_envoi' => 'Date d\'envoi du message',
            'prioritaire' => 'Indique si le message est prioritaire (booléen)',
        ];
    }
}

