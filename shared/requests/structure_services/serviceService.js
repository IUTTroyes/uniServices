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

/** Liste courte (id, libelle) de tous les services, triée par libellé. */
const getServicesListeService = async () => {
    const response = await api.get('/api/mini/structure_services', { params: { pagination: false } });
    const data = response.data;
    const liste = Array.isArray(data) ? data : (data?.member || data?.['hydra:member'] || []);
    return liste.slice().sort((a, b) => (a.libelle || '').localeCompare(b.libelle || '', 'fr'));
}

// ----------------------------------------------
// ------------------- CREATE -------------------
// (réservé au super administrateur)
// ----------------------------------------------
const createServiceService = async (libelle) => {
    const response = await api.post('/api/structure_services', { libelle }, { headers: { 'Content-Type': 'application/ld+json' } });
    return response.data;
}

// ----------------------------------------------
// ------------------- UPDATE -------------------
// ----------------------------------------------
const updateServiceService = async (id, libelle) => {
    const response = await api.patch(`/api/structure_services/${id}`, { libelle }, { headers: { 'Content-Type': 'application/merge-patch+json' } });
    return response.data;
}

// ----------------------------------------------
// ------------------- DELETE -------------------
// ----------------------------------------------
const deleteServiceService = async (id) => api.delete(`/api/structure_services/${id}`);

export {getServicesService, getServicesListeService, createServiceService, updateServiceService, deleteServiceService}