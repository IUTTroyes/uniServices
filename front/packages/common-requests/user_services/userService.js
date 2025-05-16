import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

const getUserService = async (type, id, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/${type}/${id}`],
            'Utilisateur récupéré avec succès',
            'Erreur lors de la récupération de l\'utilisateur',
            showToast
        );
        return response;
    } catch (error) {
        console.error('Erreur dans getUserService:', error);
        throw error;
    }
}

const updateUserService = async (type, id, data, showToast = true) => {
    try {
        const response = await apiCall(
            api.patch,
            [`/api/${type}/${id}`, data, {
                headers: {
                    'Content-Type': 'application/merge-patch+json'
                }
            }],
            'Utilisateur mis à jour avec succès',
            'Erreur lors de la mise à jour de l\'utilisateur',
            showToast
        );
        return response;
    } catch (error) {
        console.error('Erreur dans updateUserService:', error);
        throw error;
    }
}

const getAllStatutsService = async (showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            ['/api/statuts'],
            'Statuts récupérés avec succès',
            'Erreur lors de la récupération des statuts',
            showToast
        );
        return response;
    } catch (error) {
        console.error('Erreur dans getAllStatutsService:', error);
        throw error;
    }
}

export { getUserService, updateUserService, getAllStatutsService };
