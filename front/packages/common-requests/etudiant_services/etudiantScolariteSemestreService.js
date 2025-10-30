import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

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


// ----------------------------------------------
// ------------------- DELETE -------------------
// ----------------------------------------------

export { getEtudiantScolariteSemestresService };
