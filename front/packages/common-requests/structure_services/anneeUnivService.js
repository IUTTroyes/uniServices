import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

const getAllAnneesUniversitairesService = async (showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/structure_annee_universitaires`],
            'Années universitaires récupérées avec succès',
            'Erreur lors de la récupération des années universitaires',
            showToast
        );
        return response['member'];
    } catch (error) {
        console.error('Erreur dans getAllAnneesUniversitairesService:', error);
        throw error;
    }
}

const getAnneeUniversitaireService = async (id, showToast = false) => {
    try {
        return await apiCall(
            api.get,
            [`/api/structure_annee_universitaires/${id}`],
            'Année universitaire récupérée avec succès',
            'Erreur lors de la récupération de l\'année universitaire',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getAnneeUniversitaireService:', error);
        throw error;
    }
}

const getCurrentAnneeUniversitaireService = async (showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/structure_annee_universitaires?actif=true`],
            'Année universitaire courante récupérée avec succès',
            'Erreur lors de la récupération de l\'année universitaire courante',
            showToast
        );
        return await response.member[0];
    } catch (error) {
        console.error('Erreur dans getCurrentAnneeUniversitaireService:', error);
        throw error;
    }
}

// ----------------------------------------------
// ------------------- CREATE -------------------
// ----------------------------------------------

const createAnneeUniversitaireService = async (data, showToast = false) => {
    try {
        return await apiCall(
            api.post,
            [`/api/structure_annee_universitaires`, data, { headers: { 'Content-Type': 'application/ld+json' }}],
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

const updateAnneeUniversitaireService = async (id, data, showToast = false) => {
    try {
        return await apiCall(
            api.patch,
            [`/api/structure_annee_universitaires/${id}`, data, { headers: { 'Content-Type': 'application/merge-patch+json' }}],
            'Année universitaire mise à jour avec succès',
            'Erreur lors de la mise à jour de l\'année universitaire',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans updateAnneeUniversitaireService:', error);
        throw error;
    }
}

const activateAnneeUniversitaireService = async (id, showToast = false) => {
    try {
        return await apiCall(
            api.patch,
            [`/api/structure_annee_universitaires/${id}`, { actif: true }, { headers: { 'Content-Type': 'application/merge-patch+json' }}],
            'Année universitaire activée avec succès',
            'Erreur lors de l\'activation de l\'année universitaire',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans activateAnneeUniversitaireService:', error);
        throw error;
    }
}

// ----------------------------------------------
// ------------------- DELETE -------------------
// ----------------------------------------------

const deleteAnneeUniversitaireService = async (id, showToast = false) => {
    try {
        return await apiCall(
            api.delete,
            [`/api/structure_annee_universitaires/${id}`],
            'Année universitaire supprimée avec succès',
            'Erreur lors de la suppression de l\'année universitaire',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans deleteAnneeUniversitaireService:', error);
        throw error;
    }
}

export { getAllAnneesUniversitairesService, getAnneeUniversitaireService, getCurrentAnneeUniversitaireService, createAnneeUniversitaireService, updateAnneeUniversitaireService, activateAnneeUniversitaireService, deleteAnneeUniversitaireService };
