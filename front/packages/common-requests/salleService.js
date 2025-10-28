import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

const getSallesService = async (params = [], showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/salles`, {params}],
            'Salles récupérés avec succès',
            'Erreur lors de la récupération des salles',
            showToast
        );
        return response['member'];
    } catch (error) {
        console.error('Erreur dans getSallesDepartementService:', error);
        throw error;
    }
}

export { getSallesService };
