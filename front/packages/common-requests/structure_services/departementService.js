import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

const getAllDepartementsService = async (showToast = false) => {
    try {
        return await apiCall(
            api.get,
            ['/api/structure_departements'],
            'Départements récupérés avec succès',
            'Erreur lors de la récupération des départements',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getAllDepartementsService:', error);
        throw error;
    }
}

const getDepartementService = async (departementId, showToast = false) => {
    try {
        return await apiCall(
            api.get,
            [`/api/structure_departements/${departementId}`],
            'Département récupéré avec succès',
            'Erreur lors de la récupération du département',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getDepartementService:', error);
        throw error;
    }
}

const getDepartementsPersonnelService = async (personnelId, actif, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/structure_departements?personnel=${personnelId}?actif=${actif}`],
            'Départements du personnel récupérés avec succès',
            'Erreur lors de la récupération des départements du personnel',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getDepartementsPersonnelService:', error);
        throw error;
    }
}

// ----------------------------------------------
// ------------------- CREATE -------------------
// ----------------------------------------------

// ----------------------------------------------
// ------------------- UPDATE -------------------
// ----------------------------------------------

const changeDepartementActifService = async (departementId, showToast = true) => {
    try {
        console.log('changeDepartementActifService', departementId);
        return await apiCall(
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
    } catch (error) {
        console.error('Erreur dans changeDepartementActifService:', error);
        throw error;
    }
}

// ----------------------------------------------
// ------------------- DELETE -------------------
// ----------------------------------------------

export { getAllDepartementsService, getDepartementService, getDepartementsPersonnelService, changeDepartementActifService };
