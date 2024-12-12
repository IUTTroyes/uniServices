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
    {
        debut: '10/12/2024 08:00',
        fin: '10/12/2024 09:30',
        jour: 3,
        text: 'WS301D - Développer des parcours utilisateur au sein d\'un système d\'information',
        color: '#FBE4EE',
        colorFocus: '#fbc6e3',
        semestre: 'S3 Strat-UX Alt',
        groupe: 'CM',
        groupeColor: '#1f4ea6',
        salle: 'H018'
    },
    {
        debut: '10/12/2024 09:00',
        fin: '10/12/2024 11:00',
        jour: 3,
        text: 'WS301D - Développer des parcours utilisateur au sein d\'un système d\'information',
        color: '#FBE4EE',
        colorFocus: '#fbc6e3',
        semestre: 'S3 Strat-UX Alt',
        groupe: 'CM',
        groupeColor: '#1f4ea6',
        salle: 'H018'
    },
    {
        debut: '10/12/2024 09:30',
        fin: '10/12/2024 12:30',
        jour: 3,
        text: 'WR601 - Entrepreneuriat',
        color: '#FBE4EE',
        colorFocus: '#fbc6e3',
        semestre: 'S3 Strat-UX Alt',
        groupe: 'TD CD',
        groupeColor: '#2d7315',
        salle: 'H201'
    },
    {
        debut: '10/12/2024 14:00',
        fin: '10/12/2024 18:30',
        jour: 3,
        text: 'WR602D - Hébergement et Cybersécurité',
        color: '#E1E2FE',
        colorFocus: '#b1b4ff',
        semestre: 'S3 DWebDi Alt',
        groupe: 'TP E',
        groupeColor: '#bd6910',
        salle: 'H201'
    },
    {
        debut: '11/12/2024 08:00',
        fin: '11/12/2024 12:30',
        jour: 4,
        text: 'PTUT',
        color: '#FFEDD2',
        colorFocus: '#ffdeae',
        semestre: 'S1',
        groupe: 'TD EF',
        groupeColor: '#2d7315',
        salle: 'H201'
    },
    {
        debut: '11/12/2024 14:00',
        fin: '11/12/2024 17:00',
        jour: 4,
        text: 'PTUT',
        color: '#FFEDD2',
        colorFocus: '#ffdeae',
        semestre: 'S1',
        groupe: 'TD EF',
        groupeColor: '#2d7315',
        salle: 'H201'
    },
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

const assignColumns = (events) => {
    const eventsByDay = events.reduce((acc, event) => {
        if (!acc[event.jour]) {
            acc[event.jour] = [];
        }
        acc[event.jour].push(event);
        return acc;
    }, {});

    Object.values(eventsByDay).forEach(dayEvents => {
        const columns = [];
        dayEvents.forEach(event => {
            let placed = false;
            for (let i = 0; i < columns.length; i++) {
                if (!columns[i].some(e => (calculatePosition(e.debut.split(' ')[1]) < calculatePosition(event.fin.split(' ')[1]) && calculatePosition(e.fin.split(' ')[1]) > calculatePosition(event.debut.split(' ')[1])))) {
                    columns[i].push(event);
                    event.column = i;
                    placed = true;
                    break;
                }
            }
            if (!placed) {
                columns.push([event]);
                event.column = columns.length - 1;
            }
        });
        dayEvents.columnsCount = columns.length;
    });

    return eventsByDay;
};

const eventsByDay = assignColumns(events);

const hasOverlap = (event, dayEvents) => {
    return dayEvents.some(e => e !== event && calculatePosition(e.debut.split(' ')[1]) < calculatePosition(event.fin.split(' ')[1]) && calculatePosition(e.fin.split(' ')[1]) > calculatePosition(event.debut.split(' ')[1]));
};
</script>

<template>
    <div class="calendar grid grid-cols-5 gap-4" :style="{ height: calendarHeight + 'px' }">
        <div class="flex flex-col gap-5 light-surface-ground" v-for="(day, dayIndex) in days" :key="dayIndex">
            <div
                :class="['day text-center uppercase font-bold flex flex-col p-4 bg-opacity-20 surface-ground rounded-md', { 'bg-primary-light': currentDay === day.dayNumber, active: currentDay === day.dayNumber }]">
                {{ day.dayName }} <span class="font-black">{{ day.dayNumber }}</span>
            </div>
            <div class="relative h-full">
                <template v-for="(event, index) in events" :key="index">
                    <template v-if="event.jour === dayIndex">
                        <div class="event rounded-md absolute flex flex-col gap-1 opacity-90"
                             :style="{ top: calculatePosition(event.debut.split(' ')[1]) + 'px', height: calculateHeight(event.debut.split(' ')[1], event.fin.split(' ')[1]) - 5 + 'px', backgroundColor: event.color, width: hasOverlap(event, eventsByDay[dayIndex]) ? `calc(100% / ${eventsByDay[dayIndex].columnsCount})` : '100%', left: hasOverlap(event, eventsByDay[dayIndex]) ? `calc((100% / ${eventsByDay[dayIndex].columnsCount}) * ${event.column})` : '0' }">
                            <div class="event-header w-full p-2 rounded-t-md flex justify-between"
                                 :style="{ backgroundColor: event.colorFocus }">
                                <div class="flex flex-col">
                                    <div v-if="event.text.length > 50" v-tooltip.top="`${event.text}`">
                                        <span class="font-bold">{{ event.text.substring(0, 50) }}...</span>
                                    </div>
                                    <div v-else>
                                        <span class="font-bold">{{ event.text }}</span>
                                    </div>
                                    <span>{{ event.debut.split(' ')[1] }} - {{ event.fin.split(' ')[1] }}</span>
                                </div>
                                <Button type="button" icon="pi pi-ellipsis-v" @click="toggle(event, $event)" aria-haspopup="true" aria-controls="overlay_menu" class="action-button"/>
                            </div>
                            <div class="event-body p-2">
                                <div><span>{{ event.semestre }}</span></div>
                                <div><span class="font-bold">{{ event.groupe }}</span> | <span>{{ event.salle }}</span>
                                </div>
                            </div>
                        </div>
                    </template>
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
