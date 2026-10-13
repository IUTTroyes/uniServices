import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

export const getPlansCoursService = async (params = {}, scope = '', showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api${scope}/plan_cours`, { params }],
            'Données des plans de cours récupérées avec succès',
            'Erreur lors de la récupération des plans de cours',
            showToast
        );
        return response.member ?? response;
    } catch (error) {
        console.error('Erreur dans getPlansCoursService:', error);
        throw error;
    }
};

export const getPlanCoursService = async (id, scope = '', showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api${scope}/plan_cours/${id}`],
            'Plan de cours récupéré avec succès',
            'Erreur lors de la récupération du plan de cours',
            showToast
        );
        return response;
    } catch (error) {
        console.error('Erreur dans getPlanCoursService:', error);
        throw error;
    }
};

export const createPlanCoursService = async (data, scope = '', showToast = true) => {
    try {
        const response = await apiCall(
            api.post,
            [`/api/plan_cours${scope}`, data],
            'Plan de cours créé avec succès',
            'Erreur lors de la création du plan de cours',
            showToast
        );
        return response;
    } catch (error) {
        console.error('Erreur dans createPlanCoursService:', error);
        throw error;
    }
};

export const updatePlanCoursService = async (id, data, scope = '', showToast = true) => {
    try {
        const response = await apiCall(
            api.patch,
            [`/api${scope}/plan_cours/${id}`, data, {
                headers: {
                    'Content-Type': 'application/merge-patch+json'
                }
            }],
            'Plan de cours enregistré avec succès',
            'Erreur lors de la sauvegarde du plan de cours',
            showToast
        );
        return response;
    } catch (error) {
        console.error('Erreur dans updatePlanCoursService:', error);
        throw error;
    }
};

export const deletePlanCoursService = async (id, scope = '', showToast = true) => {
    try {
        const response = await apiCall(
            api.delete,
            [`/api${scope}/plan_cours/${id}`],
            'Plan de cours supprimé avec succès',
            'Erreur lors de la suppression du plan de cours',
            showToast
        );
        return response;
    } catch (error) {
        console.error('Erreur dans deletePlanCoursService:', error);
        throw error;
    }
};
