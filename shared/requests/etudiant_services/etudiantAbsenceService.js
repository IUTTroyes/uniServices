import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

const getEtudiantAbsencesService = async (params = {}, scope = '', showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api${scope}/etudiant_absences`, { params }],
            'Absences récupérées avec succès',
            'Erreur lors de la récupération des absences',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getEtudiantAbsencesService:', error);
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

export { getEtudiantAbsencesService };
