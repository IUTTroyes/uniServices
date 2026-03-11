import {defineStore} from 'pinia'
import {ref} from 'vue'
import {getAllAnneesUniversitairesService, getCurrentAnneeUniversitaireService, getAnneeUniversitaireService, activateAnneeUniversitaireService } from '@requests'

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
        console.log('année store', anneeUniv.value)
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

  const setSelectedAnneeUniv = (annee) => {
    // Ensure isActif is correctly set based on actif property
    const isActif = annee.actif !== undefined ? annee.actif : annee.isActif;

    // Create the object to store in localStorage and in the store
    const anneeToStore = {
      id: annee.id,
      libelle: annee.libelle ?? annee.label,
      isActif: isActif
    };

    // Store in localStorage
    localStorage.setItem('selectedAnneeUniv', JSON.stringify(anneeToStore));

    // Update the store value
    selectedAnneeUniv.value = anneeToStore;
  }

  const activateAnneeUniv = async (id) => {
    try {
      await activateAnneeUniversitaireService(id, true);
      // Rafraîchir la liste des années après activation
      await getAllAnneesUniv();
      await getCurrentAnneeUniv();
    } catch (error) {
      console.error('Erreur lors de l\'activation de l\'année universitaire:', error);
      throw error;
    }
  }

  return {
    getAllAnneesUniv,
    getCurrentAnneeUniv,
    getAnneeUniv,
    anneeUniv,
    anneesUniv,
    selectedAnneeUniv,
    setSelectedAnneeUniv,
    activateAnneeUniv,
  };
})
