<?php

namespace UnifolioBundle\Email;

use App\Service\Email\AbstractEmailDefinition;

final class ResumeNotificationsEmail extends AbstractEmailDefinition
{
    public function getKey(): string
    {
        return 'unifolio.resume_notifications';
    }

    public function getLabel(): string
    {
        return 'UniFolio — résumé des notifications';
    }

    public function getDescription(): string
    {
        return 'Un seul e-mail regroupant les événements récents (quotidien ou hebdomadaire, au choix de chacun).';
    }

    public function getDefaultSubject(): string
    {
        return '[UniFolio] {{ nb_total }} nouveauté(s) {{ periode }}';
    }

    public function getHtmlTemplatePath(): string
    {
        return 'emails/unifolio/resume_notifications.html.twig';
    }

    public function getAvailableVariables(): array
    {
        return [
            'destinataire_nom' => 'Nom et prénom du destinataire',
            'periode' => '« de la journée » ou « de la semaine »',
            'nb_total' => 'Nombre total d\'événements',
            'rubriques' => 'Rubriques : libelle, nombre, elements (texte, url, nombre), reste',
            'urlPreferences' => 'Lien vers les préférences de notification',
            'urlPortal' => 'Lien vers UniFolio',
        ];
    }
}
