import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------
const getTicketsService = async (params = {},scope='',showToast=false) => {
    try{
        const response= await apiCall(
            api.get,
            [`/api${scope}/helpdesk_tickets`,{params}],
            'Tickets récupérés avec succès',
            'Erreur lors de la récupération des tickets',
            showToast
        )
        return response.member;
    }
    catch (error) {
        console.error('Erreur dans getTicketsService', error);
        throw error;
    }
};

const getTicketService = async (id,params, scope='',showToast=false) => {
    try{
        return await apiCall(
            api.get,
            [`/api/helpdesk_tickets/${id}`,{params}],
            'Ticket récupéré avec succès',
            'Erreur lors de la récupération du ticket',
            showToast
        )
    }
    catch (error){
        console.error('Erreur dans getTicketService',error)
        throw error;
    }
}

export { getTicketsService,getTicketService };



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
