import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

const getDiplomeAnneesService = async (diplomeId, actif, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/structure_annees?departement=${diplomeId}&actif=${actif}`],
            'Années du diplome récupérées avec succès',
            'Erreur lors de la récupération des années du diplome',
            showToast
        );
        return response['member'];
    } catch (error) {
        console.error('Erreur dans getDiplomeAnneesService:', error);
        throw error;
    }
}

const getDepartementAnneesService = async (departementId, actif, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/structure_annees?departement=${departementId}&actif=${actif}`],
            'Années du département récupérées avec succès',
            'Erreur lors de la récupération des années du département',
            showToast
        );
        return response['member'];
    } catch (error) {
        console.error('Erreur dans getDepartementAnneesService:', error);
        throw error;
    }
}

const getPnAnneesService = async (pnId, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/structure_annees?pn=${pnId}`],
            'Années du PN récupérées avec succès',
            'Erreur lors de la récupération des années du PN',
            showToast
        );
        return response['member'];
    } catch (error) {
        console.error('Erreur dans getPnAnneesService:', error);
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

export { getDiplomeAnneesService, getDepartementAnneesService, getPnAnneesService };
