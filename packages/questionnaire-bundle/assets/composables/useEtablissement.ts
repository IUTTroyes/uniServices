import { computed, onMounted } from 'vue';
import { useEtablissementStore } from '@stores';

/**
 * Libellé de l'établissement (données de l'établissement), pour désigner les questionnaires sans département.
 * Charge l'établissement une seule fois si le store est vide.
 */
export function useEtablissement() {
  const store = useEtablissementStore();

  onMounted(() => {
    if (!store.etablissement) {
      store.getEtablissement();
    }
  });

  const libelle = computed<string>(() => {
    const e: any = Array.isArray(store.etablissement) ? store.etablissement[0] : store.etablissement;
    return e?.libelle || e?.sigle || 'Établissement';
  });

  /** Libellé d'un questionnaire sans département. */
  const libelleGlobal = computed(() => `${libelle.value} (tous départements)`);

  return { libelle, libelleGlobal };
}
