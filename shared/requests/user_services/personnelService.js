import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

const getPersonnelsService = async (params, scope = '', showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api${scope}/personnels`, {params}],
            'Personnels récupérés avec succès',
            'Erreur lors de la récupération des personnels',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getPersonnelsDepartementService:', error);
        throw error;
    }
}

// todo: méthode get Enseignants /= getPersonnels

export { getPersonnelsService };
