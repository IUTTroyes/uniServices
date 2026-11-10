<script setup>
import { computed } from 'vue';
import { EmptyState } from '@components';

const props = defineProps({
    data: {
        type: Object,
        default: () => ({ items: [] }),
    },
});

/** Éléments : { titre, echeance, lien } (lien interne vers l'écran de réponse). */
const items = computed(() => props.data?.items || []);
</script>

<template>
    <ul v-if="items.length" class="list-none p-0 m-0 space-y-2">
        <li v-for="item in items" :key="item.lien" class="text-sm flex items-center justify-between gap-2">
            <router-link :to="item.lien" class="font-medium hover:underline">{{ item.titre }}</router-link>
            <span v-if="item.echeance" class="text-sm text-color-secondary shrink-0">avant le {{ item.echeance }}</span>
        </li>
    </ul>
    <EmptyState v-else compact title="Aucun questionnaire à remplir" icon="pi pi-inbox" />
</template>
