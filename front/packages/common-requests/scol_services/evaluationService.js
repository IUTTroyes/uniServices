import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

const getEvaluationsService = async (params, scope = '', showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api${scope}/scol_evaluations`, { params }],
            'EValuations récupérées avec succès',
            'Erreur lors de la récupération des évaluations',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getEvaluationsService:', error);
        throw error;
    }
}

// ----------------------------------------------
// ------------------- CREATE -------------------
// ----------------------------------------------

// ----------------------------------------------
// ------------------- UPDATE -------------------
// ----------------------------------------------

const updateEvaluationService = async (evaluationId, data, scope = '', showToast = false) => {
    try {
        return await apiCall(
            api.patch,
            [`/api${scope}/scol_evaluations/${evaluationId}`, data, { headers: { 'Content-Type': 'application/merge-patch+json' } }],
            'Évaluation mise à jour avec succès',
            'Erreur lors de la mise à jour de l\'évaluation',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans updateEvaluationService:', error);
        throw error;
    }
}

// ----------------------------------------------
// ------------------- DELETE -------------------
// ----------------------------------------------

export { getEvaluationsService, updateEvaluationService };
