import { defineStore } from 'pinia';
import traceService from '../requests/traceService';

/** Données de référence des traces : années de BUT de l'étudiant et référentiel par année (cache). */
export const useTraceReferenceStore = defineStore('unifolioTraceReference', {
  state: () => ({
    scolarites: [],
    scolaritesChargees: false,
    referentiels: {},
    /** Granularité imposée par la formation, par année (null = libre). */
    granularites: {},
  }),

  getters: {
    scolariteActive: (state) => state.scolarites.find((s) => s.active) ?? state.scolarites[0] ?? null,
  },

  actions: {
    async chargerScolarites() {
      if (this.scolaritesChargees) return this.scolarites;
      const { data } = await traceService.scolarites();
      this.scolarites = data ?? [];
      this.scolaritesChargees = true;
      return this.scolarites;
    },

    async referentiel(scolariteId) {
      if (!scolariteId) return [];
      if (!this.referentiels[scolariteId]) {
        const { data } = await traceService.referentiel(scolariteId);
        this.referentiels[scolariteId] = data?.competences ?? [];
        this.granularites[scolariteId] = data?.granularite?.value ?? null;
      }
      return this.referentiels[scolariteId];
    },
  },
});
