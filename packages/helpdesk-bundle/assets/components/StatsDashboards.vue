<script setup>

const props = defineProps({
  tickets: {
    type: Array,
    required: true
  }
});

</script>

<template>
  <div class="flex flex-col items-end gap-4 w-full">
    <div class="card rounded-xl p-5 bg-white dark:bg-zinc-800 border border-gray-100 dark:border-zinc-700">
      <div v-if="tickets" class="flex items-center gap-8 px-4 py-2">
        <div class="flex items-center gap-3">
          <span class="text-sm text-gray-600 dark:text-gray-400 font-bold">Tickets Totaux :</span>
          <span class="text-2xl font-bold text-purple-600 dark:text-violet-400">{{ tickets.length }}</span>
        </div>
        <div class="w-px h-6 bg-gray-200 dark:bg-zinc-600"></div>
        <div class="flex items-center gap-3">
          <span class="text-sm text-gray-600 dark:text-gray-400 font-bold">En cours :</span>
          <span class="text-2xl font-bold text-purple-600 dark:text-violet-400">
            {{ tickets.filter(t => t.statut === 'En cours').length }}
          </span>
        </div>
        <div class="w-px h-6 bg-gray-200 dark:bg-zinc-600"></div>
        <div class="flex items-center gap-3">
          <span class="text-sm text-gray-600 dark:text-gray-400 font-bold">Traités :</span>
          <span class="text-2xl font-bold text-purple-600 dark:text-violet-400">
            {{ tickets.filter(t => t.statut === 'Clôturé').length }}
          </span>
        </div>
      </div>
    </div>



  </div>
</template>

<style scoped>

</style>