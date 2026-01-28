import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

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
            [`/api/structure_pns?diplome=${diplomeId}&anneeUniversitaire=${anneeUnivId}`],
            'PN du diplôme récupérés avec succès',
            'Erreur lors de la récupération du PN du diplôme',
            showToast
        );
        return response.member[0] || null; // Retourne le premier PN ou null si aucun n'est trouvé
    } catch (error) {
        console.error('Erreur dans getPnsDiplome:', error);
        throw error;
    }
}

// ----------------------------------------------
// ------------------- CREATE -------------------
// ----------------------------------------------


// ----------------------------------------------
// ------------------- UPDATE -------------------
// ----------------------------------------------

// ----------------------------------------------
// ------------------- DELETE -------------------
// ----------------------------------------------

const deletePnService = async (pnId, showToast = false) => {
    try {
        return await apiCall(
            api.delete,
            [`/api/structure_pns/${pnId}`],
            'PN supprimé avec succès',
            'Erreur lors de la suppression du PN',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans deletePnService:', error);
        throw error;
    }
}

export { getAllPns, getPnsDiplome, getPnDiplome, deletePnService };
