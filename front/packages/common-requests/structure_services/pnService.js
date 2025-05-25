import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

const getAllPns = async (showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            ['/api/structure_pns'],
            'PNs récupérés avec succès',
            'Erreur lors de la récupération des PNs',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getAllPns:', error);
        throw error;
    }
}

const getPnsDiplome = async (diplomeId, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/structure_pns?diplome=${diplomeId}`],
            'PNs du diplôme récupérés avec succès',
            'Erreur lors de la récupération des PNs du diplôme',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getPnsDiplome:', error);
        throw error;
    }
}

const getPnDiplome = async (diplomeId, anneeUnivId, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/structure_pns?diplome=${diplomeId}&annee_universitaire=${anneeUnivId}`],
            'PN du diplôme récupérés avec succès',
            'Erreur lors de la récupération du PN du diplôme',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getPnsDiplome:', error);
        throw error;
    }
}

export { getAllPns, getPnsDiplome, getPnDiplome };
