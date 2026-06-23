<script setup>
  import {formatDateCourt} from "@helpers/date.js";

const props = defineProps({
  ticket: {
    type: Object,
    required: true
  }
});
</script>

<template>
  <div class="border border-gray-200 rounded-lg p-5 shadow-md hover:shadow-lg transition-shadow mb-4">
    <div class="flex items-start justify-between gap-4 mb-3">
      <div class="flex-1">
        <div class="font-semibold text-xl">
          {{ ticket.subject }}
        </div>
      </div>

      <div class="flex items-center gap-6">
        <span class="text-sm text-gray-600 italic dark:text-gray-400">
  {{ ticket.helpdeskCategorie?.libelle || ticket.category }}
</span>
      </div>

      <div class="flex items-center">
        <span class="text-sm text-gray-600 italic dark:text-gray-400">{{formatDateCourt(ticket.created)}}</span>
      </div>

    </div>
    <div class="text-sm  mb-4 line-clamp-2">
      <p class="text-muted-color">{{ ticket.description }}</p>
    </div>
    </div>
</template>

<style scoped>

</style>