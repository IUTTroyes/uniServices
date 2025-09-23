import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

const getSemestresService = async (showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/structure_semestres`],
            'Semestres récupérés avec succès',
            'Erreur lors de la récupération des semestres',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getSemestresService:', error);
        throw error;
    }
}

const getSemestreService = async (semestreId, showToast = false) => {
    try {
        return await apiCall(
            api.get,
            [`/api/structure_semestres/${semestreId}`],
            'Semestre récupéré avec succès',
            'Erreur lors de la récupération du semestre',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getSemestreService:', error);
        throw error;
    }
}

const getDepartementSemestresService = async (departementId, onlyActif, scope, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api${scope}/structure_semestres?actif=${onlyActif}&departement=${departementId}`],
            'Semestres du département récupérés avec succès',
            'Erreur lors de la récupération des semestres du département',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getDepartementSemestresService:', error);
        throw error;
    }
}

const getDiplomeSemestresService = async (diplomeId, onlyActif, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/structure_semestres?diplome=${diplomeId}&actif=${onlyActif}`],
            'Semestres du diplôme récupérés avec succès',
            'Erreur lors de la récupération des semestres du diplôme',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getDiplomeSemestresService:', error);
        throw error;
    }
}

const getAnneeSemestresService = async (anneeId, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/structure_semestres?annee=${anneeId}`],
            'Semestres de l\'année récupérés avec succès',
            'Erreur lors de la récupération des semestres de l\'année universitaire',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getAnneeSemestresService:', error);
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

export { getSemestresService, getSemestreService, getDepartementSemestresService, getDiplomeSemestresService, getAnneeSemestresService };
