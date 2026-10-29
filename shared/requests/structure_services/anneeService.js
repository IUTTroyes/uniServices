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

const getAnneeService = async (anneeId, showToast = false) => {
    try {
        return await apiCall(
            api.get,
            [`/api/structure_annees/${anneeId}`],
            'Année récupérée avec succès',
            'Erreur lors de la récupération de l\'année',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getAnneeService:', error);
        throw error;
    }
}

// ----------------------------------------------
// ------------------- CREATE -------------------
// ----------------------------------------------

// ----------------------------------------------
// ------------------- UPDATE -------------------
// ----------------------------------------------

const updateAnneeService = async (anneeId, data, showToast = true) => {
    try {
        return await apiCall(
            api.patch,
            [`/api/structure_annees/${anneeId}`, data, { headers: { 'Content-Type': 'application/merge-patch+json' }}],
            'Année mise à jour avec succès',
            'Erreur lors de la mise à jour de l\'année',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans updateAnneeService:', error);
        throw error;
    }
}

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

export { getAnneesService, getAnneeService, updateAnneeService, deleteAnneeService };
