import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';
import createApiService from "@requests/apiService";
// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

const getEtudiantService = async (etudiantId, showToast = false) => {
    try {
        return await apiCall(
            api.get,
            [`/api/etudiants/${etudiantId}`],
            'Étudiant récupéré avec succès',
            'Erreur lors de la récupération de l\'étudiant',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getEtudiant:', error);
        throw error;
    }
}

const getEtudiantsService = async (params = {}, showToast = false, pagination = true) => {
    try {
        const response= await apiCall(
            api.get,
            ['/api/etudiants', { params }],
            'Étudiants récupérés avec succès',
            'Erreur lors de la récupération des étudiants',
            showToast
        );
        response.member.totalItems = response.totalItems;
        return response.member
    } catch (error) {
        console.error('Erreur dans getEtudiants:', error);
        throw error;
    }
}

// ----------------------------------------------
// ------------------- CREATE -------------------
// ----------------------------------------------

const createEtudiantService = async (etudiant, showToast = true) => {
    try {
        return await apiCall(
            api.post,
            ['/api/etudiants', etudiant],
            'Étudiant créé avec succès',
            'Erreur lors de la création de l\'étudiant',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans createEtudiant:', error);
        throw error;
    }
}

const createEtudiantsService = async (data, showToast = true) => {
    try {
        return await apiCall(
            api.post,
            ['/api/etudiants/new', data],
            'Étudiant créé avec succès',
            'Erreur lors de la création de l\'étudiant',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans createEtudiant:', error);
        throw error;
    }
}

const importEtudiantApogeeService = async (data, showToast = true) => {
    try {
        return await apiCall(
            api.post,
            ['/api/etudiants/import_apogee', data],
            'Étudiants importés avec succès',
            'Erreur lors de l\'importation des étudiants',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans importEtudiantApogee:', error);
        throw error;
    }
}

// ----------------------------------------------
// ------------------- UPDATE -------------------
// ----------------------------------------------
const updateEtudiantService = async (id, data, showToast = true) => {
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

export { updateEtudiantService, getEtudiantService, createEtudiantService, createEtudiantsService, importEtudiantApogeeService, getEtudiantsService };
