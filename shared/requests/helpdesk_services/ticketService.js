import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

// ----------------------------------------------
// ------------------- CREATE -------------------
// ----------------------------------------------
const createTicketService =async (data, showToast=true)=>{
    try{
        return await apiCall(
            api.post,
            [`/api/tickets`, data,{ headers:{'Content-Type': 'application/ld+json' }}],
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