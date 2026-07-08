<script setup>
import { computed, defineProps } from 'vue';

const props = defineProps({
    data: {
        type: Object,
        default: () => ({}),
    },
});

const percentage = computed(() => {
    const validated = Number(props.data?.validated || 0);
    const target = Number(props.data?.target || 0);

    if (!target) {
        return 0;
    }

    return Math.min(100, Math.round((validated / target) * 100));
});
</script>

<template>
    <div class="space-y-2">
        <div class="flex items-center justify-between">
            <span class="text-sm text-color-secondary">Progression</span>
            <span class="text-sm font-semibold">{{ percentage }}%</span>
        </div>
        <ProgressBar :value="percentage" />
        <div class="text-xs text-color-secondary">{{ data.validated ?? 0 }} / {{ data.target ?? 0 }}</div>
    </div>
</template>
