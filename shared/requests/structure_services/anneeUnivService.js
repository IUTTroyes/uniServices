import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

const getAnneesUniversitairesService = async (params = {}, scope = '', showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api${scope}/structure_annee_universitaires`, {params}],
            'Années universitaires récupérées avec succès',
            'Erreur lors de la récupération des années universitaires',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getAnneesUniversitairesService:', error);
        throw error;
    }
}

const getAnneeUniversitaireService = async (id, scope = '', showToast = false) => {
    try {
        return await apiCall(
            api.get,
            [`/api${scope}/structure_annee_universitaires/${id}`],
            'Année universitaire récupérée avec succès',
            'Erreur lors de la récupération de l\'année universitaire',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getAnneeUniversitaireService:', error);
        throw error;
    }
}

// ----------------------------------------------
// ------------------- CREATE -------------------
// ----------------------------------------------

const createAnneeUniversitaireService = async (data, scope = '', showToast = false) => {
    try {
        return await apiCall(
            api.post,
            [`/api${scope}/structure_annee_universitaires`, data, { headers: { 'Content-Type': 'application/ld+json' }}],
            'Année universitaire créée avec succès',
            'Erreur lors de la création de l\'année universitaire',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans createAnneeUniversitaireService:', error);
        throw error;
    }
}

// ----------------------------------------------
// ------------------- UPDATE -------------------
// ----------------------------------------------

const updateAnneeUniversitaireService = async (id, data, scope = '', showToast = false) => {
    try {
        return await apiCall(
            api.patch,
            [`/api${scope}/structure_annee_universitaires/${id}`, data, { headers: { 'Content-Type': 'application/merge-patch+json' }}],
            'Année universitaire mise à jour avec succès',
            'Erreur lors de la mise à jour de l\'année universitaire',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans updateAnneeUniversitaireService:', error);
        throw error;
    }
}

// ----------------------------------------------
// ------------------- DELETE -------------------
// ----------------------------------------------

const deleteAnneeUniversitaireService = async (id, scope = '', showToast = false) => {
    try {
        return await apiCall(
            api.delete,
            [`/api${scope}/structure_annee_universitaires/${id}`],
            'Année universitaire supprimée avec succès',
            'Erreur lors de la suppression de l\'année universitaire',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans deleteAnneeUniversitaireService:', error);
        throw error;
    }
}

export { getAnneesUniversitairesService, getAnneeUniversitaireService, createAnneeUniversitaireService, updateAnneeUniversitaireService, deleteAnneeUniversitaireService };
