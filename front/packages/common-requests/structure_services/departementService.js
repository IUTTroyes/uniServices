import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

const getAllDepartementsService = async (showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            ['/api/structure_departements'],
            'Départements récupérés avec succès',
            'Erreur lors de la récupération des départements',
            showToast
        );
        return response;
    } catch (error) {
        console.error('Erreur dans getAllDepartementsService:', error);
        throw error;
    }
}

const getDepartementService = async (departementId, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/structure_departements/${departementId}`],
            'Département récupéré avec succès',
            'Erreur lors de la récupération du département',
            showToast
        );
        return response;
    } catch (error) {
        console.error('Erreur dans getDepartementService:', error);
        throw error;
    }
}

const changeDepartementActifService = async (departementId, showToast = true) => {
    try {
        const response = await apiCall(
            api.post,
            [`/api/structure_departement_personnels/${departementId}/change_departement`, {departementId}, {
                headers: {
                    'Content-Type': 'application/ld+json'
                }
            }],
            'Département actif changé avec succès',
            'Erreur lors du changement de département actif',
            showToast
        );
        return response;
    } catch (error) {
        console.error('Erreur dans changeDepartementActifService:', error);
        throw error;
    }
}

export { getAllDepartementsService, getDepartementService, changeDepartementActifService };
