import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';
// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

const getEtablissementService = async (showToast = false) => {
    try {
        return await apiCall(
            api.get,
            [`/api/etablissements`],
            'Établissement récupéré avec succès',
            'Erreur lors de la récupération de l\'établissement',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getEtablissementService:', error);
        throw error;
    }
}

// ----------------------------------------------
// ------------------- CREATE -------------------
// ----------------------------------------------

// ----------------------------------------------
// ------------------- UPDATE -------------------
// ----------------------------------------------

const updateEtablissementService = async (etablissementId, data, showToast = false) => {
    try {
        return await apiCall(
            api.put,
            [`/api/etablissement/${etablissementId}`],
            'Établissement mis à jour avec succès',
            'Erreur lors de la mise à jour de l\'établissement',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans updateEtablissementService:', error);
        throw error;
    }
}

// ----------------------------------------------
// ------------------- DELETE -------------------
// ----------------------------------------------

export { getEtablissementService, updateEtablissementService };
