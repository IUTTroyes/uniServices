<script setup>
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
        class="w-1/3 group block bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow duration-200 overflow-hidden"
    >
      <div class="relative overflow-hidden">
        <img
            :src="actu.image"
            alt=""
            class="w-full h-24 object-cover group-hover:scale-105 transition-transform duration-300"
        />
      </div>

      <div class="p-4 flex flex-col gap-2">
        <h3 class="text-lg! font-semibold text-gray-800 line-clamp-2 group-hover:text-orange-500 transition-colors" v-tooltip.top="actu.title">
          {{ actu.title }}
        </h3>
        <div class="flex items-center gap-1.5 text-sm text-gray-400">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <span class="capitalize">{{ formatDate(actu.pubDate) }}</span>
        </div>
      </div>
    </a>
  </div>
</template>

<style scoped>
</style>
