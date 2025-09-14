import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

const getAllEnseignementsService = async (showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            ['/api/scol_enseignements'],
            'Enseignements récupérés avec succès',
            'Erreur lors de la récupération des enseignements',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getAllEnseignementsService:', error);
        throw error;
    }
}

const getEnseignementsSemestreService = async (semestreId, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/scol_enseignements?semestre=${semestreId}`],
            'Enseignements du semestre récupérés avec succès',
            'Erreur lors de la récupération des enseignements du semestre',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getEnseignementsSemestreService:', error);
        throw error;
    }
}

const getEnseignementsDepartementService = async (departementId, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/scol_enseignements?departement=${departementId}`],
            'Enseignements du département récupérés avec succès',
            'Erreur lors de la récupération des enseignements du département',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getEnseignementsDepartementService:', error);
        throw error;
    }
}

const getEnseignementsUeService = async (ueId, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/scol_enseignements?ue=${ueId}&groups=enseignement_ue:read`],
            'Enseignements de l\'ue récupérés avec succès',
            'Erreur lors de la récupération des enseignements de l\'ue',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getEnseignementsUeService:', error);
        throw error;
    }
}

const getEnseignementService = async (id, showToast = false) => {
    try {
        return await apiCall(
            api.get,
            [`/api/scol_enseignements/${id}`],
            'Enseignement récupéré avec succès',
            'Erreur lors de la récupération de l\'enseignement',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getEnseignementService:', error);
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

export { getAllEnseignementsService, getEnseignementsSemestreService, getEnseignementService, getEnseignementsDepartementService, getEnseignementsUeService };
