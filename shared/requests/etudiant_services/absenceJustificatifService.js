import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

const getAbsenceJustificatifsService = async (params = {}, scope = '', showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api${scope}/etudiant_absence_justificatifs`, { params }],
            'Justificatifs récupérés avec succès',
            'Erreur lors de la récupération des justificatifs',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getAbsenceJustificatifsService:', error);
        throw error;
    }
}

const patchAbsenceJustificatifService = async (id, data, scope = '', showToast = false) => {
    try {
        const response = await apiCall(
            api.patch,
            [`/api${scope}/etudiant_absence_justificatifs/${id}`, data, { headers: {'Content-Type': 'application/merge-patch+json'}}],
            'Justificatif mis à jour avec succès',
            'Erreur lors de la mise à jour du justificatif',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans patchAbsenceJustificatifService:', error);
        throw error;
    }
}

export { getAbsenceJustificatifsService, patchAbsenceJustificatifService };
