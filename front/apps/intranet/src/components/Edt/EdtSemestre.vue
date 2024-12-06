<script setup>
import {defineProps, watch, ref, onMounted} from 'vue';

const props = defineProps({
    data: Object
});

const days = ref(props.data.days);

watch(() => props.data.days, (newDays) => {
    days.value = newDays;
});

onMounted(() => {
    // Fetch data from API at localhost:8000/api/structure_semestres/
});
</script>

<template>
    <div class="calendar grid grid-cols-5 gap-4">
        <div class="bg-gray-200 bg-opacity-20 p-4 rounded-md flex flex-col gap-5" v-for="(day, index) in days" :key="index">
            <div class="day text-center uppercase font-bold flex flex-col">
                {{ day.dayName }} <span class="font-black">{{ day.dayNumber }}</span>
            </div>
            <div class="events">
                <!-- Events content -->
            </div>
        </div>
    </div>
</template>

<style scoped>
</style>
