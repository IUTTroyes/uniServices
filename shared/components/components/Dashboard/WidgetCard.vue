<script setup>
import { defineProps } from 'vue';

const props = defineProps({
    widget: {
        type: Object,
        required: true
    },
    data: {
        type: Object,
        required: true
    },
    first: {
        type: Boolean,
        default: false
    },
    last: {
        type: Boolean,
        default: false
    }
});

const SIZE_CLASSES = {
    small: 'col-span-12 lg:col-span-4',
    medium: 'col-span-12 lg:col-span-8',
    large: 'col-span-12',
};

const gridClass = (size) =>
SIZE_CLASSES[size] ?? SIZE_CLASSES.medium;

const emit = defineEmits(['move', 'rotate', 'toggle']);

const moveWidget = (direction) => {
    emit('move', props.widget, direction);
};

const rotateSize = () => {
    emit('rotate', props.widget);
};

const toggleWidget = () => {
    emit('toggle', props.widget);
};
</script>

<template>
    <article
    :key="widget.code"
    :class="`${gridClass(widget.size)} card m-0! lg:p-6! p-4!`"
    >
    <div class="mb-3 flex items-start justify-between gap-2">
        <div class="font-semibold text-xl"><i :class="`${widget.icon} mr-2 text-primary-500`"/>{{ widget.label }}</div>
        <div class="flex items-center gap-1">
            <Button v-if="!first" icon="pi pi-arrow-left" text rounded @click="moveWidget(-1)"/>
            <Button v-if="!last" icon="pi pi-arrow-right" text rounded @click="moveWidget(1)"/>
            <Button icon="pi pi-arrows-h" text rounded @click="rotateSize()"/>
            <Button icon="pi pi-times" text rounded @click="toggleWidget()"/>
        </div>
    </div>
    <div class="text-sm text-color-secondary mb-2">{{ widget.code }}</div>
    <div class="widget-data">{{ data[widget.code] || { message: 'Chargement...' } }}</div>
</article>
</template>