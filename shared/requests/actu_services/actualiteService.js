import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

const getActusService = async (params = {}, scope = '', showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api${scope}/departement_actualites`, { params }],
            'Actualités récupérées avec succès',
            'Erreur lors de la récupération des actualités',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getActusService:', error);
        throw error;
    }
}

// ----------------------------------------------
// ------------------- CREATE -------------------
// ----------------------------------------------

const createActuService = async (data, scope = '', showToast = false) => {
    try {
        const response = await apiCall(
            api.post,
            [`/api${scope}/departement_actualites`, data, { headers: {'Content-Type': 'application/ld+json'}}],
            'Actualité créée avec succès',
            'Erreur lors de la création de l\'actualité',
            showToast
        );
        // return member when normalized collection, otherwise return raw response (created entity)
        return response?.member ?? response;
    } catch (error) {
        console.error('Erreur dans createActuService:', error);
        throw error;
    }
}

// ----------------------------------------------
// ------------------- UPDATE -------------------
// ----------------------------------------------

// ----------------------------------------------
// ------------------- DELETE -------------------
// ----------------------------------------------

const deleteActuService = async (id, scope = '', showToast = false) => {
    try {
        return await apiCall(
            api.delete,
            [`/api${scope}/departement_actualites/${id}`],
            'Actualité supprimée avec succès',
            'Erreur lors de la suppression de l\'actualité',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans deleteActuService:', error);
        throw error;
    }
}

export { getActusService, createActuService, deleteActuService };
