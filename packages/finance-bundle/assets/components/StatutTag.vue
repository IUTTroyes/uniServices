<script setup>
import { computed } from 'vue';
import Tag from 'primevue/tag';
import { styleStatut } from '@/utils/finance';
import { getStatusBadgeInfo } from '@/utils/workflowConstants';

/** Statut d'une demande, dans la couleur de l'étape en cours (même repère que la frise et les cartes). */
const props = defineProps({
    statut: { type: String, required: true },
    /** Demande finalisée en entier (clôturée, ou payée et soldée) : tag vert. */
    finalisee: { type: Boolean, default: false },
});
const info = computed(() => getStatusBadgeInfo(props.statut));
</script>

<template>
    <Tag :value="info.label" :icon="info.icon" :class="styleStatut(statut, finalisee)" />
</template>
