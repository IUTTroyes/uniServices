import {defineStore} from 'pinia'
import {ref} from 'vue'
import {getServiceAllAnneesUniversitaires, getServiceCurrentAnneeUniversitaire} from '@requests'

export const useAnneeUnivStore = defineStore('anneeUniv', () => {

  const anneesUniv = ref([]);
  const anneeUniv = ref(null);

  const getAllAnneesUniv = async () => {
    try {
      anneesUniv.value = await getServiceAllAnneesUniversitaires();
    } catch (error) {
      console.error('Error fetching user:', error);
    }
  };

  const getCurrentAnneeUniv = async () => {
    try {
        anneeUniv.value = await getServiceCurrentAnneeUniversitaire();
    } catch (error) {
      console.error('Error fetching user:', error);
    }
  }

  return {
    getAllAnneesUniv,
    getCurrentAnneeUniv,
    anneeUniv,
    anneesUniv
  };
})
