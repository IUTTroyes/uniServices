import api from '@helpers/axios'
import apiCall from '@helpers/apiCall'

const getStageEtudiantsService = async (params = {}, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            ['/api/stage_etudiants', { params }],
            'Étudiants de stage récupérés avec succès',
            'Erreur lors de la récupération des étudiants de stage',
            showToast
        );
        return response['member'] || response;
    } catch (error) {
        console.error('Erreur dans getStageEtudiantsService:', error);
        throw error;
    }
};

const updateStageEtudiantService = async (id, data, showToast = false) => {
    try {
        return await apiCall(
            api.patch,
            [`/api/stage_etudiants/${id}`, data, { headers: { 'Content-Type': 'application/merge-patch+json' } }],
            'Convention mise à jour avec succès',
            'Erreur lors de la mise à jour de la convention',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans updateStageEtudiantService:', error);
        throw error;
    }
};

const deleteStageEtudiantService = async (id, showToast = false) => {
    try {
        return await apiCall(
            api.delete,
            [`/api/stage_etudiants/${id}`],
            'Demande de convention supprimée',
            'Erreur lors de la suppression de la demande',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans deleteStageEtudiantService:', error);
        throw error;
    }
};

const createStageEtudiantService = async (data, showToast = false) => {
    try {
        return await apiCall(
            api.post,
            ['/api/stage_etudiants', data],
            'Demande de convention créée',
            'Erreur lors de la création de la demande',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans createStageEtudiantService:', error);
        throw error;
    }
};

// ---------- Rapport de stage (fichier PDF stocké côté serveur) ----------

/** URL de téléchargement du rapport (à utiliser dans un lien : le navigateur envoie le cookie de session). */
const rapportStageUrl = (id) => `/api/stage_etudiants/${id}/rapport`;

const deposerRapportStageService = async (id, file, onProgress = null) => {
    const formData = new FormData();
    formData.append('file', file);
    const response = await api.post(rapportStageUrl(id), formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
        onUploadProgress: (event) => {
            if (onProgress && event.total) {
                onProgress(Math.round((event.loaded * 100) / event.total));
            }
        }
    });
    return response.data;
};

const retirerRapportStageService = async (id) => {
    const response = await api.delete(rapportStageUrl(id));
    return response.data;
};

export {
    rapportStageUrl,
    deposerRapportStageService,
    retirerRapportStageService,
    getStageEtudiantsService,
    createStageEtudiantService,
    updateStageEtudiantService,
    deleteStageEtudiantService
};

