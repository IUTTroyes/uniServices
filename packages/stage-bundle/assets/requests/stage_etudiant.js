import api from '@helpers/axios'
import apiCall from '@helpers/apiCall'

const getStageEtudiantsService = async (params = {}, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            ['/api/stage_etudiants', { params }],
            'Étudiants de stage récupérés avec succès',
            'Erreur lors de la récupération des étudiants de stage',
            showToast
        );
        return response['member'] || response;
    } catch (error) {
        console.error('Erreur dans getStageEtudiantsService:', error);
        throw error;
    }
};

const updateStageEtudiantService = async (id, data, showToast = false) => {
    try {
        return await apiCall(
            api.patch,
            [`/api/stage_etudiants/${id}`, data, { headers: { 'Content-Type': 'application/merge-patch+json' } }],
            'Convention mise à jour avec succès',
            'Erreur lors de la mise à jour de la convention',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans updateStageEtudiantService:', error);
        throw error;
    }
};

const deleteStageEtudiantService = async (id, showToast = false) => {
    try {
        return await apiCall(
            api.delete,
            [`/api/stage_etudiants/${id}`],
            'Demande de convention supprimée',
            'Erreur lors de la suppression de la demande',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans deleteStageEtudiantService:', error);
        throw error;
    }
};

export {
    getStageEtudiantsService,
    updateStageEtudiantService,
    deleteStageEtudiantService
};
