<script setup>
import { computed } from 'vue';
import { EmptyState } from '@components';

const props = defineProps({
    data: {
        type: Object,
        default: () => ({ items: [] }),
    },
});

const items = computed(() => props.data?.items || []);
</script>

<template>
    <ul v-if="items.length" class="list-none p-0 m-0 space-y-2">
        <li v-for="(item, index) in items" :key="`${item}-${index}`" class="text-sm">
            {{ item }}
        </li>
    </ul>
    <EmptyState v-else compact title="Aucune réponse récente" icon="pi pi-history" />
</template>
