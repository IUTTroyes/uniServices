import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

// ----------------------------------------------
// ------------------- CREATE -------------------
// ----------------------------------------------
const createMessageService = async (data, showToast = true) => {
    try {
        return await apiCall(
            api.post,
            [`/api/helpdesk_messages`, data, { headers: { 'Content-Type': 'application/ld+json' } }],
            'Message envoyé avec succès !',
            'Erreur lors de l\'envoi de votre message',
            showToast
        );
    }
    catch (error) {
        console.error('Erreur dans createMessageService', error);
        throw error;
    }
};

// ----------------------------------------------
// ------------------- UPDATE -------------------
// ----------------------------------------------

// ----------------------------------------------
// ------------------- DELETE -------------------
// ----------------------------------------------

const deleteMessageService = async (id, showToast = false) => {
    try {
        return await apiCall(
            api.delete,
            [`/api/helpdesk_messages/${id}`],
            'Message supprimées avec succès',
            'Erreur lors de la suppression du message',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans deleteMessageService:', error);
        throw error;
    }
}

export {createMessageService, deleteMessageService}