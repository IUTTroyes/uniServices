import { useAnneeUnivStore } from '@stores';

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
    const selectedAnneeUniv = localStorage.getItem('selectedAnneeUniv');
    if (!selectedAnneeUniv) {
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
    }

    console.log('Application data initialized successfully');
  } catch (error) {
    console.error('Error initializing application data:', error);
  }
};
