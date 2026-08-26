<script setup>
import {formatDateCourt} from "@helpers/date.js";
defineProps({
  data: {
    type: Object,
    default: () => ({items: []}),
  },
});

function formatDate(dateStr) {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  return date.toLocaleDateString('fr-FR', {
    weekday: 'long',
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  });
}
</script>

<template>
  <div class="flex flex-row gap-4">
    <a
        v-for="actu in data.items"
        :key="actu.title"
        :href="actu.link || '#'"
        target="_blank"
        class="w-1/3 bg-surface-200/20 rounded-md hover:shadow-md transition-shadow duration-200 overflow-hidden"
    >
      <div class="relative overflow-hidden">
        <img
            :src="actu.image"
            alt=""
            class="w-full h-24 object-cover group-hover:scale-105 transition-transform duration-300"
        />
      </div>

      <div class="p-4 flex flex-col">
        <h3 class="text-lg! font-semibold text-gray-800 line-clamp-2 group-hover:text-orange-500 transition-colors mb-0!" v-tooltip.top="actu.title">
          {{ actu.title }}
        </h3>
        <div class="flex items-center gap-1.5 text-sm text-gray-400">
          <span class="capitalize">Publié le {{ formatDateCourt(actu.pubDate) }}</span>
        </div>
      </div>
    </a>
  </div>
</template>

<style scoped>
</style>
