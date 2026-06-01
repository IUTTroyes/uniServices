import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------
const getServicesService =async (params,scope='',showToast=false)=>{
    try{
        const response =await apiCall(
            api.get,
            [`/api${scope}/structure_services`,{ params }],
            'Services récupérés avec succès !',
            'Erreur lors de la récupération des services',
            showToast
        );
        return response.member;
    }
    catch (error){
        console.error('Erreur dans getServicesService',error);
        throw error;
    }
}

// ----------------------------------------------
// ------------------- CREATE -------------------
// ----------------------------------------------

// ----------------------------------------------
// ------------------- UPDATE -------------------
// ----------------------------------------------

// ----------------------------------------------
// ------------------- DELETE -------------------
// ----------------------------------------------

export {getServicesService}