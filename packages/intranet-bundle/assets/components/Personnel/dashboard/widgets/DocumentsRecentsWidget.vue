<script setup>
defineProps({
    data: {
        type: Object,
        default: () => ({items: []}),
    },
});
</script>

<template>
    <div class="flex flex-col gap-2.5">
        <div v-for="doc in data.items || []" :key="doc.titre" class="flex items-center justify-between rounded-lg border border-surface-200 px-3 py-2.5">
            <div class="flex items-center gap-2.5">
                <i :class="[doc.icon, 'text-red-400']"></i>
                <p class="m-0">{{ doc.titre }}</p>
            </div>
            <p class="m-0 text-sm text-color-secondary">{{ doc.date }}</p>
        </div>
    </div>
</template>
