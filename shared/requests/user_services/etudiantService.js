import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';
// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

const getEtudiantService = async (etudiantId, scope = '', showToast = false) => {
    try {
        return await apiCall(
            api.get,
            [`/api${scope}/etudiants/${etudiantId}`],
            'Étudiant récupéré avec succès',
            'Erreur lors de la récupération de l\'étudiant',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getEtudiant:', error);
        throw error;
    }
}

const getEtudiantsService = async (params, scope = '', showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api${scope}/etudiants`, { params }],
            'Étudiants récupérés avec succès',
            'Erreur lors de la récupération des étudiants',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getEtudiants:', error);
        throw error;
    }
}

// ----------------------------------------------
// ------------------- CREATE -------------------
// ----------------------------------------------

const createEtudiantService = async (etudiant, scope = '', showToast = true) => {
    try {
        return await apiCall(
            api.post,
            [`/api${scope}/etudiants`, etudiant],
            'Étudiant créé avec succès',
            'Erreur lors de la création de l\'étudiant',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans createEtudiant:', error);
        throw error;
    }
}

// ----------------------------------------------
// ------------------- UPDATE -------------------
// ----------------------------------------------
const updateEtudiantService = async (id, data, scope = '', showToast = true) => {
    try {
        return await apiCall(
            api.patch,
            [`/api${scope}/etudiants/${id}`, data, { headers: { 'Content-Type': 'application/merge-patch+json' } }],
            'Étudiant mis à jour avec succès',
            'Erreur lors de la mise à jour de l\'étudiant',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans updateEtudiant:', error);
        throw error;
    }
}

// ----------------------------------------------
// ------------------- DELETE -------------------
// ----------------------------------------------

const deleteEtudiantService = async (id, scope = '', showToast = true) => {
    try {
        return await apiCall(
            api.delete,
            [`/api${scope}/etudiants/${id}`],
            'Étudiant supprimé avec succès',
            'Erreur lors de la suppression de l\'étudiant',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans deleteEtudiant:', error);
        throw error;
    }
}

export { updateEtudiantService, getEtudiantService, createEtudiantService, getEtudiantsService, deleteEtudiantService };
