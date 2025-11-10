import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

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


// ----------------------------------------------
// ------------------- DELETE -------------------
// ----------------------------------------------

export { createEtudiantNoteService };
