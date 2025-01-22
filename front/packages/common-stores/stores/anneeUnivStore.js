import {defineStore} from 'pinia'
import {ref} from 'vue'
import {getAllAnneesUniversitairesService, getCurrentAnneeUniversitaireService} from '@requests'

export const useAnneeUnivStore = defineStore('anneeUniv', () => {

  const anneesUniv = ref([]);
  const anneeUniv = ref(null);

  const getAllAnneesUniv = async () => {
    try {
      anneesUniv.value = await getAllAnneesUniversitairesService();
    } catch (error) {
      console.error('Error fetching user:', error);
    }
  };

  const getCurrentAnneeUniv = async () => {
    try {
        anneeUniv.value = await getCurrentAnneeUniversitaireService();
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
