import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

/**
 * Récupère les données d'administration des plans de cours pour un diplôme
 */
export const getPlansCoursAdministrationService = async (params, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            ['/api/plan_cours_administration', { params }],
            'Données des plans de cours récupérées avec succès',
            'Erreur lors de la récupération des plans de cours',
            showToast
        );
        return response;
    } catch (error) {
        console.error('Erreur dans getPlansCoursAdministrationService:', error);
        throw error;
    }
};

/**
 * Récupère les modules et plans de cours de l'enseignant (personnel) connecté
 */
export const getPlansCoursPersonnelService = async (params, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            ['/api/plan_cours_personnel', { params }],
            'Plans de cours du personnel récupérés avec succès',
            'Erreur lors de la récupération des plans de cours du personnel',
            showToast
        );
        return response;
    } catch (error) {
        console.error('Erreur dans getPlansCoursPersonnelService:', error);
        throw error;
    }
};

/**
 * Récupère un plan de cours par son ID
 */
export const getPlanCoursService = async (id, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/plan_cours/${id}`],
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

/**
 * Initialise un plan de cours pour un enseignement et une année universitaire s'il n'existe pas encore
 */
export const initPlanCoursModuleService = async (enseignementId, anneeUniversitaireId, showToast = true) => {
    try {
        const response = await apiCall(
            api.post,
            ['/api/plan_cours/init_module', { enseignementId, anneeUniversitaireId }],
            'Plan de cours initialisé avec succès',
            'Erreur lors de l\'initialisation du plan de cours',
            showToast
        );
        return response;
    } catch (error) {
        console.error('Erreur dans initPlanCoursModuleService:', error);
        throw error;
    }
};

/**
 * Met à jour un plan de cours
 */
export const updatePlanCoursService = async (id, data, showToast = true) => {
    try {
        const response = await apiCall(
            api.patch,
            [`/api/plan_cours/${id}`, data, {
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

/**
 * Recopie un plan de cours source vers un plan de cours cible
 */
export const recopiePlanCoursService = async (targetId, sourcePlanCoursId, showToast = true) => {
    try {
        const response = await apiCall(
            api.post,
            [`/api/plan_cours/${targetId}/recopie`, { sourcePlanCoursId }],
            'Plan de cours recopié avec succès',
            'Erreur lors de la recopie du plan de cours',
            showToast
        );
        return response;
    } catch (error) {
        console.error('Erreur dans recopiePlanCoursService:', error);
        throw error;
    }
};

/**
 * Supprime un plan de cours
 */
export const deletePlanCoursService = async (id, showToast = true) => {
    try {
        const response = await apiCall(
            api.delete,
            [`/api/plan_cours/${id}`],
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
