import api from '@helpers/axios'
import apiCall from '@helpers/apiCall'

const getStagePeriodesService = async (params = {}, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            ['/api/stage_periodes', { params }],
            'Périodes de stage récupérées avec succès',
            'Erreur lors de la récupération des périodes de stage',
            showToast
        );
        return response['member'] || response;
    } catch (error) {
        console.error('Erreur dans getStagePeriodesService:', error);
        throw error;
    }
};

const getStagePeriodeService = async (id, showToast = false) => {
    try {
        return await apiCall(
            api.get,
            [`/api/stage_periodes/${id}`],
            'Période de stage récupérée avec succès',
            'Erreur lors de la récupération de la période de stage',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getStagePeriodeService:', error);
        throw error;
    }
};

const createStagePeriodeService = async (data, showToast = false) => {
    try {
        return await apiCall(
            api.post,
            ['/api/stage_periodes', data, { headers: { 'Content-Type': 'application/ld+json' } }],
            'Période de stage créée avec succès',
            'Erreur lors de la création de la période de stage',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans createStagePeriodeService:', error);
        throw error;
    }
};

const updateStagePeriodeService = async (id, data, showToast = false) => {
    try {
        return await apiCall(
            api.patch,
            [`/api/stage_periodes/${id}`, data, { headers: { 'Content-Type': 'application/merge-patch+json' } }],
            'Période de stage mise à jour avec succès',
            'Erreur lors de la mise à jour de la période de stage',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans updateStagePeriodeService:', error);
        throw error;
    }
};

const deleteStagePeriodeService = async (id, showToast = false) => {
    try {
        return await apiCall(
            api.delete,
            [`/api/stage_periodes/${id}`],
            'Période de stage supprimée avec succès',
            'Erreur lors de la suppression de la période de stage',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans deleteStagePeriodeService:', error);
        throw error;
    }
};

export {
    getStagePeriodesService,
    getStagePeriodeService,
    createStagePeriodeService,
    updateStagePeriodeService,
    deleteStagePeriodeService
};
