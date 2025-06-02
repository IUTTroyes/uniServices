import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

const getPersonnelEnseignantHrsService = async (personnelId, anneeUnivId, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/personnel_enseignant_hrs?anneeUniversitaire=${anneeUnivId}&personnel=${personnelId}`],
            'Heures du personnel récupérées avec succès',
            'Erreur lors de la récupération des heures du personnel',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getPersonnelHrsService:', error);
        throw error;
    }
}

const getPersonnelEnseignantTypesHrsService = async (personnelId, anneeUnivId, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/personnel_enseignant_type_hrs`],
            'Types d\'heures du personnel récupérés avec succès',
            'Erreur lors de la récupération des types d\'heures du personnel',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getPersonnelEnseignantTypesHrsService:', error);
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

const deletePersonnelEnseignantHrsService = async (hrsId, showToast = false) => {
    try {
        return await apiCall(
            api.delete,
            [`/api/personnel_enseignant_hrs/${hrsId}`],
            'Heures du personnel supprimées avec succès',
            'Erreur lors de la suppression des heures du personnel',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans deletePersonnelEnseignantHrsService:', error);
        throw error;
    }
}

export { getPersonnelEnseignantHrsService, getPersonnelEnseignantTypesHrsService, deletePersonnelEnseignantHrsService };
