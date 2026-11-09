/**
 * Présentation des statuts d'une demande d'achat : libellé, sévérité du <Tag> PrimeVue et icône.
 * Les règles du circuit (qui peut faire quoi, depuis quel statut) sont côté serveur (FinanceWorkflow).
 */
export const STATUS_WORKFLOW_MAP = {
    BROUILLON: { label: 'Brouillon', severity: 'secondary', icon: 'pi pi-pencil' },
    SOUMIS: { label: 'Demande soumise', severity: 'info', icon: 'pi pi-send' },
    AVIS_DIRECTION: { label: 'Avis de la direction', severity: 'info', icon: 'pi pi-compass' },
    VERIFICATION_FINANCIERE: { label: 'Vérification financière', severity: 'warn', icon: 'pi pi-search' },
    VISA_ENGAGEMENT: { label: 'Visa d\'engagement', severity: 'warn', icon: 'pi pi-check-circle' },
    COMMANDE_VALIDEE: { label: 'Commande validée', severity: 'info', icon: 'pi pi-shopping-cart' },
    COMMANDE_TRANSMISE: { label: 'BC transmis au fournisseur', severity: 'info', icon: 'pi pi-truck' },
    SERVICE_FAIT_CONSTATE: { label: 'Service fait constaté', severity: 'info', icon: 'pi pi-box' },
    SERVICE_FAIT_CERTIFIE: { label: 'Service fait certifié', severity: 'success', icon: 'pi pi-verified' },
    FACTURE_RECUE: { label: 'Facture reçue', severity: 'warn', icon: 'pi pi-receipt' },
    PAYE: { label: 'Payée', severity: 'success', icon: 'pi pi-check' },
    CLOTURE: { label: 'Clôturée', severity: 'secondary', icon: 'pi pi-lock' },
    REJETE: { label: 'Rejetée', severity: 'danger', icon: 'pi pi-times-circle' },
};

/** Présentation d'un statut (repli neutre pour un statut inconnu). */
export function getStatusBadgeInfo(statut) {
    const info = STATUS_WORKFLOW_MAP[statut] ?? { label: statut || 'Inconnu', severity: 'secondary', icon: 'pi pi-info-circle' };
    return { ...info, fullLabel: info.label };
}

/** Statuts regroupés pour les indicateurs du tableau de bord. */
export const STATUTS_INSTRUCTION = ['SOUMIS', 'AVIS_DIRECTION', 'VERIFICATION_FINANCIERE', 'VISA_ENGAGEMENT'];
export const STATUTS_ATTENTE_SERVICE_FAIT = ['COMMANDE_VALIDEE', 'COMMANDE_TRANSMISE', 'SERVICE_FAIT_CONSTATE'];
export const STATUTS_TERMINES = ['PAYE', 'CLOTURE'];
/** Statuts dont le montant est réellement engagé ou dépensé (ni brouillon, ni rejet). */
export const STATUTS_ENGAGES = ['COMMANDE_VALIDEE', 'COMMANDE_TRANSMISE', 'SERVICE_FAIT_CONSTATE', 'SERVICE_FAIT_CERTIFIE', 'FACTURE_RECUE', 'PAYE', 'CLOTURE'];
