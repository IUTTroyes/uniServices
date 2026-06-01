import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';
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

export {createMessageService}