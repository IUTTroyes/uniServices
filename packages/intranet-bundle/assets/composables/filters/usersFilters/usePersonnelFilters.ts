import { createFilters } from '../createFilters';
import { FilterMatchMode } from '@primevue/core/api';

const defaultPersonnelFilters = {
    nom: { value: null, matchMode: FilterMatchMode.CONTAINS },
    prenom: { value: null, matchMode: FilterMatchMode.CONTAINS },
    statut: { value: null, matchMode: FilterMatchMode.EQUALS },
    numeroHarpege: { value: null, matchMode: FilterMatchMode.EQUALS },
    mailUniv: { value: null, matchMode: FilterMatchMode.EQUALS },
};

export function usePersonnelFilters() {
    return createFilters(defaultPersonnelFilters);
}
