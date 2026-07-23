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

const createEtudiantAbsenceService = async (data, scope = '', showToast = false) => {
    try {
        const response = await apiCall(
            api.post,
            [`/api${scope}/etudiant_absences`, data, { headers: {'Content-Type': 'application/ld+json'}}],
            'Absence créée avec succès',
            'Erreur lors de la création de l\'absence',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans createEtudiantAbsenceService:', error);
        throw error;
    }
}

// ----------------------------------------------
// ------------------- UPDATE -------------------
// ----------------------------------------------

// ----------------------------------------------
// ------------------- DELETE -------------------
// ----------------------------------------------

const deleteEtudiantAbsenceService = async (id, scope = '', showToast = false) => {
    try {
        return await apiCall(
            api.delete,
            [`/api${scope}/etudiant_absences/${id}`],
            'Absence supprimée avec succès',
            'Erreur lors de la suppression de l\'absence',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans deleteEtudiantAbsenceService:', error);
        throw error;
    }
}

export { getEtudiantAbsencesService, createEtudiantAbsenceService, deleteEtudiantAbsenceService };
