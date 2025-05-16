import api from '@helpers/axios';
import createApiService from '@requests/apiService';
import apiCall from '@helpers/apiCall';

const personnelDepartementService = createApiService('/api/structure_departement_personnels');

const getPersonnelsDepartementService = async (departementId, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/structure_departement_personnels/by_departement/${departementId}`],
            'Personnels récupérés avec succès',
            'Erreur lors de la récupération des personnels',
            showToast
        );
        return response['member'];
    } catch (error) {
        console.error('Erreur dans getPersonnelsDepartementService:', error);
        throw error;
    }
}

// todo: méthode get Enseignants /= getPersonnels

export { getPersonnelsDepartementService };
