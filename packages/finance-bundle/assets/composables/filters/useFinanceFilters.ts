import { createFilters } from '@composables/filters/createFilters';
import { FilterMatchMode } from '@primevue/core/api';

export const defaultFinanceFilters = {
    centreFinancier: { value: null, matchMode: FilterMatchMode.EQUALS },
    gestionnaire: { value: null, matchMode: FilterMatchMode.CONTAINS },
    numeroBonCommande: { value: null, matchMode: FilterMatchMode.CONTAINS },
    'fournisseur.numeroFournisseur': { value: null, matchMode: FilterMatchMode.CONTAINS },
    'fournisseur.nomFournisseur': { value: null, matchMode: FilterMatchMode.CONTAINS },
    objetDemande: { value: null, matchMode: FilterMatchMode.CONTAINS },
    pjSurSifac: { value: null, matchMode: FilterMatchMode.EQUALS },
    bcTransmis: { value: null, matchMode: FilterMatchMode.EQUALS },
    factureFinale: { value: null, matchMode: FilterMatchMode.EQUALS },
    statut: { value: null, matchMode: FilterMatchMode.EQUALS },
};

export function useFinanceFilters() {
    return createFilters(defaultFinanceFilters);
}
