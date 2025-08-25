import { defineStore } from 'pinia'
import { ref } from 'vue'
import { getDepartementAnneesService, getDiplomeAnneesService } from '@requests'

export const useAnneeStore = defineStore('annee', () => {

  const annees = ref({});

  const getAnneesDepartement = async (departementId) => {
    try {
      annees.value = await getDepartementAnneesService(departementId);
    } catch (error) {
      console.error('Error fetching annee:', error);
    }
  };

  const getAnneesDiplome = async (diplomeId) => {
    try {
      const response = await getDiplomeAnneesService(diplomeId);
      return response;
    } catch (error) {
      console.error('Error fetching annee:', error);
    }
  };

  return {
    getAnneesDepartement,
    annees,
    getAnneesDiplome,
  };
})
