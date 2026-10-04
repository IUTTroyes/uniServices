import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

const getAllTypeHrsService = async (showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            ['/api/personnel_enseignant_type_hrs'],
            'Types d\'heures récupérés avec succès',
            'Erreur lors de la récupération des types d\'heures',
            showToast
        );
        return response.member || response['hydra:member'] || response;
    } catch (error) {
        console.error('Erreur dans getAllTypeHrsService:', error);
        throw error;
    }
};

const getTypeHrsService = async (id, showToast = false) => {
    try {
        return await apiCall(
            api.get,
            [`/api/personnel_enseignant_type_hrs/${id}`],
            'Type d\'heures récupéré avec succès',
            'Erreur lors de la récupération du type d\'heures',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getTypeHrsService:', error);
        throw error;
    }
};

// ----------------------------------------------
// ------------------- CREATE -------------------
// ----------------------------------------------

const createTypeHrsService = async (data, showToast = true) => {
    try {
        return await apiCall(
            api.post,
            ['/api/personnel_enseignant_type_hrs', data, {
                headers: {
                    'Content-Type': 'application/ld+json'
                }
            }],
            'Type d\'heures créé avec succès',
            'Erreur lors de la création du type d\'heures',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans createTypeHrsService:', error);
        throw error;
    }
};

// ----------------------------------------------
// ------------------- UPDATE -------------------
// ----------------------------------------------

const updateTypeHrsService = async (id, data, showToast = true) => {
    try {
        return await apiCall(
            api.patch,
            [`/api/personnel_enseignant_type_hrs/${id}`, data, {
                headers: {
                    'Content-Type': 'application/merge-patch+json'
                }
            }],
            'Type d\'heures mis à jour avec succès',
            'Erreur lors de la mise à jour du type d\'heures',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans updateTypeHrsService:', error);
        throw error;
    }
};

// ----------------------------------------------
// ------------------- DELETE -------------------
// ----------------------------------------------

const deleteTypeHrsService = async (id, showToast = true) => {
    try {
        return await apiCall(
            api.delete,
            [`/api/personnel_enseignant_type_hrs/${id}`],
            'Type d\'heures supprimé avec succès',
            'Erreur lors de la suppression du type d\'heures',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans deleteTypeHrsService:', error);
        throw error;
    }
};

export {
    getAllTypeHrsService,
    getTypeHrsService,
    createTypeHrsService,
    updateTypeHrsService,
    deleteTypeHrsService
};
