import apiCall from '@helpers/apiCall.js'
import api from '@helpers/axios.js'

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

const getGroupesSemestreService = async (semestre,showToast = false) => {
  try {
    return await apiCall(
        api.get,
        [`/api/structure_groupes?semestre=${semestre}`],
        'Années universitaires récupérées avec succès',
        'Erreur lors de la récupération des années universitaires',
        showToast
    );
  } catch (error) {
    console.error('Erreur dans getAllAnneesUniversitairesService:', error);
    throw error;
  }
}

const getGroupesService = async (params, scope = '', showToast = false) => {
  try {
    const response = await apiCall(
        api.get,
        [`/api${scope}/structure_groupes`, { params }],
        'Groupes récupérés avec succès',
        'Erreur lors de la récupération des groupes',
        showToast
    );
    return response.member;
  } catch (error) {
    console.error('Erreur dans getAllAnneesUniversitairesService:', error);
    throw error;
  }
}

// ----------------------------------------------
// ------------------- CREATE -------------------
// ----------------------------------------------

// ----------------------------------------------
// ------------------- UPDATE -------------------
// ----------------------------------------------

const updateGroupeService = async (id, data, scope = '', showToast = false) => {
  try {
    return await apiCall(
        api.patch,
        [`/api${scope}/structure_groupes/${id}`, data, { headers: { 'Content-Type': 'application/merge-patch+json' }}],
        'Groupe mis à jour avec succès',
        'Erreur lors de la mise à jour du groupe',
        showToast
    );
  } catch (error) {
    console.error('Erreur dans updateGroupeService:', error);
    throw error;
  }
}

// ----------------------------------------------
// ------------------- DELETE -------------------
// ----------------------------------------------


export { getGroupesSemestreService, getGroupesService, updateGroupeService };
