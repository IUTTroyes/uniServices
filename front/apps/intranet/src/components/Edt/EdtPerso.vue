<script setup>
import { defineProps, watch, ref } from 'vue';

const props = defineProps({
    data: Object
});

const days = ref(props.data.days);
const currentDay = ref(props.data.currentDay);

watch(() => props.data.days, (newDays) => {
    days.value = newDays;
    const currentDayExists = newDays.some(day => day.dayNumber === currentDay.value);
});

const events = [
    { debut: '10/12/2024 08:00', fin: '10/12/2024 09:30', jour: 3, text: 'WS301D - Développer des parcours utilisateur au sein d\'un système d\'information', color: '#FBE4EE', colorFocus: '#fbc6e3' ,semestre: 'S3 Strat-UX Alt', groupe: 'CM', groupeColor: '#1f4ea6', salle: 'H018' },
    { debut: '10/12/2024 09:30', fin: '10/12/2024 12:30', jour: 3, text: 'WR601 - Entrepreneuriat', color: '#FBE4EE', colorFocus: '#fbc6e3', semestre: 'S3 Strat-UX Alt', groupe: 'TD CD', groupeColor: '#2d7315', salle: 'H201' },
    { debut: '10/12/2024 14:00', fin: '10/12/2024 18:30', jour: 3, text: 'WR602D - Hébergement et Cybersécurité', color: '#E1E2FE', colorFocus: '#b1b4ff', semestre: 'S3 DWebDi Alt', groupe: 'TP E', groupeColor: '#bd6910', salle: 'H201' },
    { debut: '11/12/2024 14:00', fin: '11/12/2024 17:00', jour: 4, text: 'PTUT', color: '#FFEDD2', colorFocus: '#ffdeae', semestre: 'S1', groupe: 'TD EF', groupeColor: '#2d7315', salle: 'H201' },
];

const calculatePosition = (time) => {
    const [hours, minutes] = time.split(':').map(Number);
    return (hours - 8) * 120 + minutes; // Convert time to minutes from 08:00
};

const calculateHeight = (start, end) => {
    return calculatePosition(end) - calculatePosition(start);
};

const maxEndTime = Math.max(...events.map(event => calculatePosition(event.fin.split(' ')[1])));
const calendarHeight = Math.max(maxEndTime, calculatePosition('18:30'));
</script>

<template>
    <div class="calendar grid grid-cols-5 gap-4" :style="{ height: calendarHeight + 'px' }">
        <div class="flex flex-col gap-5 light-surface-ground" v-for="(day, dayIndex) in days" :key="dayIndex">
            <div :class="['day text-center uppercase font-bold flex flex-col p-4 bg-opacity-20 surface-ground rounded-md', { 'bg-primary-light': currentDay === day.dayNumber, active: currentDay === day.dayNumber }]">
                {{ day.dayName }} <span class="font-black">{{ day.dayNumber }}</span>
            </div>
            <div class="relative h-full">
                <template v-for="(event, index) in events" :key="index">
                    <template v-if="event.jour === dayIndex">
                        <div class="event rounded-md absolute flex flex-col gap-1 opacity-90"
                             :style="{ top: calculatePosition(event.debut.split(' ')[1]) + 'px', height: calculateHeight(event.debut.split(' ')[1], event.fin.split(' ')[1]) - 5 + 'px', backgroundColor: event.color }">
                            <div class="event-header w-full p-2 rounded-t-md flex justify-between"
                                 :style="{ backgroundColor: event.colorFocus }">
                                <div class="flex flex-col">
                                    <div v-if="event.text.length > 50" v-tooltip.top="`${event.text}`">
                                        <span class="font-bold">{{ event.text.substring(0, 50) }}...</span>
                                    </div>
                                    <div v-else>
                                        <span  class="font-bold">{{ event.text }}</span>
                                    </div>
                                    <span>{{ event.debut.split(' ')[1] }} - {{ event.fin.split(' ')[1] }}</span>
                                </div>
                            </div>
                            <div class="event-body p-2">
                                <div><span>{{ event.semestre }}</span></div>
                                <div><span class="font-bold">{{ event.groupe }}</span> | <span>{{ event.salle }}</span></div>
                            </div>
                        </div>
                    </template>
                </template>
            </div>
        </div>
    </div>
</template>

<style scoped>
.surface-ground {
    background-color: var(--surface-ground);
}

.bg-primary-light {
    background-color: var(--p-tag-primary-background);
}

.active {
    color: var(--primary-color);
    border-top-left-radius: 0.5rem;
    border-top-right-radius: 0.5rem;
}

.relative {
    position: relative;
}

.event {
    position: absolute;
    width: 100%;
    color: black;
    overflow: auto;
    scrollbar-width: thin;
}
</style>
