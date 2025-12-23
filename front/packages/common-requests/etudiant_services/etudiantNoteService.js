import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

const getEtudiantNotesService = async (params = {}, scope = '', showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api${scope}/etudiant_notes`, { params }],
            'Notes des étudiants récupérées avec succès',
            'Erreur lors de la récupération des notes des étudiants',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getEtudiantNotesService:', error);
        throw error;
    }
}

// ----------------------------------------------
// ------------------- CREATE -------------------
// ----------------------------------------------

const createEtudiantNoteService = async (data, showToast = false) => {
    try {
        return await apiCall(
            api.post,
            ['/api/etudiant_notes', data, { headers: { 'Content-Type': 'application/ld+json' }}],
            'Note de l\'étudiant créée avec succès',
            'Erreur lors de la création de la note de l\'étudiant',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans createEtudiantNoteService:', error);
        throw error;
    }
}

// ----------------------------------------------
// ------------------- UPDATE -------------------
// ----------------------------------------------

const updateEtudiantNoteService = async (etudiantNoteId, data, showToast = false) => {
    try {
        return await apiCall(
            api.patch,
            [`/api/etudiant_notes/${etudiantNoteId}`, data, { headers: { 'Content-Type': 'application/merge-patch+json' }}],
            'Note de l\'étudiant mise à jour avec succès',
            'Erreur lors de la mise à jour de la note de l\'étudiant',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans updateEtudiantNoteService:', error);
        throw error;
    }
}

// ----------------------------------------------
// ------------------- DELETE -------------------
// ----------------------------------------------

export { createEtudiantNoteService, getEtudiantNotesService, updateEtudiantNoteService };
