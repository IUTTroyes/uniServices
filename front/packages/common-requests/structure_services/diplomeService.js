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

const getAllDiplomesActifsService = async (showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            ['/api/diplomes?actif=true'],
            'Diplômes actifs récupérés avec succès',
            'Erreur lors de la récupération des diplômes actifs',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getAllDiplomesActifsService:', error);
        throw error;
    }
}

const getDepartementDiplomesService = async (departementId, actif, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/structure_diplomes?departement=${departementId}?actif=${actif}`],
            'Diplômes du département récupérés avec succès',
            'Erreur lors de la récupération des diplômes du département',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getDepartementDiplomesService:', error);
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

export { getAllDiplomesService, getAllDiplomesActifsService, getDepartementDiplomesService };
