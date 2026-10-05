import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

const getSuiviPointage = async (params = {}, scope = '', showToast = false) => {
    try {
        const apiParams = {...params};

        return await apiCall(
            api.get,
            [`/api${scope}/edt_appel`, { params: apiParams }],
            'Suivi de pointage récupéré avec succès',
            'Erreur lors de la récupération du suivi de pointage',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getSuiviPointage:', error);
        throw error;
    }
};

export { getSuiviPointage };
