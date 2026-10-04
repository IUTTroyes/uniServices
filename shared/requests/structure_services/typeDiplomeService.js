import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

const getAllTypeDiplomesService = async (showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            ['/api/structure_type_diplomes'],
            'Types de diplômes récupérés avec succès',
            'Erreur lors de la récupération des types de diplômes',
            showToast
        );
        return response.member || response['hydra:member'] || response;
    } catch (error) {
        console.error('Erreur dans getAllTypeDiplomesService:', error);
        throw error;
    }
};

const getTypeDiplomeService = async (id, showToast = false) => {
    try {
        return await apiCall(
            api.get,
            [`/api/structure_type_diplomes/${id}`],
            'Type de diplôme récupéré avec succès',
            'Erreur lors de la récupération du type de diplôme',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getTypeDiplomeService:', error);
        throw error;
    }
};

// ----------------------------------------------
// ------------------- CREATE -------------------
// ----------------------------------------------

const createTypeDiplomeService = async (data, showToast = true) => {
    try {
        return await apiCall(
            api.post,
            ['/api/structure_type_diplomes', data, {
                headers: {
                    'Content-Type': 'application/ld+json'
                }
            }],
            'Type de diplôme créé avec succès',
            'Erreur lors de la création du type de diplôme',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans createTypeDiplomeService:', error);
        throw error;
    }
};

// ----------------------------------------------
// ------------------- UPDATE -------------------
// ----------------------------------------------

const updateTypeDiplomeService = async (id, data, showToast = true) => {
    try {
        return await apiCall(
            api.patch,
            [`/api/structure_type_diplomes/${id}`, data, {
                headers: {
                    'Content-Type': 'application/merge-patch+json'
                }
            }],
            'Type de diplôme mis à jour avec succès',
            'Erreur lors de la mise à jour du type de diplôme',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans updateTypeDiplomeService:', error);
        throw error;
    }
};

// ----------------------------------------------
// ------------------- DELETE -------------------
// ----------------------------------------------

const deleteTypeDiplomeService = async (id, showToast = true) => {
    try {
        return await apiCall(
            api.delete,
            [`/api/structure_type_diplomes/${id}`],
            'Type de diplôme supprimé avec succès',
            'Erreur lors de la suppression du type de diplôme',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans deleteTypeDiplomeService:', error);
        throw error;
    }
};

export {
    getAllTypeDiplomesService,
    getTypeDiplomeService,
    createTypeDiplomeService,
    updateTypeDiplomeService,
    deleteTypeDiplomeService
};
