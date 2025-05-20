import {defineStore} from 'pinia'
import {ref} from 'vue'
import {getAllAnneesUniversitairesService, getCurrentAnneeUniversitaireService, getAnneeUniversitaireService } from '@requests'

export const useAnneeUnivStore = defineStore('anneeUniv', () => {

  const anneesUniv = ref([]);
  const anneeUniv = ref(null);
  const selectedAnneeUniv = ref(null);

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

  const getAnneeUniv = async (id) => {
    try {
      anneeUniv.value = await getAnneeUniversitaireService(id);
    } catch (error) {
      console.error('Error fetching user:', error);
    }
  };

  const setSelectedAnneeUniversitaire = (annee) => {
    selectedAnneeUniv.value = annee;
  }

  return {
    getAllAnneesUniv,
    getCurrentAnneeUniv,
    getAnneeUniv,
    anneeUniv,
    anneesUniv,
    selectedAnneeUniv,
    setSelectedAnneeUniversitaire
  };
})
