import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------
const getTicketsService = async (params = {}, endpoint = '/api/helpdesk_tickets') => {
    try {
        return await apiCall(
            api.get,
            [endpoint, { params }],
            null,
            'Erreur lors de la récupération des tickets',
            false
        );
    }
    catch (error) {
        console.error('Erreur dans getTicketsService', error);
        throw error;
    }
};

export { getTicketsService };

// ----------------------------------------------
// ------------------- CREATE -------------------
// ----------------------------------------------
const createTicketService =async (data, showToast=true)=>{
    try{
        return await apiCall(
            api.post,
            [`/api/helpdesk_tickets`, data],
            'Ticket créé avec succès !',
            'Erreur lors de la création de votre ticket',
            showToast
        )
    }
    catch (error){
        console.error('Erreur dans createTicketServices',error);
        throw error;
    }
}

// ----------------------------------------------
// ------------------- UPDATE -------------------
// ----------------------------------------------

// ----------------------------------------------
// ------------------- DELETE -------------------
// ----------------------------------------------

export {createTicketService}
