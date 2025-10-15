<script setup>
import { ref } from 'vue';
import EdtEvent from './EdtEvent.vue';

// todo: requête pour récupérer l'evenement en cours et le suivant - si pas d'event en cours, on ne récupère que le suivant
const events = ref([
    // {
    //     debut: '2024-12-18T12:00:00',
    //     fin: '2024-12-18T15:30:00',
    //     text: 'WS301D - Développer des parcours utilisateur au sein d\'un système d\'information',
    //     color: '#FFEDD2',
    //     colorFocus: '#ffdeae',
    //     semestre: 'S3 Strat-UX Alt',
    //     groupe: 'TD GH',
    //     groupeColor: '#1f4ea6',
    //     salle: 'H018'
    // },
    {
        debut: '2024-12-10T09:30:00',
        fin: '2024-12-10T11:00:00',
        text: 'WS301D - Développer des parcours utilisateur au sein d\'un système d\'information',
        color: '#FFEDD2',
        colorFocus: '#ffdeae',
        semestre: 'S3 Strat-UX Alt',
        groupe: 'TD GH',
        groupeColor: '#1f4ea6',
        salle: 'H018'
    }
]);

const isEventOngoing = (event) => {
    const now = new Date();
    const start = new Date(event.debut);
    const end = new Date(event.fin);

    return now >= start && now <= end;
};

const sortedEvents = ref([]);

const sortEvents = () => {
    const ongoingEvent = events.value.find(isEventOngoing);
    if (ongoingEvent) {
        sortedEvents.value = [
            { ...ongoingEvent, isFirst: true },
            ...events.value.filter(event => event !== ongoingEvent).map(event => ({ ...event, isFirst: false }))
        ];
    } else {
        sortedEvents.value = [
            { text: 'Aucun cours', color: 'rgba(223,223,223,0.2)', colorFocus: '#FFFFFF', isFirst: true },
            ...events.value.map(event => ({ ...event, isFirst: false }))
        ];
    }
};

sortEvents();

const isToday = (dateString) => {
    const today = new Date();
    const date = new Date(dateString);
    return today.toDateString() === date.toDateString();
};
</script>

<template>
    <div class="flex flex-row justify-between gap-4">
        <div v-for="(event, index) in sortedEvents" :key="index" class="w-full">
            <EdtEvent :event="event" type="jour" />
        </div>
    </div>
</template>

<style scoped>
/* Styles moved to EdtEvent.vue */
</style>
