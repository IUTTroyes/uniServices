<script setup>
import {defineProps, watch, ref, useTemplateRef} from 'vue';

const props = defineProps({
    data: Object
});

const days = ref(props.data.days);
const currentDay = ref(props.data.currentDay);

watch(() => props.data.days, (newDays) => {
    days.value = newDays;
    const currentDayExists = newDays.some(day => day.dayNumber === currentDay.value);
});

const events = ref([
    { id: 1, debut: '10/12/2024 08:00', fin: '10/12/2024 09:30', jour: 3, text: 'WS301D - Développer des parcours utilisateur au sein d\'un système d\'information', color: '#FBE4EE', colorFocus: '#fbc6e3' ,semestre: 'S3 Strat-UX Alt', groupe: 'CM', groupeColor: '#1f4ea6', salle: 'H018', showMenu: useTemplateRef('menu_1') },
    { id: 2, debut: '10/12/2024 09:30', fin: '10/12/2024 12:30', jour: 3, text: 'WR601 - Entrepreneuriat', color: '#FBE4EE', colorFocus: '#fbc6e3', semestre: 'S3 Strat-UX Alt', groupe: 'TD CD', groupeColor: '#2d7315', salle: 'H201', showMenu: useTemplateRef('menu_2') },
    { id: 3, debut: '10/12/2024 14:00', fin: '10/12/2024 18:30', jour: 3, text: 'WR602D - Hébergement et Cybersécurité', color: '#E1E2FE', colorFocus: '#b1b4ff', semestre: 'S3 DWebDi Alt', groupe: 'TP E', groupeColor: '#bd6910', salle: 'H201', showMenu: {} },
    { id: 4, debut: '11/12/2024 14:00', fin: '11/12/2024 21:00', jour: 4, text: 'PTUT', color: '#FFEDD2', colorFocus: '#ffdeae', semestre: 'S1', groupe: 'TD EF', groupeColor: '#2d7315', salle: 'H201', showMenu: {} },
    { id: 5, debut: '11/12/2024 09:30', fin: '11/12/2024 11:00', jour: 4, text: 'WR602D - Hébergement et Cybersécurité', color: '#E1E2FE', colorFocus: '#b1b4ff', semestre: 'S3 DWebDi Alt', groupe: 'TP E', groupeColor: '#bd6910', salle: 'H201', showMenu: {} },
    { id: 6, debut: '11/12/2024 11:00', fin: '11/12/2024 12:30', jour: 4, text: 'PTUT', color: '#E1E2FE', colorFocus: '#b1b4ff', semestre: 'S3 DWebDi Alt', groupe: 'TP E', groupeColor: '#bd6910', salle: 'H201', showMenu: {} },
    { id: 7, debut: '11/12/2024 11:00', fin: '11/12/2024 12:30', jour: 4, text: 'PTUT', color: '#FFEDD2', colorFocus: '#ffdeae', semestre: 'S1', groupe: 'TD EF', groupeColor: '#2d7315', salle: 'H201', showMenu: {} },
]);

const calculatePosition = (time) => {
    const [hours, minutes] = time.split(':').map(Number);
    return (hours - 8) * 120 + minutes; // Convert time to minutes from 08:00
};

const calculateHeight = (start, end) => {
    return calculatePosition(end) - calculatePosition(start);
};

const maxEndTime = Math.max(...events.value.map(event => calculatePosition(event.fin.split(' ')[1])));
const calendarHeight = Math.max(maxEndTime, calculatePosition('18:30'));

const getOverlappingEvents = (dayIndex) => {
    const dayEvents = events.value.filter(event => event.jour === dayIndex);
    const overlappingEvents = [];

    dayEvents.forEach((event, index) => {
        const overlaps = dayEvents.filter((e, i) => i !== index && (
            (calculatePosition(e.debut.split(' ')[1]) < calculatePosition(event.fin.split(' ')[1]) &&
                calculatePosition(e.fin.split(' ')[1]) > calculatePosition(event.debut.split(' ')[1]))
        ));
        overlappingEvents.push({ event, overlaps });
    });

    return overlappingEvents;
};

const menu = ref();
const itemsTest = ref([
    {
        items: [
            {
                label: 'cahier de texte',
                command: () => {
                    test();
                }
            },
            {
                label: 'appel',
            }
        ]
    }
]);

const test = () => {
    console.log('test');
}

const selected = ref(null);
const toggle = (edtEvent, event) => {
    menu.value.toggle(event);
    selected.value = edtEvent;
};
</script>

<template>
    <div class="calendar grid grid-cols-5 gap-4" :style="{ height: calendarHeight + 'px' }">
        <div class="flex flex-col gap-5 light-surface-ground" v-for="(day, dayIndex) in days" :key="dayIndex">
            <div :class="['day text-center uppercase font-bold flex flex-col p-4 bg-opacity-20 surface-ground rounded-md', { 'bg-primary-light': currentDay === day.dayNumber, active: currentDay === day.dayNumber }]">
                {{ day.dayName }} <span class="font-black">{{ day.dayNumber }}</span>
            </div>
            <div class="relative h-full">
                <template v-for="(eventGroup, index) in getOverlappingEvents(dayIndex)" :key="index">
                    <div v-for="(event, subIndex) in [eventGroup.event, ...eventGroup.overlaps]" :key="subIndex" class="event rounded-md absolute flex flex-col gap-1"
                         :style="{ top: calculatePosition(event.debut.split(' ')[1]) + 'px', height: calculateHeight(event.debut.split(' ')[1], event.fin.split(' ')[1]) - 5 + 'px', backgroundColor: event.color, width: `calc(100% / ${eventGroup.overlaps.length + 1})`, left: `calc(${subIndex} * 100% / ${eventGroup.overlaps.length + 1})` }">
                        <div class="event-header w-full p-2 rounded-t-md flex justify-between"
                             :style="{ backgroundColor: event.colorFocus }">
                            <div class="flex flex-col">
                                <div v-if="event.text.length > 50" v-tooltip.top="`${event.text}`">
                                    <span class="font-bold">{{ event.text.substring(0, 50) }}...</span>
                                </div>
                                <div v-else>
                                    <span  class="font-bold">{{ event.text }}</span>
                                </div>
                                <span class="text-sm">{{ event.debut.split(' ')[1] }} - {{ event.fin.split(' ')[1] }}</span>
                            </div>
                            <Button type="button" icon="pi pi-ellipsis-v" @click="toggle(event, $event)" aria-haspopup="true" aria-controls="overlay_menu" class="action-button"/>

                        </div>
                        <div class="event-body p-2 flex flex-col gap-2">
                            <div>
                                <div>{{ event.semestre }}</div>
                                <div><span class="font-bold">{{ event.groupe }}</span> | <span>{{ event.salle }}</span></div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
    <Menu ref="menu" id="overlay_menu" :model="itemsTest" :popup="true" />

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
    color: black;
    overflow: auto;
    scrollbar-width: thin;
}

.action-button {
    background: none;
    border: none;
    font-size: 1.2rem;
    color: black;

    &:focus {
        outline: none;
    }

    &:hover {
        background-color: transparent !important;
        border: none !important;
    }
}
</style>
