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
</script>

<template>

    <Carousel :value="days" :numVisible="1" :numScroll="1">
        <template #item="slotProps">
            <div class="bg-gray-200 bg-opacity-20 rounded-md">
                <div
                    :class="['day text-center uppercase font-bold flex flex-col p-4', { 'bg-primary': currentDay === slotProps.data.dayNumber, active: currentDay === slotProps.data.dayNumber }]">
                    {{ slotProps.data.dayName }} <span class="font-black">{{ slotProps.data.dayNumber }}</span>
                </div>
            </div>
        </template>
    </Carousel>
</template>

<style scoped>
.active {
    color: white;
    border-top-left-radius: 0.5rem;
    border-top-right-radius: 0.5rem;
}
</style>
