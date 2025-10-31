import api from '@helpers/axios';
import createApiService from '@requests/apiService';
import apiCall from '@helpers/apiCall';

// Create API service for etudiant_scolarite_semestres endpoint
const etudiantScolariteSemestresService = createApiService('/api/etudiant_scolarite_semestres');

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

const getEtudiantScolariteSemestresService = async (params, scope = '', showToast = false) => {
    try {

        const reponse = await apiCall(
            api.get,
            [`/api${scope}/etudiant_scolarite_semestres`, {params}],
            'Semestre de la Scolarité de l\'étudiant récupérées avec succès',
            'Erreur lors de la récupération du Semestre de la Scolarité de l\'étudiant',
            showToast
        );
        return reponse.member;
    } catch (error) {
        console.error('Erreur dans getEtudiantScolariteSemestresService:', error);
        throw error;
    }
}

// ----------------------------------------------
// ------------------- CREATE -------------------
// ----------------------------------------------


// ----------------------------------------------
// ------------------- UPDATE -------------------
// ----------------------------------------------

const updateEtudiantScolariteSemestreService = async (id, data, showToast = false) => {
    try {
        const response = await apiCall(
            etudiantScolariteSemestresService.update,
            [id, data],
            'Semestre de la Scolarité de l\'étudiant mis à jour avec succès',
            'Erreur lors de la mise à jour du Semestre de la Scolarité de l\'étudiant',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans updateEtudiantScolariteSemestreService:', error);
        throw error;
    }
}


// ----------------------------------------------
// ------------------- DELETE -------------------
// ----------------------------------------------

export { getEtudiantScolariteSemestresService, updateEtudiantScolariteSemestreService };
