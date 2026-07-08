<script setup>
import { defineProps } from 'vue';

defineProps({
    data: {
        type: Object,
        default: () => ({}),
    },
});
</script>

<template>
    <div class="grid grid-cols-2 gap-2">
        <div class="rounded border border-surface-200 dark:border-surface-700 p-2 text-center">
            <div class="text-xs text-color-secondary">Total</div>
            <div class="text-lg font-semibold">{{ data.total ?? 0 }}</div>
        </div>
        <div class="rounded border border-surface-200 dark:border-surface-700 p-2 text-center">
            <div class="text-xs text-color-secondary">Publiés</div>
            <div class="text-lg font-semibold">{{ data.published ?? 0 }}</div>
        </div>
        <div class="rounded border border-surface-200 dark:border-surface-700 p-2 text-center">
            <div class="text-xs text-color-secondary">Brouillons</div>
            <div class="text-lg font-semibold">{{ data.draft ?? 0 }}</div>
        </div>
        <div class="rounded border border-surface-200 dark:border-surface-700 p-2 text-center">
            <div class="text-xs text-color-secondary">Clôturés</div>
            <div class="text-lg font-semibold">{{ data.closed ?? 0 }}</div>
        </div>
    </div>
</template>
