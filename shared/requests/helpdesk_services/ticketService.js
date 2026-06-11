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

export {createTicketService}

// ----------------------------------------------
// ------------------- UPDATE -------------------
// ----------------------------------------------

const updateTicketStatutService = async (id,data, showToast = false) => {
    try {
        return await apiCall(
            api.patch,
            [`/api/helpdesk_tickets/${id}`, data, { headers: { 'Content-Type': 'application/merge-patch+json' }}],
            'Statut mise à jour avec succès',
            'Erreur lors de la mise à jour du statut',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans updateTicketStatutService:', error);
        throw error;
    }
}



export {updateTicketStatutService}

// ----------------------------------------------
// ------------------- DELETE -------------------
// ----------------------------------------------

const deleteTicketService = async (id, showToast = false) => {
    try {
        return await apiCall(
            api.delete,
            [`/api/helpdesk_tickets/${id}`],
            'Ticket supprimées avec succès',
            'Erreur lors de la suppression du ticket',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans deleteTicketService:', error);
        throw error;
    }
}

export {deleteTicketService}
