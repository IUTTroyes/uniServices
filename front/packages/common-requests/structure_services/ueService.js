import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

const getSemestreUesService = async (semestre, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/structure_ues?semestre=${semestre}`],
            'Ues du semestre récupérées avec succès',
            'Erreur lors de la récupération des ues du semestre',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getSemestreUesService:', error);
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

export { getSemestreUesService };
