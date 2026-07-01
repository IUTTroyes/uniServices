<script setup>
import { computed, defineProps } from 'vue';

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

const emit = defineEmits(['move', 'updateSpan', 'toggle']);

const gridStyle = computed(() => ({
    gridColumn: `span ${props.widget.colSpan || 1}`,
    gridRow: `span ${props.widget.rowSpan || 1}`,
}));

const moveWidget = (direction) => {
    emit('move', props.widget, direction);
};

const increaseColSpan = () => {
    emit('updateSpan', props.widget, 'colSpan', (props.widget.colSpan || 1) + 1);
};

const decreaseColSpan = () => {
    const newColSpan = Math.max(1, (props.widget.colSpan || 1) - 1);
    emit('updateSpan', props.widget, 'colSpan', newColSpan);
};

const increaseRowSpan = () => {
    emit('updateSpan', props.widget, 'rowSpan', (props.widget.rowSpan || 1) + 1);
};

const decreaseRowSpan = () => {
    const newRowSpan = Math.max(1, (props.widget.rowSpan || 1) - 1);
    emit('updateSpan', props.widget, 'rowSpan', newRowSpan);
};

const toggleWidget = () => {
    emit('toggle', props.widget);
};
</script>

<template>
    <article
    :key="widget.code"
    :style="gridStyle"
    class="card m-0! lg:p-6! p-4!"
    >
    <div class="mb-3 flex items-start justify-between gap-2">
        <div class="font-semibold text-xl"><i :class="`${widget.icon} mr-2 text-primary-500`"/>{{ widget.label }}</div>
        <div class="flex items-center gap-1">
            <Button v-if="!first" icon="pi pi-arrow-left" text rounded @click="moveWidget(-1)"/>
            <Button v-if="!last" icon="pi pi-arrow-right" text rounded @click="moveWidget(1)"/>
            <Button icon="pi pi-plus" text rounded title="Augmenter colonnes" @click="increaseColSpan"/>
            <Button icon="pi pi-minus" text rounded title="Diminuer colonnes" @click="decreaseColSpan"/>
            <Button icon="pi pi-chevron-down" text rounded title="Augmenter lignes" @click="increaseRowSpan"/>
            <Button icon="pi pi-chevron-up" text rounded title="Diminuer lignes" @click="decreaseRowSpan"/>
            <Button icon="pi pi-times" text rounded @click="toggleWidget()"/>
        </div>
    </div>
    <div class="text-sm text-color-secondary mb-2">{{ widget.code }}</div>
    <div class="widget-data">{{ data[widget.code] || { message: 'Chargement...' } }}</div>
</article>
</template>