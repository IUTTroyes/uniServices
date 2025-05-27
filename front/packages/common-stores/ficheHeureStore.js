import { defineStore } from 'pinia';
import * as ficheHeureService from '@requests/ficheHeureService'; // Assuming alias

export const useFicheHeureStore = defineStore('ficheHeure', {
  state: () => ({
    mesFichesHeures: [],
    fichesHeuresPourValidation: [],
    currentFicheHeure: null,
    isLoadingMesFichesHeures: false,
    isLoadingFichesHeuresPourValidation: false,
    isLoadingCurrentFicheHeure: false,
    isSubmitting: false, // Used for create, update, submit
    isValidating: false,
    isRejecting: false,
    error: null,
  }),
  getters: {
    // Example: getFicheHeureByIdFromList: (state) => (id) => 
    //   state.mesFichesHeures.find(fh => fh.id === id) || state.fichesHeuresPourValidation.find(fh => fh.id === id),
    // A more complete example considering currentFicheHeure as well:
    getFicheHeureById: (state) => (id) => {
      if (state.currentFicheHeure && state.currentFicheHeure.id === id) {
        return state.currentFicheHeure;
      }
      let fiche = state.mesFichesHeures.find(fh => fh.id === id);
      if (fiche) return fiche;
      fiche = state.fichesHeuresPourValidation.find(fh => fh.id === id);
      return fiche;
    },
  },
  actions: {
    async fetchMesFichesHeures(params) {
      this.isLoadingMesFichesHeures = true;
      this.error = null;
      try {
        const response = await ficheHeureService.getMesFichesHeures(params);
        this.mesFichesHeures = response.data || response['hydra:member'] || response; 
      } catch (err) {
        this.error = err.message || 'Failed to fetch own time sheets';
      }
      this.isLoadingMesFichesHeures = false;
    },

    async fetchFicheHeure(id) {
      this.isLoadingCurrentFicheHeure = true;
      this.error = null;
      try {
        const response = await ficheHeureService.getFicheHeure(id);
        this.currentFicheHeure = response.data || response;
      } catch (err) {
        this.error = err.message || 'Failed to fetch time sheet details';
      }
      this.isLoadingCurrentFicheHeure = false;
    },

    async createFicheHeure(data) {
      this.isSubmitting = true; 
      this.error = null;
      try {
        const response = await ficheHeureService.createFicheHeure(data);
        // Optionally add to mesFichesHeures list or trigger a refetch
        // const newItem = response.data || response;
        // if (newItem) this.mesFichesHeures.push(newItem);
        this.isSubmitting = false; // Set to false on success before returning
        return response.data || response;
      } catch (err) {
        this.error = err.message || 'Failed to create time sheet';
        this.isSubmitting = false; // Set to false on error
        throw err; 
      }
      // This line is unreachable due to return/throw in try/catch, so isSubmitting=false is moved up
      // this.isSubmitting = false; 
    },

    async updateFicheHeure(id, data) {
      this.isSubmitting = true;
      this.error = null;
      try {
        const response = await ficheHeureService.updateFicheHeure(id, data);
        const updatedItem = response.data || response;
        // Update in mesFichesHeures list and/or currentFicheHeure
        // const index = this.mesFichesHeures.findIndex(fh => fh.id === id);
        // if (index !== -1 && updatedItem) this.mesFichesHeures[index] = updatedItem;
        // if (this.currentFicheHeure && this.currentFicheHeure.id === id && updatedItem) this.currentFicheHeure = updatedItem;
        this.isSubmitting = false;
        return updatedItem;
      } catch (err) {
        this.error = err.message || 'Failed to update time sheet';
        this.isSubmitting = false;
        throw err;
      }
    },

    async submitFicheHeure(id) {
      this.isSubmitting = true;
      this.error = null;
      try {
        const response = await ficheHeureService.submitFicheHeure(id);
        const updatedItem = response.data || response;
        // Update status in lists/current object
        // For example, if the response contains the updated item:
        // const index = this.mesFichesHeures.findIndex(fh => fh.id === id);
        // if (index !== -1 && updatedItem) this.mesFichesHeures[index] = updatedItem;
        // if (this.currentFicheHeure && this.currentFicheHeure.id === id && updatedItem) this.currentFicheHeure = updatedItem;
        this.isSubmitting = false;
        return updatedItem;
      } catch (err) {
        this.error = err.message || 'Failed to submit time sheet';
        this.isSubmitting = false;
        throw err;
      }
    },

    async fetchFichesHeuresPourValidation(params) {
      this.isLoadingFichesHeuresPourValidation = true;
      this.error = null;
      try {
        const response = await ficheHeureService.getFichesHeuresPourValidation(params);
        this.fichesHeuresPourValidation = response.data || response['hydra:member'] || response;
      } catch (err) {
        this.error = err.message || 'Failed to fetch time sheets for validation';
      }
      this.isLoadingFichesHeuresPourValidation = false;
    },

    async validateFicheHeure(id) {
      this.isValidating = true;
      this.error = null;
      try {
        const response = await ficheHeureService.validateFicheHeure(id);
        const updatedItem = response.data || response;
        // Update status in lists/current object (e.g., remove from fichesHeuresPourValidation, update in mesFichesHeures if present)
        // this.fichesHeuresPourValidation = this.fichesHeuresPourValidation.filter(fh => fh.id !== id);
        // const index = this.mesFichesHeures.findIndex(fh => fh.id === id);
        // if (index !== -1 && updatedItem) this.mesFichesHeures[index] = updatedItem;
        // if (this.currentFicheHeure && this.currentFicheHeure.id === id && updatedItem) this.currentFicheHeure = updatedItem;
        this.isValidating = false;
        return updatedItem;
      } catch (err) {
        this.error = err.message || 'Failed to validate time sheet';
        this.isValidating = false;
        throw err;
      }
    },

    async rejectFicheHeure(id, commentaire) {
      this.isRejecting = true;
      this.error = null;
      try {
        const response = await ficheHeureService.rejectFicheHeure(id, commentaire);
        const updatedItem = response.data || response;
        // Update status in lists/current object
        // this.fichesHeuresPourValidation = this.fichesHeuresPourValidation.filter(fh => fh.id !== id);
        // const index = this.mesFichesHeures.findIndex(fh => fh.id === id);
        // if (index !== -1 && updatedItem) this.mesFichesHeures[index] = updatedItem;
        // if (this.currentFicheHeure && this.currentFicheHeure.id === id && updatedItem) this.currentFicheHeure = updatedItem;
        this.isRejecting = false;
        return updatedItem;
      } catch (err) {
        this.error = err.message || 'Failed to reject time sheet';
        this.isRejecting = false;
        throw err;
      }
    },

    clearCurrentFicheHeure() {
      this.currentFicheHeure = null;
    }
  }
});
