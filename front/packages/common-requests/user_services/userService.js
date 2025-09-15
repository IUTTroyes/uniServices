import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

const getUserService = async (type, id, showToast = false) => {
    try {
        return await apiCall(
            api.get,
            [`/api/${type}/${id}`],
            'Utilisateur récupéré avec succès',
            'Erreur lors de la récupération de l\'utilisateur',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getUserService:', error);
        throw error;
    }
}

const getAllStatutsService = async (showToast = false) => {
    try {
        return await apiCall(
            api.get,
            ['/api/statuts'],
            'Statuts récupérés avec succès',
            'Erreur lors de la récupération des statuts',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getAllStatutsService:', error);
        throw error;
    }
}

// ----------------------------------------------
// ------------------- CREATE -------------------
// ----------------------------------------------


// ----------------------------------------------
// ------------------- UPDATE -------------------
// ----------------------------------------------

const updateUserService = async (type, id, data, showToast = true) => {
    try {
        return await apiCall(
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
    } catch (error) {
        console.error('Erreur dans updateUserService:', error);
        throw error;
    }
}

const updateUserPasswordService = async (email, showToast = true) => {
    try {
        return await apiCall(
            api.post,
            [`/api/change_password`, email],
            'Un email de réinitialisation a été envoyé',
            'Erreur lors de la mise à jour du mot de passe',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans updateUserPasswordService:', error);
        throw error;
    }
}

const resetPasswordService = async (token, password, showToast = true) => {
    try {
        return await apiCall(
            api.post,
            [`/api/reset_password`, { token, password }],
            'Mot de passe réinitialisé avec succès',
            'Erreur lors de la réinitialisation du mot de passe',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans resetPasswordService:', error);
        throw error;
    }
}

// ----------------------------------------------
// ------------------- DELETE -------------------
// ----------------------------------------------

export { getUserService, updateUserService, getAllStatutsService, updateUserPasswordService, resetPasswordService };
