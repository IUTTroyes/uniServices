import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

const getDiplomeAnneesService = async (diplomeId, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/annees-par-diplome/${diplomeId}`],
            'Années du diplôme récupérées avec succès',
            'Erreur lors de la récupération des années du diplôme',
            showToast
        );
        return response['member'];
    } catch (error) {
        console.error('Erreur dans getDiplomeAnneesService:', error);
        throw error;
    }
}

const getDiplomeAnneesActifsService = async (diplomeId, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/annees-par-diplome/${diplomeId}?actif=true`],
            'Années actives du diplôme récupérées avec succès',
            'Erreur lors de la récupération des années actives du diplôme',
            showToast
        );
        return response['member'];
    } catch (error) {
        console.error('Erreur dans getDiplomeAnneesActifsService:', error);
        throw error;
    }
}

const getDepartementAnneesService = async (departementId, onlyActif, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/structure_annees?departement=${departementId}&actif=${onlyActif}`],
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

export { getDiplomeAnneesService, getDiplomeAnneesActifsService, getDepartementAnneesService, getPnAnneesService };
