import { ref, watch, type Ref, type WatchOptions } from 'vue';

export function createFilters<F extends Record<string, any>>(defaultState: F) {
  const filters = ref({ ...defaultState }) as Ref<F>;

  const updateFilters = (patch: Partial<F>) => {
    filters.value = { ...filters.value, ...patch } as F;
  };

  const resetFilters = () => {
    filters.value = { ...defaultState } as F;
  };

  // Helper pour attacher la logique de rechargement serveur (ou autre)
  const watchChanges = (cb: (newF: F, oldF: F) => any, options?: WatchOptions) => {
    return watch(filters, cb, { deep: true, ...options });
  };

  return { filters, updateFilters, resetFilters, watchChanges };
}
