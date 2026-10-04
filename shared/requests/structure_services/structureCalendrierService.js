import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

const getAllCalendriersService = async (params = {}, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            ['/api/structure_calendriers', { params }],
            'Calendriers récupérés avec succès',
            'Erreur lors de la récupération des calendriers',
            showToast
        );
        return response.member || response['hydra:member'] || response;
    } catch (error) {
        console.error('Erreur dans getAllCalendriersService:', error);
        throw error;
    }
};

const getCalendrierService = async (id, showToast = false) => {
    try {
        return await apiCall(
            api.get,
            [`/api/structure_calendriers/${id}`],
            'Semaine de calendrier récupérée avec succès',
            'Erreur lors de la récupération de la semaine de calendrier',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getCalendrierService:', error);
        throw error;
    }
};

const getSemaineUniversitaireService = async (weekNumber, anneeUniversitaire, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/structure_calendriers?semaineReelle=${weekNumber}&anneeUniversitaire=${anneeUniversitaire}`],
            'Semaine universitaire récupérée avec succès',
            'Erreur lors de la récupération de la semaine universitaire',
            showToast
        );
        return response.member || response['hydra:member'] || response;
    } catch (error) {
        console.error('Erreur dans getSemaineUniversitaireService:', error);
        throw error;
    }
};

// ----------------------------------------------
// ------------------- CREATE -------------------
// ----------------------------------------------

const createCalendrierService = async (data, showToast = true) => {
    try {
        return await apiCall(
            api.post,
            ['/api/structure_calendriers', data, {
                headers: {
                    'Content-Type': 'application/ld+json'
                }
            }],
            'Semaine ajoutée au calendrier avec succès',
            'Erreur lors de l\'ajout au calendrier',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans createCalendrierService:', error);
        throw error;
    }
};

// ----------------------------------------------
// ------------------- UPDATE -------------------
// ----------------------------------------------

const updateCalendrierService = async (id, data, showToast = true) => {
    try {
        return await apiCall(
            api.patch,
            [`/api/structure_calendriers/${id}`, data, {
                headers: {
                    'Content-Type': 'application/merge-patch+json'
                }
            }],
            'Semaine du calendrier mise à jour avec succès',
            'Erreur lors de la mise à jour du calendrier',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans updateCalendrierService:', error);
        throw error;
    }
};

// ----------------------------------------------
// ------------------- DELETE -------------------
// ----------------------------------------------

const deleteCalendrierService = async (id, showToast = true) => {
    try {
        return await apiCall(
            api.delete,
            [`/api/structure_calendriers/${id}`],
            'Semaine supprimée du calendrier avec succès',
            'Erreur lors de la suppression du calendrier',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans deleteCalendrierService:', error);
        throw error;
    }
};

export {
    getAllCalendriersService,
    getCalendrierService,
    getSemaineUniversitaireService,
    createCalendrierService,
    updateCalendrierService,
    deleteCalendrierService
};
