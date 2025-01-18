import {defineStore} from 'pinia'
import {ref} from 'vue'
import {getAllAnneesUniversitaires, getCurrentAnneeUniversitaire} from '@requests'

export const useAnneeUnivStore = defineStore('anneeUniv', () => {

  const anneesUniv = ref([]);
  const anneeUniv = ref(null);

  const getAllAnneesUniv = async () => {
    try {
      anneesUniv.value = await getAllAnneesUniversitaires();
    } catch (error) {
      console.error('Error fetching user:', error);
    }
  };

  const getCurrentAnneeUniv = async () => {
    try {
        anneeUniv.value = await getCurrentAnneeUniversitaire();
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
