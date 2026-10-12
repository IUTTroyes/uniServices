import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

const getAllBacsService = async (showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            ['/api/scol_bacs'],
            'Bacs récupérés avec succès',
            'Erreur lors de la récupération des bacs',
            showToast
        );
        return response.member || response['hydra:member'] || response;
    } catch (error) {
        console.error('Erreur dans getAllBacsService:', error);
        throw error;
    }
};

const getBacService = async (id, showToast = false) => {
    try {
        return await apiCall(
            api.get,
            [`/api/scol_bacs/${id}`],
            'Bac récupéré avec succès',
            'Erreur lors de la récupération du bac',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getBacService:', error);
        throw error;
    }
};

// ----------------------------------------------
// ------------------- CREATE -------------------
// ----------------------------------------------

const createBacService = async (data, showToast = true) => {
    try {
        return await apiCall(
            api.post,
            ['/api/scol_bacs', data, {
                headers: {
                    'Content-Type': 'application/ld+json'
                }
            }],
            'Bac créé avec succès',
            'Erreur lors de la création du bac',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans createBacService:', error);
        throw error;
    }
};

// ----------------------------------------------
// ------------------- UPDATE -------------------
// ----------------------------------------------

const updateBacService = async (id, data, showToast = true) => {
    try {
        return await apiCall(
            api.patch,
            [`/api/scol_bacs/${id}`, data, {
                headers: {
                    'Content-Type': 'application/merge-patch+json'
                }
            }],
            'Bac mis à jour avec succès',
            'Erreur lors de la mise à jour du bac',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans updateBacService:', error);
        throw error;
    }
};

// ----------------------------------------------
// ------------------- DELETE -------------------
// ----------------------------------------------

const deleteBacService = async (id, showToast = true) => {
    try {
        return await apiCall(
            api.delete,
            [`/api/scol_bacs/${id}`],
            'Bac supprimé avec succès',
            'Erreur lors de la suppression du bac',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans deleteBacService:', error);
        throw error;
    }
};

export {
    getAllBacsService,
    getBacService,
    createBacService,
    updateBacService,
    deleteBacService
};
