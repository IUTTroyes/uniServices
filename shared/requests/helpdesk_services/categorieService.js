import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------
const getCategoriesService =async (params,scope='',showToast=false)=>{
    try{
        const response =await apiCall(
            api.get,
            [`/api${scope}/helpdesk_categories`,{ params }],
            'Catégories récupérés avec succès !',
            'Erreur lors de la récupération des catégories',
            showToast
        );
        return response.member;
    }
    catch (error){
        console.error('Erreur dans getCategoriesService',error);
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

export {getCategoriesService}