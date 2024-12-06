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
</script>

<template>
    <div class="calendar grid grid-cols-5 gap-4">
        <div class="surface-ground rounded-md flex flex-col gap-5" v-for="(day, index) in days" :key="index">
            <div :class="['day text-center uppercase font-bold flex flex-col p-4 bg-opacity-20', { 'bg-primary': currentDay === day.dayNumber, active: currentDay === day.dayNumber }]">
                {{ day.dayName }} <span class="font-black">{{ day.dayNumber }}</span>
            </div>
            <div class="events">
                <!-- Events content -->
            </div>
        </div>
    </div>
</template>

<style scoped>
.surface-ground {
    background-color: var(--surface-ground);
}

.active {
    color: var(--primary-color);
    border-top-left-radius: 0.5rem;
    border-top-right-radius: 0.5rem;
}
</style>
