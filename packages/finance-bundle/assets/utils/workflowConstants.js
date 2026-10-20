/**
 * Constantes et définitions unifiées du workflow Finance & Achats
 * Cohérence stricte : Étape (1 à 5), couleur sémantique, icône et libellés
 */

export const WORKFLOW_STEPS = [
    {
        id: '1',
        stepNumber: 1,
        title: '1. Demande & Avis',
        shortTitle: 'Demande & Avis',
        icon: 'pi pi-file-edit',
        colorName: 'blue',
        primaryColor: '#1d4ed8', // blue-700
        bgLight: 'bg-blue-50/70 dark:bg-blue-950/30',
        borderColor: 'border-blue-200 dark:border-blue-800',
        borderLeft: 'border-l-4 border-l-blue-600',
        textColor: 'text-blue-900 dark:text-blue-200',
        iconColor: 'text-blue-600 dark:text-blue-400',
        dotColor: 'bg-blue-600',
        badgeClass: 'bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800',
        desc: 'Demande initiale, signature externe et avis hiérarchique'
    },
    {
        id: '2',
        stepNumber: 2,
        title: '2. Vérification SIFAC',
        shortTitle: 'Vérification SIFAC',
        icon: 'pi pi-search',
        colorName: 'amber',
        primaryColor: '#d97706', // amber-600
        bgLight: 'bg-amber-50/70 dark:bg-amber-950/30',
        borderColor: 'border-amber-200 dark:border-amber-800',
        borderLeft: 'border-l-4 border-l-amber-600',
        textColor: 'text-amber-900 dark:text-amber-200',
        iconColor: 'text-amber-600 dark:text-amber-400',
        dotColor: 'bg-amber-500',
        badgeClass: 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800',
        desc: 'Contrôle financier, fournisseur et N° BC SIFAC'
    },
    {
        id: '3',
        stepNumber: 3,
        title: '3. Visa Engagement',
        shortTitle: 'Visa Engagement',
        icon: 'pi pi-check-circle',
        colorName: 'purple',
        primaryColor: '#9333ea', // purple-600
        bgLight: 'bg-purple-50/70 dark:bg-purple-950/30',
        borderColor: 'border-purple-200 dark:border-purple-800',
        borderLeft: 'border-l-4 border-l-purple-600',
        textColor: 'text-purple-900 dark:text-purple-200',
        iconColor: 'text-purple-600 dark:text-purple-400',
        dotColor: 'bg-purple-600',
        badgeClass: 'bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800',
        desc: 'Visa CSA / Responsable financier autorisant la commande'
    },
    {
        id: '4',
        stepNumber: 4,
        title: '4. Constatation SF',
        shortTitle: 'Constatation SF',
        icon: 'pi pi-box',
        colorName: 'teal',
        primaryColor: '#0d9488', // teal-600
        bgLight: 'bg-teal-50/70 dark:bg-teal-950/30',
        borderColor: 'border-teal-200 dark:border-teal-800',
        borderLeft: 'border-l-4 border-l-teal-600',
        textColor: 'text-teal-900 dark:text-teal-200',
        iconColor: 'text-teal-600 dark:text-teal-400',
        dotColor: 'bg-teal-600',
        badgeClass: 'bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 border border-teal-200 dark:border-teal-800',
        desc: 'Réception des biens / prestations et PV de service fait'
    },
    {
        id: '5',
        stepNumber: 5,
        title: '5. Facture & Paiement',
        shortTitle: 'Facture & Paiement',
        icon: 'pi pi-wallet',
        colorName: 'rose',
        primaryColor: '#e11d48', // rose-600
        bgLight: 'bg-rose-50/70 dark:bg-rose-950/30',
        borderColor: 'border-rose-200 dark:border-rose-800',
        borderLeft: 'border-l-4 border-l-rose-600',
        textColor: 'text-rose-900 dark:text-rose-200',
        iconColor: 'text-rose-600 dark:text-rose-400',
        dotColor: 'bg-rose-600',
        badgeClass: 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800',
        desc: 'Liquidation de la facture et mise en paiement'
    }
];

