<script setup>
import {defineProps, watch, ref} from 'vue';

const props = defineProps({
    data: Object
});

const days = ref(props.data.days);
const currentDay = ref(props.data.currentDay);

watch(() => props.data.days, (newDays) => {
    days.value = newDays;
    const currentDayExists = newDays.some(day => day.dayNumber === currentDay.value);
});

const semestres = [
    { name: 'Semestre 1', value: '1' },
    { name: 'Semestre 2', value: '2' },
    { name: 'Semestre 3', value: '3' },
    { name: 'Semestre 4', value: '4' },
    { name: 'Semestre 5', value: '5' },
    { name: 'Semestre 6', value: '6' }
];

const semaines = [
    { name: 'Semaine 1', value: '1' },
    { name: 'Semaine 2', value: '2' },
    { name: 'Semaine 3', value: '3' },
    { name: 'Semaine 4', value: '4' },
    { name: 'Semaine 5', value: '5' },
    { name: 'Semaine 6', value: '6' }
];

const groupes = [
    { name: 'A', value: '1' },
    { name: 'B', value: '2' },
    { name: 'C', value: '3' },
    { name: 'D', value: '4' },
    { name: 'E', value: '5' },
    { name: 'F', value: '6' }
];
</script>

<template>
    <div class="flex gap-4">
        <Select v-model="selectedCity" :options="semestres" optionLabel="name" placeholder="Choisir un semestre" class="w-full md:w-56" />
        <Select v-model="selectedCity" :options="semaines" optionLabel="name" placeholder="Choisir une semaine" class="w-full md:w-56" />
    </div>

    <Carousel :value="days" :numVisible="1" :numScroll="1">
        <template #item="slotProps">
            <div>
                <div :class="['day text-center uppercase font-bold flex flex-col p-4 rounded-md m-3', { 'bg-primary-light': currentDay === slotProps.data.dayNumber, active: currentDay === slotProps.data.dayNumber, 'surface-ground': currentDay !== slotProps.data.dayNumber }]">
                    {{ slotProps.data.dayName }} <span class="font-black">{{ slotProps.data.dayNumber }}</span>
                </div>
                <div class="flex justify-between gap-4 m-3">
                    <div v-for="(groupe, index) in groupes" class="text-center w-full p-4 surface-ground rounded-md flex flex-col font-black">
                        {{ groupe.name }}
                    </div>
                </div>
            </div>
        </template>
    </Carousel>
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
</style>
