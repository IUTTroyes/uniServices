import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

const getCompetenceUeService = async (ue, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/apc_competences?ue=${ue}&groups=competence_ue:read`],
            'Compétence de l\'ue récupérées avec succès',
            'Erreur lors de la récupération de la compétence de l\'ue',
            showToast
        );
        return response.member[0];
    } catch (error) {
        console.error('Erreur dans getCompetenceUeService:', error);
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

export { getCompetenceUeService };
