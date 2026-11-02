<script setup>
defineProps({
  assiduite: {
    type: Array,
    required: true,
  },
  totalAbsences: {
    type: Number,
    required: true,
  },
});
</script>

<template>
  <article class="card p-5! mb-0!">
    <div class="flex items-center justify-between gap-3 mb-4">
      <h3 class="m-0! text-lg! flex items-center gap-2"><i class="pi pi-user-minus text-primary" aria-hidden="true" />Suivi de l’assiduité</h3>
      <Tag :value="`${totalAbsences} absence${totalAbsences > 1 ? 's' : ''}`" severity="warn" />
    </div>
    <ul v-if="assiduite.length" class="list-none m-0! p-0! flex flex-col divide-y divide-surface-200 dark:divide-surface-700">
      <li v-for="enseignement in assiduite" :key="enseignement.id" class="py-3 flex flex-wrap items-center justify-between gap-3">
        <div>
          <p class="m-0! font-semibold">{{ enseignement.libelle }}</p>
          <p class="m-0! text-sm text-muted-color">{{ enseignement.code }}</p>
        </div>
        <Tag :value="`${enseignement.absences} absence${enseignement.absences > 1 ? 's' : ''}`" severity="warn" />
      </li>
    </ul>
    <p v-else class="m-0! text-sm text-muted-color">Aucune absence signalée pour vos enseignements.</p>
  </article>
</template>
