import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

const getEtudiantScolariteActifService = async (etudiant, anneeUniv, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/etudiant_scolarites/etudiant/${etudiant}/structureAnneeUniversitaire/${anneeUniv}`],
            'Scolarité de l\'étudiant récupérée avec succès',
            'Erreur lors de la récupération de la scolarité de l\'étudiant',
            showToast
        );
        return response;
    } catch (error) {
        console.error('Erreur dans getEtudiantScolariteActifService:', error);
        throw error;
    }
}

const updateEtudiant = async (etudiant, showToast = true) => {
    try {
        const response = await apiCall(
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
        return response;
    } catch (error) {
        console.error('Erreur dans updateEtudiant:', error);
        throw error;
    }
}

export { updateEtudiant, getEtudiantScolariteActifService };
