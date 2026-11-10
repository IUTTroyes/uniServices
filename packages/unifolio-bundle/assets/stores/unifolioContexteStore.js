import { defineStore } from 'pinia';
import unifolioService from '../requests/unifolioService';

const CAPACITES_VIDES = {
  acces: false,
  etudiant: false,
  correcteur: false,
  referent: false,
  moderateur: false,
};

export const useUnifolioContexteStore = defineStore('unifolioContexte', {
  state: () => ({
    capacites: { ...CAPACITES_VIDES },
    scolarite: null,
    isLoaded: false,
    isLoading: false,
    error: null,
  }),

  getters: {
    peut: (state) => (capacite) => state.capacites[capacite] === true,
  },

  actions: {
    async charger(force = false) {
      if ((this.isLoaded && !force) || this.isLoading) return;
      this.isLoading = true;
      this.error = null;
      try {
        const { data } = await unifolioService.getContexte();
        this.capacites = { ...CAPACITES_VIDES, ...(data?.capacites ?? {}) };
        this.scolarite = data?.scolarite ?? null;
        this.isLoaded = true;
      } catch (e) {
        this.capacites = { ...CAPACITES_VIDES };
        this.scolarite = null;
        this.error = 'Impossible de charger votre espace UniFolio.';
      } finally {
        this.isLoading = false;
      }
    },
  },
});
