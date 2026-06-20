<script setup>
import {computed, ref} from 'vue';

const props = defineProps({
    widget: {
        type: Object,
        required: true,
    },
    widgetsLength: {
        type: Number,
        required: true,
    },
    loading: {
        type: Boolean,
        default: false,
    },
    error: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['toggle-enabled', 'toggle-collapsed', 'refresh', 'resize', 'move-backward', 'move-forward']);

const sizes = ['small', 'medium', 'large'];
const sizeIndex = computed(() => sizes.indexOf(props.widget.size || 'medium'));
const canResize = computed(() => sizeIndex.value !== -1);

const rotateSize = () => {
    if (!canResize.value) {
        return;
    }
    const newSize = sizes[(sizeIndex.value + 1) % sizes.length];
    emit('resize', newSize);
};

const toggleEnabled = () => {
    emit('toggle-enabled', !props.widget.enabled);
};
</script>

<template>
    <div class="rounded-xl border border-surface-200 bg-surface-0 p-5">
        <div class="mb-3 flex items-center justify-between gap-2">
            <div class="m-0 text-md! font-bold"><i :class="widget.icon" class="mr-2 text-primary-500"></i>{{ widget.label }}</div>
            <div class="flex items-center gap-2">
                <Button v-if="widget.position != 0" icon="pi pi-arrow-left" text rounded v-tooltip.top="`Déplacer vers l'avant`" @click="emit('move-backward')"/>
                <Button v-if="widget.position != widgetsLength - 1" icon="pi pi-arrow-right" text rounded v-tooltip.top="`Déplacer vers l'arrière`" @click="emit('move-forward')"/>
                <Button icon="pi pi-arrows-h" text rounded v-tooltip.top="`${widget.size === 'large' ? 'Réduire' : 'Agrandir'}`" @click="rotateSize"/>
                <Button icon="pi pi-times" text rounded v-tooltip.top="`Retirer le widget`" @click="toggleEnabled"/>
            </div>
        </div>
        
        <div v-if="loading" class="text-color-secondary">Chargement...</div>
        <div v-else-if="error" class="text-red-500">Impossible de charger les données du widget.</div>
        <div v-else-if="!widget.enabled" class="text-color-secondary">Widget désactivé.</div>
        <div v-else-if="widget.collapsed" class="text-color-secondary">Widget réduit.</div>
        <slot v-else />
    </div>
</template>
