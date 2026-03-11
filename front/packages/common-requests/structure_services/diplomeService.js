import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

const getAllDiplomesService = async (showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            ['/api/diplomes'],
            'Diplômes récupérés avec succès',
            'Erreur lors de la récupération des diplômes',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getAllDiplomesService:', error);
        throw error;
    }
}

const getDiplomesService = async (params, scope = '', showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api${scope}/structure_diplomes`, { params }],
            'Diplômes récupérés avec succès',
            'Erreur lors de la récupération des diplômes',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getDiplomesService:', error);
        throw error;
    }
}

// ----------------------------------------------
// ------------------- CREATE -------------------
// ----------------------------------------------


// ----------------------------------------------
// ------------------- UPDATE -------------------
// ----------------------------------------------
const updateDiplomeService = async (diplomeId, data, showToast = false) => {
    try {
        return await apiCall(
            api.patch,
            [`/api/structure_diplomes/${diplomeId}`, data, { headers: { 'Content-Type': 'application/merge-patch+json' }}],
            'Diplôme mis à jour avec succès',
            'Erreur lors de la mise à jour du diplôme',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans updateDiplomeService:', error);
        throw error;
    }
}

const deleteDiplomeFromAnneeUnivService = async (anneeUnivId, diplomeId, showToast = false) => {
    try {
        return await apiCall(
            api.delete,
            [`/api/structure_annee_universitaire/${anneeUnivId}/diplomes/${diplomeId}`],
            'Diplôme supprimé de l\'année universitaire avec succès',
            'Erreur lors de la suppression du diplôme de l\'année universitaire',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans deleteDiplomeFromAnneeUnivService:', error);
        throw error;
    }
}

// ----------------------------------------------
// ------------------- DELETE -------------------
// ----------------------------------------------
const deleteDiplomeService = async (diplomeId, showToast = false) => {
    try {
        return await apiCall(
            api.delete,
            [`/api/structure_diplomes/${diplomeId}`],
            'Diplôme supprimé avec succès',
            'Erreur lors de la suppression du diplôme',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans deleteDiplomeService:', error);
        throw error;
    }
}

export { getAllDiplomesService, getDiplomesService, updateDiplomeService, deleteDiplomeService, deleteDiplomeFromAnneeUnivService };
