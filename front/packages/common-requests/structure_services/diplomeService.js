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
        // Trie les diplômes pour mettre les actifs en premier
        response.member.sort((a, b) => (b.actif === true) - (a.actif === true));
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
        // Trie les diplômes pour mettre les actifs en premier
        response.member.sort((a, b) => (b.actif === true) - (a.actif === true));
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

// ----------------------------------------------
// ------------------- DELETE -------------------
// ----------------------------------------------

export { getAllDiplomesService, getDiplomesService };
