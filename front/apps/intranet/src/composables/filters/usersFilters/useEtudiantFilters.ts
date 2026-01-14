import { createFilters } from '../createFilters';
import { FilterMatchMode } from '@primevue/core/api';

const defaultEtudiantFilters = {
  numEtudiant: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
  nom: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
  prenom: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
};

export function useEtudiantFilters() {
  return createFilters(defaultEtudiantFilters);
}