export const STATUS_WORKFLOW_MAP = {
    BROUILLON: {
        step: 1,
        stepLabel: 'Étape 1',
        label: '1. Brouillon',
        fullLabel: 'Étape 1 : Brouillon',
        severity: 'secondary',
        icon: 'pi pi-pencil',
        badgeClass: 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-700',
        dotClass: 'bg-slate-500',
        color: '#64748b'
    },
    SOUMIS: {
        step: 1,
        stepLabel: 'Étape 1',
        label: '1. Demande soumise',
        fullLabel: 'Étape 1 : Demande soumise',
        severity: 'info',
        icon: 'pi pi-send',
        badgeClass: 'bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800',
        dotClass: 'bg-blue-600',
        color: '#2563eb'
    },
    AVIS_DIRECTION: {
        step: 1,
        stepLabel: 'Étape 1',
        label: '1. Avis Direction',
        fullLabel: 'Étape 1 : Avis Direction',
        severity: 'info',
        icon: 'pi pi-compass',
        badgeClass: 'bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800',
        dotClass: 'bg-blue-600',
        color: '#2563eb'
    },
    VERIFICATION_FINANCIERE: {
        step: 2,
        stepLabel: 'Étape 2',
        label: '2. Vérif. SIFAC',
        fullLabel: 'Étape 2 : Vérification SIFAC',
        severity: 'warn',
        icon: 'pi pi-search',
        badgeClass: 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800',
        dotClass: 'bg-amber-500',
        color: '#d97706'
    },
    VISA_ENGAGEMENT: {
        step: 3,
        stepLabel: 'Étape 3',
        label: '3. Visa Engagement',
        fullLabel: 'Étape 3 : Visa Engagement',
        severity: 'help',
        icon: 'pi pi-check-circle',
        badgeClass: 'bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800',
        dotClass: 'bg-purple-600',
        color: '#9333ea'
    },
    COMMANDE_VALIDEE: {
        step: 3,
        stepLabel: 'Étape 3',
        label: '3. Commande validée',
        fullLabel: 'Étape 3 : Commande validée',
        severity: 'help',
        icon: 'pi pi-shopping-cart',
        badgeClass: 'bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800',
        dotClass: 'bg-purple-600',
        color: '#9333ea'
    },
    COMMANDE_TRANSMISE: {
        step: 3,
        stepLabel: 'Étape 3',
        label: '3. BC transmis',
        fullLabel: 'Étape 3 : BC transmis fournisseur',
        severity: 'help',
        icon: 'pi pi-truck',
        badgeClass: 'bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800',
        dotClass: 'bg-purple-600',
        color: '#9333ea'
    },
    SERVICE_FAIT_CONSTATE: {
        step: 4,
        stepLabel: 'Étape 4',
        label: '4. Service Fait constaté',
        fullLabel: 'Étape 4 : Service Fait constaté',
        severity: 'success',
        icon: 'pi pi-box',
        badgeClass: 'bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 border border-teal-200 dark:border-teal-800',
        dotClass: 'bg-teal-600',
        color: '#0d9488'
    },
    SERVICE_FAIT_CERTIFIE: {
        step: 4,
        stepLabel: 'Étape 4',
        label: '4. Service Fait certifié',
        fullLabel: 'Étape 4 : Service Fait certifié',
        severity: 'success',
        icon: 'pi pi-verified',
        badgeClass: 'bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 border border-teal-200 dark:border-teal-800',
        dotClass: 'bg-teal-600',
        color: '#0d9488'
    },
    FACTURE_RECUE: {
        step: 5,
        stepLabel: 'Étape 5',
        label: '5. Facture reçue',
        fullLabel: 'Étape 5 : Facture reçue',
        severity: 'warn',
        icon: 'pi pi-receipt',
        badgeClass: 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800',
        dotClass: 'bg-rose-600',
        color: '#e11d48'
    },
    PAYE: {
        step: 5,
        stepLabel: 'Étape 5',
        label: '5. Payé & Soldé',
        fullLabel: 'Étape 5 : Payé & Soldé',
        severity: 'success',
        icon: 'pi pi-dollar',
        badgeClass: 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800',
        dotClass: 'bg-emerald-600',
        color: '#059669'
    },
    CLOTURE: {
        step: 5,
        stepLabel: 'Étape 5',
        label: '5. Clôturé',
        fullLabel: 'Étape 5 : Clôturé',
        severity: 'secondary',
        icon: 'pi pi-lock',
        badgeClass: 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-700',
        dotClass: 'bg-slate-500',
        color: '#64748b'
    },
    REJETE: {
        step: -1,
        stepLabel: 'Rejeté',
        label: 'Rejeté',
        fullLabel: 'Demande rejetée',
        severity: 'danger',
        icon: 'pi pi-times-circle',
        badgeClass: 'bg-red-50 dark:bg-red-950/60 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800',
        dotClass: 'bg-red-600',
        color: '#dc2626'
    },
};

export function getStatusBadgeInfo(statut) {
    return STATUS_WORKFLOW_MAP[statut] || {
        step: 1,
        stepLabel: 'Étape',
        label: statut || 'Inconnu',
        fullLabel: statut || 'Inconnu',
        severity: 'info',
        icon: 'pi pi-info-circle',
        badgeClass: 'bg-slate-100 text-slate-700 border border-slate-200',
        dotClass: 'bg-slate-400',
        color: '#64748b'
    };
}
