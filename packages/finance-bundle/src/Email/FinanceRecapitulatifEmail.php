<?php

namespace FinanceBundle\Email;

use App\Service\Email\AbstractEmailDefinition;

/**
 * Récapitulatif des demandes d'achat en attente, envoyé deux fois par jour ouvré (finance:recapitulatif) au service
 * financier et aux valideurs. Même clé que le type de notification : il remplace l'e-mail générique.
 * Contexte en valeurs simples (FinanceRecapitulatif).
 */
final class FinanceRecapitulatifEmail extends AbstractEmailDefinition
{
    public function getKey(): string
    {
        return 'finance.recapitulatif';
    }

    public function getLabel(): string
    {
        return 'Finances — récapitulatif des demandes en attente';
    }

    public function getDescription(): string
    {
        return 'Chaque jour ouvré à 8 h et 13 h 30, au service financier et aux valideurs : les demandes d\'achat en attente, par statut.';
    }

    public function getDefaultSubject(): string
    {
        return '{{ total }} demande(s) d\'achat en attente';
    }

    public function getHtmlTemplatePath(): string
    {
        return '@Finance/emails/finance/recapitulatif.html.twig';
    }

    public function getAvailableVariables(): array
    {
        return [
            'destinataire_nom' => 'Nom du destinataire',
            'total' => 'Nombre de demandes en attente',
            'resume' => 'Résumé par statut (texte)',
            'groupes' => 'Liste des statuts : statut, nombre, autres (au-delà de 10), lignes (objet, departement, demandeur, montant, numero, depuis, lien)',
            'lien' => 'Lien vers le tableau de bord des finances',
        ];
    }

    /** Envoyé comme notification : réception réglée par le destinataire (préférences). */
    public function getCategorie(): string
    {
        return self::CATEGORIE_NOTIFICATION;
    }
}
