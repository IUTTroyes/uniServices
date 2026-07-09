<?php

namespace App\Services\Email;

/**
 * Classe abstraite que chaque email de l'application doit étendre.
 *
 * Convention de nommage des clés :
 *   {bundle}.{nom_email}  →  ex: "questionnaire.invitation", "helpdesk.ticket_created"
 *   Pour les emails du core : "core.{nom_email}"  →  ex: "core.reset_password"
 *
 * Chaque implémentation concrète doit :
 *  - Retourner une clé unique via getKey()
 *  - Fournir un template Twig HTML par défaut via getHtmlTemplatePath()
 *  - Optionnellement un template TXT via getTxtTemplatePath()
 *  - Déclarer les variables disponibles via getAvailableVariables()
 */
abstract class AbstractEmailDefinition
{
    /**
     * Clé unique de l'email, ex : "questionnaire.invitation".
     */
    abstract public function getKey(): string;

    /**
     * Libellé lisible par un humain, affiché dans l'interface d'administration.
     */
    abstract public function getLabel(): string;

    /**
     * Objet (subject) par défaut du mail.
     * Peut contenir des variables Twig, ex : "Invitation au questionnaire {{ survey.titre }}"
     */
    abstract public function getDefaultSubject(): string;

    /**
     * Chemin du template Twig HTML par défaut (relatif au dossier templates/).
     * Ex : "emails/questionnaire/invitation.html.twig"
     */
    abstract public function getHtmlTemplatePath(): string;

    /**
     * Chemin du template Twig TXT par défaut.
     * Retourner null si pas de version texte brut.
     */
    public function getTxtTemplatePath(): ?string
    {
        // Convention : même chemin que HTML mais avec .txt.twig
        $html = $this->getHtmlTemplatePath();
        $txt = str_replace('.html.twig', '.txt.twig', $html);

        return $txt !== $html ? $txt : null;
    }

    /**
     * Variables disponibles dans ce template, avec leur description.
     * Utilisé pour afficher l'aide dans l'interface d'administration.
     *
     * Format : ['nom_variable' => 'Description de la variable']
     *
     * @return array<string, string>
     */
    abstract public function getAvailableVariables(): array;

    /**
     * Description humaine de l'email, affichée dans l'interface admin.
     */
    public function getDescription(): string
    {
        return '';
    }

    /**
     * Namespace/bundle auquel appartient cet email, ex : "Questionnaire", "Helpdesk", "Core".
     * Utilisé pour regrouper les emails dans l'interface d'administration.
     */
    public function getBundle(): string
    {
        // Infère depuis la clé : "questionnaire.invitation" → "questionnaire"
        $parts = explode('.', $this->getKey(), 2);

        return ucfirst($parts[0]);
    }
}
