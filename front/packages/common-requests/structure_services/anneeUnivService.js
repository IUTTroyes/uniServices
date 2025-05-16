import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

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
        const response = await apiCall(
            api.get,
            [`/api/structure_annee_universitaires/${id}`],
            'Année universitaire récupérée avec succès',
            'Erreur lors de la récupération de l\'année universitaire',
            showToast
        );
        return response;
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
        return response.member[0];
    } catch (error) {
        console.error('Erreur dans getCurrentAnneeUniversitaireService:', error);
        throw error;
    }
}

export { getAllAnneesUniversitairesService, getAnneeUniversitaireService, getCurrentAnneeUniversitaireService };
