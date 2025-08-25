import {ref} from 'vue';
import { useAnneeUnivStore, useUsersStore, useDiplomeStore, useAnneeStore } from '@stores';

/**
 * Initialize application data
 * This function fetches essential data needed by the application on startup
 * It initializes academic year data and user data in a streamlined way
 */
export const initializeAppData = async () => {
  try {
    console.log('Initializing application data...');

    // Initialize academic year data
    await initializeAcademicYearData();

    // Initialize user data
    await initializeUserData();

    console.log('Application data initialized successfully');
  } catch (error) {
    console.error('Error initializing application data:', error);
  }
};

/**
 * Initialize academic year data
 * This function fetches and sets up the academic year data
 */
const initializeAcademicYearData = async () => {
  // Get the annee universitaire store
  const anneeUnivStore = useAnneeUnivStore();

  // Fetch all annees universitaires
  await anneeUnivStore.getAllAnneesUniv();

  // Check if a selected annee universitaire exists in localStorage
  const selectedAnneeUnivStr = localStorage.getItem('selectedAnneeUniv');

  if (!selectedAnneeUnivStr) {
    // No selected annee in localStorage, set the current one
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
};

/**
 * Initialize user data
 * This function fetches user data and related department data
 */
const initializeUserData = async () => {
  // Get the user store
  const userStore = useUsersStore();

  // Fetch user data
  const user = await userStore.getUser();

  if (user && userStore.userType === 'personnels') {
    const departement = userStore.departementDefaut;

    const diplomeStore = useDiplomeStore();
    // Fetch diplomas for the default department
    await diplomeStore.getDiplomesDepartement(departement.id);
    const diplomes = diplomeStore.diplomes;

    const anneeStore = useAnneeStore();
    // pour chaque diplôme, on récupère les années associées
    for (const diplome of diplomes) {
      // on ajoute dans le tableau des diplômes les années associées
        diplome.annees = await anneeStore.getAnneesDiplome(departement.id);
    }

    console.log(diplomes)

    console.log('User data initialized successfully');
  }
};
