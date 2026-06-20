<script setup>
import { useRouter } from 'vue-router';
const router = useRouter();

defineProps({
    data: {
        type: Object,
        default: () => ({items: []}),
    },
});
</script>

<template>
    <div class="flex flex-col gap-3">
        <p class="m-0 text-color-secondary text-sm">{{ data.todayLabel }}</p>
        <div v-for="item in data.items || []" :key="`${item.heure}-${item.cours}`" class="flex flex-wrap items-center gap-3 border-b border-surface-200 pb-3">
            <div class="w-16 font-bold text-violet-600">{{ item.heure }}</div>
            <span class="rounded-lg px-2.5 py-1 text-xs font-semibold"
                  :class="{
                    'bg-blue-100 text-blue-700': item.color === 'blue',
                    'bg-green-100 text-green-700': item.color === 'green',
                    'bg-violet-100 text-violet-700': item.color === 'purple'
                  }">
                {{ item.type }}
            </span>
            <div class="min-w-48 flex-1 font-bold">{{ item.cours }}</div>
            <div class="flex items-center gap-1.5 text-color-secondary">
                <i class="pi pi-map-marker"></i>
                {{ item.salle }}
            </div>
        </div>
        <div v-if="data.items?.length === 0" class="text-center py-4 text-muted-color">Aucun cours prévu aujourd'hui.</div>
        <Button label="Voir l'emploi du temps" class="w-full" severity="secondary" size="small" @click="router.push('/agenda')"/>
    </div>
</template>
