import apiCall from '@helpers/apiCall.js'
import api from '@helpers/axios.js'

const getGroupesSemestreService = async (semestre,showToast = false) => {
  try {
    return await apiCall(
        api.get,
        [`/api/structure_groupes/semestre/${semestre}`],
        'Années universitaires récupérées avec succès',
        'Erreur lors de la récupération des années universitaires',
        showToast
    );
  } catch (error) {
    console.error('Erreur dans getAllAnneesUniversitairesService:', error);
    throw error;
  }
}

const getTypesGroupesService = async (showToast = false) => {
  try {
    return await apiCall(
        api.get,
        ['/api/structure_groupes/types'],
        'Types de groupes récupérés avec succès',
        'Erreur lors de la récupération des types de groupes',
        showToast
    );
  } catch (error) {
    console.error('Erreur dans getTypesGroupesService:', error);
    throw error;
  }
}

export { getGroupesSemestreService };
