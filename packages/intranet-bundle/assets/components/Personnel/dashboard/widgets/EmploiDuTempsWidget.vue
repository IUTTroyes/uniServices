<script setup>
import { useRouter } from 'vue-router';
import {colorNameToRgb, adjustColor} from "@helpers";

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
            <div class="max-w-40 font-bold text-violet-600">{{ item.heure }}</div>
            <span class="rounded-lg px-2.5 py-1 text-xs font-semibold"
                  :style="{backgroundColor: adjustColor(colorNameToRgb(item.color), 0.6, 0.1)}">
                {{ item.groupe }}
            </span>
            <div class="min-w-48 flex-1 font-bold">{{ item.cours }}</div>
            <div class="flex items-center gap-1.5 text-color-secondary">
                <i class="pi pi-map-marker"></i>
                {{ item.salle }}
            </div>
            <Button icon="pi pi-user" size="small" severity="primary" text rounded v-tooltip.top="`Faire l'appel`" />
        </div>
        <div v-if="data.items?.length === 0" class="flex justify-center text-center py-4 text-muted-color flex flex-col items-center min-h-58 max-h-58 overflow-y-hidden relative">
            <img src="@/assets/illu/palm.svg" alt="" class="w-86 h-86 absolute -bottom-24 -right-8 rotate-3">
            <div class="z-10 bg-primary-100/80 px-4 py-2 rounded-xl font-semibold text-black">Aucun cours prévu aujourd'hui.</div>
        </div>
        <Button label="Voir l'emploi du temps" class="w-full" severity="secondary" size="small" @click="router.push('/agenda')"/>
    </div>
</template>
