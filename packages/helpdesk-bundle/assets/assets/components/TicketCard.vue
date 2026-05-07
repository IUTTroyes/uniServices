<script setup>
const props = defineProps({
  ticket: {
    type: Object,
    required: true
  }
});
const getStatusClasses = (status) => {
  const map = {
    'Nouveau': 'bg-blue-50 text-blue-700 border-blue-200',
    'En cours': 'bg-orange-50 text-orange-700 border-orange-200',
    'En attente': 'bg-yellow-50 text-yellow-700 border-yellow-200',
    'Urgent': 'bg-red-50 text-red-700 border-red-200',
    'Traité': 'bg-green-50 text-green-700 border-green-200',
  };
  return map[status] || 'bg-gray-50 text-gray-700 border-gray-200';
};
</script>

<template>
  <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-md hover:shadow-lg transition-shadow mb-4">
    <div class="flex items-start justify-between gap-4 mb-3">
      <div class="flex-1">
        <h3 class="text-lg font-bold text-gray-900 leading-tight">
          {{ ticket.subject }}
        </h3>
      </div>

      <div class="flex items-center gap-6">
        <span class="text-sm text-gray-600 italic">
          {{ ticket.category }}
        </span>

        <span
            class="px-3 py-1 rounded border text-sm font-medium"
            :class="getStatusClasses(ticket.statut)"
        >
          {{ ticket.statut }}
        </span>
      </div>
    </div>

    <div class="text-gray-700 text-sm leading-relaxed mb-4 line-clamp-2">
      {{ ticket.desc }}
    </div>

    <div v-if="ticket.attachment" class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-50 border border-blue-100 rounded text-sm text-blue-800">
      <i class="pi pi-file text-xs"></i>
      <span>{{ ticket.attachment }}</span>
    </div>
  </div>
</template>

<style scoped>

</style>