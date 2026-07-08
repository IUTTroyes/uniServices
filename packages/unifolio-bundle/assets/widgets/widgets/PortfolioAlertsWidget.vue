<script setup>
import { computed, defineProps } from 'vue';

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
        <li v-for="(item, index) in items" :key="`${item}-${index}`" class="text-sm flex items-center gap-2">
            <i class="pi pi-exclamation-triangle text-orange-500" />
            {{ item }}
        </li>
    </ul>
    <div v-else class="text-sm text-color-secondary">Aucune alerte portfolio.</div>
</template>
