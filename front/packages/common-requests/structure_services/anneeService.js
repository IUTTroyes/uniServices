import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';
// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

const getAnneesService = async (params, scope = '', showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api${scope}/structure_annees`, { params }],
            'Années récupérés avec succès',
            'Erreur lors de la récupération des années',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getAnnéesService:', error);
        throw error;
    }
}

// ----------------------------------------------
// ------------------- CREATE -------------------
// ----------------------------------------------

// ----------------------------------------------
// ------------------- UPDATE -------------------
// ----------------------------------------------

// ----------------------------------------------
// ------------------- DELETE -------------------
// ----------------------------------------------

const deleteAnneeService = async (anneeId, showToast = false) => {
    try {
        return await apiCall(
            api.delete,
            [`/api/structure_annees/${anneeId}`],
            'Année supprimée avec succès',
            'Erreur lors de la suppression de l\'année',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans deleteAnneeService:', error);
        throw error;
    }
}

export { getAnneesService, deleteAnneeService };
