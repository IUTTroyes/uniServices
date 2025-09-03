import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

const getEtudiant = async (etudiantId, showToast = false) => {
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

// ----------------------------------------------
// ------------------- CREATE -------------------
// ----------------------------------------------


// ----------------------------------------------
// ------------------- UPDATE -------------------
// ----------------------------------------------
const updateEtudiant = async (etudiant, showToast = true) => {
    try {
        return await apiCall(
            api.patch,
            [`/api/etudiants/${etudiant.id}`, etudiant, {
                headers: {
                    'Content-Type': 'application/merge-patch+json',
                },
            }],
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

export { updateEtudiant, getEtudiant };
