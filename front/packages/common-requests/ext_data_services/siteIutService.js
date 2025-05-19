import { ref } from 'vue';
import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

const actuEvents = ref([]);
const agendaEvents = ref([]);

const getActualitesService = async (showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/actualites`],
            'Actualités récupérées avec succès',
            'Erreur lors de la récupération des actualités',
            showToast
        );
        actuEvents.value = response.map(actu => ({
            title: actu.title,
            date: new Date(actu.pubDate).toLocaleDateString('fr-FR', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            }),
            content: actu.description,
            image: actu.image,
            link: actu.link
        }));
        return actuEvents.value;
    } catch (error) {
        console.error('Erreur dans getActualitesService:', error);
        throw error;
    }
}

const getAgendaService = async (showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/agenda`],
            'Agenda récupéré avec succès',
            'Erreur lors de la récupération de l\'agenda',
            showToast
        );
        agendaEvents.value = response.map(agenda => ({
            title: agenda.title,
            date: new Date(agenda.pubDate).toLocaleDateString('fr-FR', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            }),
            content: agenda.description,
            image: agenda.image,
            link: agenda.link
        }));
        return agendaEvents.value;
    } catch (error) {
        console.error('Erreur dans getAgendaService:', error);
        throw error;
    }
}

export { getActualitesService, getAgendaService };
