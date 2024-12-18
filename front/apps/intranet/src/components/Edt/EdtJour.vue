<script setup>
import { ref } from 'vue';

// todo: requête pour récupérer l'evenement en cours et le suivant - si pas d'event en cours, on ne récupère que le suivant
const events = ref([
    {
        debut: '2024-12-18T10:00:00',
        fin: '2024-12-18T11:30:00',
        text: 'WS301D - Développer des parcours utilisateur au sein d\'un système d\'information',
        color: '#FFEDD2',
        colorFocus: '#ffdeae',
        semestre: 'S3 Strat-UX Alt',
        groupe: 'TD GH',
        groupeColor: '#1f4ea6',
        salle: 'H018'
    },
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

    console.log(now, start, end);
    console.log(now >= start && now <= end);

    return now >= start && now <= end;
};

const sortedEvents = ref([]);

const sortEvents = () => {
    const ongoingEvent = events.value.find(isEventOngoing);
    if (ongoingEvent) {
        sortedEvents.value = [ongoingEvent, ...events.value.filter(event => event !== ongoingEvent)];
    } else {
        sortedEvents.value = [{ text: 'Aucun cours', color: 'rgba(223,223,223,0.2)', colorFocus: '#FFFFFF' }, ...events.value];
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
            <div
                :class="['flex flex-row justify-between items-start rounded-lg h-full relative text-black', {'border-4': index === 0 && event.text !== 'Aucun cours'}, {'text-color palm': event.text === 'Aucun cours'}]"
                :style="{ borderColor: event.colorFocus, backgroundColor: event.color }">
                <div v-if="event.text !== 'Aucun cours'" class="flex flex-col justify-center items-center p-4 rounded-l-md h-full" :style="{ backgroundColor: event.colorFocus }">
                    <div v-if="!isToday(event.debut)" class="text-sm font-bold flex flex-col items-center"><span>{{ new Date(event.debut).toLocaleDateString('fr-FR', {weekday: 'long'}) }}</span><span>{{ new Date(event.debut).toLocaleDateString('fr-FR') }}</span></div>
                    <div v-else class="text-sm font-bold">Aujourd'hui</div>
                    <div class="text-xl font-black">{{ event.debut.split('T')[1].slice(0, 5) }}</div>
                    <div class="text-xl font-black">{{ event.fin.split('T')[1].slice(0, 5) }}</div>
                </div>
                <div class="flex flex-col justify-center p-4 gap-2">
                    <div class="text-lg font-bold">{{ event.text }}</div>
                    <div v-if="event.text !== 'Aucun cours'">
                        <div>{{ event.semestre }} | <span class="font-bold">{{ event.groupe }}</span></div>
                        <div class="text-lg font-bold">{{ event.salle }}</div>
                    </div>
                </div>
                <Tag v-if="index === 0" value="Événement en cours" class="absolute right-2 bottom-2 !text-white !bg-black"/>
                <Tag v-else-if="index !== 0 && event.text !== 'Aucun cours'" value="Prochain évènement" class="absolute right-2 bottom-2 !text-white !bg-black"/>
            </div>
        </div>
    </div>
</template>

<style scoped>
.palm {
    background-image: url("@/assets/illu/palm.svg");
    background-size: 50%;
    background-repeat: no-repeat;
    background-position: right;
}
</style>
