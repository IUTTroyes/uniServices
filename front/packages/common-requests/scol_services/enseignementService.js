import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

const getAllEnseignementService = async (showToast = false) => {
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
        console.error('Erreur dans getAllEnseignementService:', error);
        throw error;
    }
}

const getEnseignementSemestreService = async (semestreId, showToast = false) => {
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
        console.error('Erreur dans getEnseignementSemestreService:', error);
        throw error;
    }
}

const getEnseignementDepartementService = async (departementId, showToast = false) => {
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
        console.error('Erreur dans getEnseignementDepartementService:', error);
        throw error;
    }
}

const getEnseignementService = async (id, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/scol_enseignements/${id}`],
            'Enseignement récupéré avec succès',
            'Erreur lors de la récupération de l\'enseignement',
            showToast
        );
        return response;
    } catch (error) {
        console.error('Erreur dans getEnseignementService:', error);
        throw error;
    }
}

export { getAllEnseignementService, getEnseignementSemestreService, getEnseignementService, getEnseignementDepartementService };
