<?php

namespace App\Email;

use App\Service\Email\AbstractEmailDefinition;

final class NotificationResumeEmail extends AbstractEmailDefinition
{
    public function getKey(): string
    {
        return 'notification.resume';
    }

    public function getLabel(): string
    {
        return 'Résumé des notifications';
    }

    public function getDescription(): string
    {
        return 'Un seul e-mail regroupant les événements récents de toute l\'application (quotidien ou hebdomadaire, au choix de chacun).';
    }

    public function getDefaultSubject(): string
    {
        return '{{ nb_total }} nouveauté(s) {{ periode }}';
    }

    public function getHtmlTemplatePath(): string
    {
        return 'emails/core/notification_resume.html.twig';
    }

    public function getAvailableVariables(): array
    {
        return [
            'destinataire_nom' => 'Nom et prénom du destinataire',
            'periode' => '« de la journée » ou « de la semaine »',
            'nb_total' => 'Nombre total d\'événements',
            'rubriques' => 'Rubriques : libelle, nombre, elements (texte, url, nombre), reste',
            'urlPreferences' => 'Chemin des préférences de notification',
            'urlPortal' => 'Chemin du portail',
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
