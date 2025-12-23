import {defineStore} from 'pinia'
import {ref} from 'vue'
import {getAnneesService} from '@requests'

export const useAnneeStore = defineStore('annee', () => {

  const annees = ref({});

  const getAnneesDepartement = async (departementId) => {
    try {
      const params = { departement: departementId };
      annees.value = await getAnneesService(params);
    } catch (error) {
      console.error('Error fetching annee:', error);
    }
  };

  const getAnneesDiplome = async (diplomeId) => {
    try {
      const params = { diplome: diplomeId };
      return await getAnneesService(params);
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
