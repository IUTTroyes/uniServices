import {defineStore} from 'pinia'
import {ref} from 'vue'
import {getAnneesUniversitairesService, getAnneeUniversitaireService, updateAnneeUniversitaireService } from '@requests'

export const useAnneeUnivStore = defineStore('anneeUniv', () => {

  const anneesUniv = ref([]);
  const anneeUniv = ref(null);
  const selectedAnneeUniv = ref(null);

  const getAllAnneesUniv = async () => {
    try {
      anneesUniv.value = await getAnneesUniversitairesService();
    } catch (error) {
      console.error('Error fetching user:', error);
    }
  };

  const getCurrentAnneeUniv = async () => {
    try {
        const params = { actif: true };
        const currentYears = await getAnneesUniversitairesService(params);
        anneeUniv.value = currentYears[0] ?? null;
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
      await updateAnneeUniversitaireService(id, { actif: true }, '', true);
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
