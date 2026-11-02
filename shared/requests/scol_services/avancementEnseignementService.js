import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

const getAvancementsEnseignementService = async (params = {}, scope = '', showToast = false) => {
    try {
        return await apiCall(
            api.get,
            [`/api${scope}/avancement_enseignements`, { params }],
            'Avancement de l’enseignement récupéré avec succès',
            'Erreur lors de la récupération de l’avancement',
            showToast,
        );
    } catch (error) {
        console.error('Erreur dans getAvancementsEnseignementService:', error);
        throw error;
    }
};

// ----------------------------------------------
// ------------------- POST ---------------------
// ----------------------------------------------

const saveAvancementEnseignementService = async (payload, params = {}, scope = '', showToast = false) => {
    try {
        return await apiCall(
            api.post,
            [`/api${scope}/avancement_enseignements`, payload, { params }],
            'Repère d’avancement enregistré avec succès',
            'Erreur lors de l’enregistrement du repère',
            showToast,
        );
    } catch (error) {
        console.error('Erreur dans saveAvancementEnseignementService:', error);
        // apiCall retransmet l'erreur Axios, notamment le statut 409 et le brouillon à conserver.
        throw error;
    }
};

export {
    getAvancementsEnseignementService,
    saveAvancementEnseignementService,
};
