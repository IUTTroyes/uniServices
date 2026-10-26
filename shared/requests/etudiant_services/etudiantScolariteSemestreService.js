import api from '@helpers/axios';
import createApiService from '@requests/apiService';
import apiCall from '@helpers/apiCall';

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

const getEtudiantScolariteSemestresService = async (params, scope = '', showToast = false) => {
    try {
        if (params.filters) {
            if (params.filters.nom) {
                params['nom'] = params.filters.nom.value;
            }
            if (params.filters.prenom) {
                params['prenom'] = params.filters.prenom.value;
            }
            if (params.filters.numEtudiant) {
                params['numEtudiant'] = params.filters.numEtudiant.value;
            }
        }
        const response =  await apiCall(
            api.get,
            [`/api${scope}/etudiant_scolarite_semestres`, {params}],
            'Semestre de la Scolarité de l\'étudiant récupérées avec succès',
            'Erreur lors de la récupération du Semestre de la Scolarité de l\'étudiant',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getEtudiantScolariteSemestresService:', error);
        throw error;
    }
}

const getEtudiantScolariteSemestreService = async (id, scope = '', showToast = false) => {
    try {
        return await apiCall(
            api.get,
            [`/api${scope}/etudiant_scolarite_semestres/${id}`],
            'Semestre de la Scolarité de l\'étudiant récupéré avec succès',
            'Erreur lors de la récupération du Semestre de la Scolarité de l\'étudiant',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getEtudiantScolariteSemestreService:', error);
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
            api.put,
            [`/api/etudiant_scolarite_semestres/${id}`, data],
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

const synchroSemestrePreviousService = async (semestreId, anneeUniversitaireId, showToast = false) => {
    try {
        const response = await apiCall(
            api.post,
            ['/api/manage-groupes/synchro-semestre-previous/etudiant_scolarite_semestres', { semestreId, anneeUniversitaireId }],
            'Synchronisation depuis le semestre précédent effectuée',
            'Erreur lors de la synchronisation depuis le semestre précédent',
            showToast
        );

        return response.member ?? response;
    } catch (error) {
        console.error('Erreur dans synchroSemestrePreviousService:', error);
        throw error;
    }
}


// ----------------------------------------------
// ------------------- DELETE -------------------
// ----------------------------------------------

export { getEtudiantScolariteSemestresService, updateEtudiantScolariteSemestreService, synchroSemestrePreviousService };
