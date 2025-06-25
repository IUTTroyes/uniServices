import { useAnneeUnivStore, useDiplomeStore, useSemestreStore, useAnneeStore } from '@stores';

/**
 * Initialize application data
 * This function fetches essential data needed by the application on startup
 */
export const initializeAppData = async () => {
  try {
    // Get the annee universitaire store
    const anneeUnivStore = useAnneeUnivStore();

    // Fetch all annees universitaires
    await anneeUnivStore.getAllAnneesUniv();

    // If no selected annee universitaire in localStorage, set the current one
    const selectedAnneeUnivStr = localStorage.getItem('selectedAnneeUniv');
    if (!selectedAnneeUnivStr) {
      // Get the current (active) annee universitaire
      await anneeUnivStore.getCurrentAnneeUniv();

      // If we have a current annee, set it as selected
      if (anneeUnivStore.anneeUniv) {
        anneeUnivStore.setSelectedAnneeUniv(anneeUnivStore.anneeUniv);
      } else if (anneeUnivStore.anneesUniv.length > 0) {
        // Fallback to the first annee in the list if no current annee is found
        const sortedAnnees = [...anneeUnivStore.anneesUniv].sort((a, b) => b.libelle.localeCompare(a.libelle));
        anneeUnivStore.setSelectedAnneeUniv(sortedAnnees[0]);
      }
    } else {
      // Load the selected annee from localStorage
      const selectedAnneeUniv = JSON.parse(selectedAnneeUnivStr);

      // Find the corresponding annee in the list to get the current actif status
      const foundAnnee = anneeUnivStore.anneesUniv.find(annee => annee.id === selectedAnneeUniv.id);

      if (foundAnnee) {
        // Update the selected annee with the current actif status
        anneeUnivStore.setSelectedAnneeUniv({
          ...selectedAnneeUniv,
          isActif: foundAnnee.actif
        });
      } else {
        // If not found, just set what we have
        anneeUnivStore.selectedAnneeUniv.value = selectedAnneeUniv;
      }
    }

    console.log('Application data initialized successfully');
  } catch (error) {
    console.error('Error initializing application data:', error);
  }
};
