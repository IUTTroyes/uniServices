import { ref } from 'vue';
import api from '@helpers/axios';
const actuEvents = ref([]);
const agendaEvents = ref([]);

const getActualitesService = async () => {
    const response = await api.get(`/api/actualites`);
    actuEvents.value = response.data.map(actu => ({
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
}

const getAgendaService = async () => {
    const response = await api.get(`/api/agenda`);
    agendaEvents.value = response.data.map(agenda => ({
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
}

export { getActualitesService, getAgendaService };
