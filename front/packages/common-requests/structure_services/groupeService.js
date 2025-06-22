import apiCall from '@helpers/apiCall.js'
import api from '@helpers/axios.js'

const getGroupesSemestreService = async (semestre,showToast = false) => {
  try {
    const response = await apiCall(
      api.get,
      [`/api/structure_groupes/semestre/${semestre}`],
      'Années universitaires récupérées avec succès',
      'Erreur lors de la récupération des années universitaires',
      showToast
    );
    return response;
  } catch (error) {
    console.error('Erreur dans getAllAnneesUniversitairesService:', error);
    throw error;
  }
}

export { getGroupesSemestreService };
