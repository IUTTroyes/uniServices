import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

const getMessagesService = async (params = {},scope='',showToast=false) => {
    try{
        const response= await apiCall(
            api.get,
            [`/api${scope}/helpdesk_messages`,{params}],
            'Messages récupérés avec succès',
            'Erreur lors de la récupération des messages',
            showToast
        )
        return response.member;
    }
    catch (error) {
        console.error('Erreur dans getMessagesService', error);
        throw error;
    }
};

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

export {getMessagesService,createMessageService, deleteMessageService}