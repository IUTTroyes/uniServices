import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

const getEdtEventsService = async (params, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/edt_events`, { params }],
            'Événéments de l\'emploi du temps du personnel récupérés avec succès',
            'Erreur lors de la récupération des événements de l\'emploi du temps du personnel',
            showToast
        );

        console.log('API Response in service:', response);
        // Handle both paginated and non-paginated responses
        return response.member || response['hydra:member'];
    } catch (error) {
        console.error('Erreur dans getPersonnelEdtEventsService:', error);
        throw error;
    }
}

export { getEdtEventsService };
