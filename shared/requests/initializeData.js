import {ref} from 'vue';
import { useAnneeUnivStore, useUsersStore, useDiplomeStore, useAnneeStore, useEtablissementStore } from '@stores';

/**
 * Initialize application data
 * This function fetches essential data needed by the application on startup
 * It initializes academic year data and user data in a streamlined way
 */
export const initializeAppData = async () => {
  try {
    await initializeEtablissementData();
    // Initialize academic year data
    await initializeAcademicYearData();
    // Initialize user data
    await initializeUserData();
  } catch (error) {
    console.error('Error initializing application data:', error);
  }
};

const initializeEtablissementData = async () => {
  const etablissementStore = useEtablissementStore();
  await etablissementStore.getEtablissement();
}

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
    const anneeUniv = localStorage.getItem('selectedAnneeUniv') ? JSON.parse(localStorage.getItem('selectedAnneeUniv')) : { id: null };
    const diplomeStore = useDiplomeStore();
    // Fetch diplomas for the default department
    const params = {
      departement: departement.id,
      anneeUniversitaire: anneeUniv.id
    }
    await diplomeStore.getDiplomesDepartement(params);
    const diplomes = diplomeStore.diplomes;

    const anneeStore = useAnneeStore();
    // pour chaque diplôme, on récupère les années associées
    for (const diplome of diplomes) {
      const params = {
        diplome: diplome.id,
        anneeUniversitaire: anneeUniv.id
      }
      // on ajoute dans le tableau des diplômes les années associées
        diplome.annees = await anneeStore.getAnneesDiplome(params);
    }
  }
};
