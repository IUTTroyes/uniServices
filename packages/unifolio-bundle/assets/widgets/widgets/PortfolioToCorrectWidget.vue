<script setup>
import { EmptyState } from '@components';
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
        <li v-for="(item, index) in items" :key="`${item}-${index}`" class="text-sm" :class="item.startsWith('•') ? 'pl-3 text-color-secondary' : 'font-medium'">
            {{ item }}
        </li>
    </ul>
    <EmptyState v-else compact title="Aucun élément à corriger." />
</template>
