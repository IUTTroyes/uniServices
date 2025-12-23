import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

const getEdtEventsService = async (params, scope ='', showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api${scope}/edt_events`, { params }],
            'Événéments de l\'emploi du temps du personnel récupérés avec succès',
            'Erreur lors de la récupération des événements de l\'emploi du temps du personnel',
            showToast
        );
        // Certains endpoints d'ApiPlatform retournent { member: [...] } (ou hydra:member),
        // d'autres (notamment nos endpoints personnalisés retournant un DTO) retournent
        // directement un objet. On gère les deux cas.
        if (!response) return null;
        if (Array.isArray(response)) return response; // déjà un tableau
        if (response.member) return response.member;
        if (response['hydra:member']) return response['hydra:member'];
        // sinon c'est probablement un DTO / objet simple (ex: EdtStatsDto)
        return response;
    } catch (error) {
        console.error('Erreur dans getPersonnelEdtEventsService:', error);
        throw error;
    }
}

export { getEdtEventsService };
