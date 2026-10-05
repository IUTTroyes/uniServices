import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

const getAbsenceJustificatifsService = async (params = {}, scope = '', showToast = false) => {
    try {
        const apiParams = {...params};

        if (apiParams.filters) {
            if (apiParams.filters['etudiant.display']?.value) {
                apiParams.etudiant = apiParams.filters['etudiant.display'].value;
            }

            if (apiParams.filters.motif?.value) {
                apiParams.motif = apiParams.filters.motif.value;
            }

            if (apiParams.filters.etat?.value) {
                apiParams.etat = apiParams.filters.etat.value;
            }

            const periodeValue = apiParams.filters.periode?.value;
            if (Array.isArray(periodeValue) && periodeValue.length === 2) {
                const [debut, fin] = periodeValue;

                if (debut) {
                    apiParams.debut = debut;
                }

                if (fin) {
                    apiParams.fin = fin;
                }
            }
        }

        if (Array.isArray(apiParams.sort) && apiParams.sort.length > 0) {
            apiParams.order = apiParams.sort.reduce((acc, sortItem) => {
                if (!sortItem?.field || !sortItem?.order) return acc;

                const mappedField = sortItem.field === 'periode' ? 'debut' : sortItem.field;
                acc[mappedField] = sortItem.order > 0 ? 'asc' : 'desc';
                return acc;
            }, {});
        }

        delete apiParams.filters;
        delete apiParams.sort;

        return await apiCall(
            api.get,
            [`/api${scope}/etudiant_absence_justificatifs`, { params: apiParams }],
            'Justificatifs récupérés avec succès',
            'Erreur lors de la récupération des justificatifs',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getAbsenceJustificatifsService:', error);
        throw error;
    }
}

const updateAbsenceJustificatifService = async (id, data, scope = '', showToast = false) => {
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
        console.error('Erreur dans updateAbsenceJustificatifService:', error);
        throw error;
    }
}

const deleteAbsenceJustificatifService = async (id, scope = '', showToast = false) => {
    try {
        return await apiCall(
            api.delete,
            [`/api${scope}/etudiant_absence_justificatifs/${id}`],
            'Justificatif supprimé avec succès',
            'Erreur lors de la suppression du justificatif',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans deleteAbsenceJustificatifService:', error);
        throw error;
    }
}

export { getAbsenceJustificatifsService, updateAbsenceJustificatifService, deleteAbsenceJustificatifService };
