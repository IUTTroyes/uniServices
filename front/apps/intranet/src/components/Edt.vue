<script setup>
import { defineProps, watch, ref } from 'vue';

const props = defineProps({
    data: Object
});

const days = ref(props.data.days);

watch(() => props.data.days, (newDays) => {
    days.value = newDays;
});
</script>

<template>
    <div class="calendar grid grid-cols-5 gap-4">
        <div class="bg-gray-50 p-4 rounded-md flex flex-col gap-5" v-for="(day, index) in days" :key="index">
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
