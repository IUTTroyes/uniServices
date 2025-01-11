import {defineStore} from 'pinia'
import {ref} from 'vue'
import {getAllAnneesUniversitaires} from '@requests'

export const useAnneeUnivStore = defineStore('anneeUniv', () => {

  const anneesUniv = ref([]);

  const getAllAnneesUniv = async () => {
    try {
      anneesUniv.value = await getAllAnneesUniversitaires();
    } catch (error) {
      console.error('Error fetching user:', error);
    }
  };

  return {
    getAllAnneesUniv,
    anneesUniv
  };
})
