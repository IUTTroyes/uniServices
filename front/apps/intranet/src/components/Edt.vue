<script setup>
import { defineProps } from 'vue';
import { startOfWeek, addDays, format } from 'date-fns';
import { fr } from 'date-fns/locale';

const props = defineProps({
    data: Object
});

const startDate = startOfWeek(new Date(), { weekStartsOn: 1 }); // Commence la semaine le lundi
const days = Array.from({ length: 5 }, (_, i) => {
    const date = addDays(startDate, i);
    return {
        dayName: format(date, 'EEEE', { locale: fr }),
        dayNumber: format(date, 'dd/MM', { locale: fr })
    };
});
</script>

<template>
    <div class="calendar grid grid-cols-5 gap-4">
        <div class="bg-gray-50 p-4 rounded-md flex flex-col gap-5" v-for="(day, index) in days" :key="index">
            <div class="day text-center uppercase font-bold flex flex-col">
                {{ day.dayName }} <span class="font-black">{{ day.dayNumber }}</span>
            </div>
            <div class="events">

            </div>
        </div>
    </div>
</template>

<style scoped>
</style>
