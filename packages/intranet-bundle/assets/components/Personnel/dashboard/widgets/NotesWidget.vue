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
        <div v-for="doc in data.items || []" :key="doc.titre" class="flex flex-col hover:bg-surface-100/60 cursor-pointer px-4">
            <div class="font-bold">{{ doc.semestre }}</div>
            <div>{{ doc.titre }}</div>
            <ProgressBar :value="doc.completion"/>
            <div class="text-muted-color text-sm flex justify-end">Date limite : {{ doc.date }}</div>
        </div>
        <div v-if="data.items?.length === 0" class="text-center py-4 text-muted-color">Aucune note à saisir.</div>
        <Button label="Voir toutes les notes" class="w-full" severity="secondary" size="small" />
    </div>
</template>
