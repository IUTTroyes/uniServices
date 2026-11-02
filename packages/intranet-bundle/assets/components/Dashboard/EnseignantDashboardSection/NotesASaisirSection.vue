<script setup>
const props = defineProps({
  reminders: {
    type: Array,
    required: true,
  },
  enseignements: {
    type: Array,
    required: true,
  },
});

const emit = defineEmits(['openTeaching']);

function openEvaluation(evaluation) {
  const teaching = props.enseignements.find(item => item.id === evaluation.teaching);
  if (!teaching) {
    return;
  }
  emit('openTeaching', teaching, 'notes');
}
</script>

<template>
  <article class="card p-5! mb-0!">
    <div class="flex items-center justify-between gap-3 mb-4">
      <h3 class="m-0! text-lg! flex items-center gap-2"><i class="pi pi-pencil text-primary" aria-hidden="true" />Notes à saisir</h3>
      <Tag :value="`${reminders.length} évaluations`" severity="warn" />
    </div>
    <ul class="list-none m-0! p-0! flex flex-col divide-y divide-surface-200 dark:divide-surface-700">
      <li v-for="evaluation in reminders" :key="evaluation.id" class="py-3 flex flex-wrap items-center justify-between gap-3">
        <div><p class="m-0! font-semibold">{{ evaluation.label }}</p><p class="m-0! text-sm text-muted-color">{{ evaluation.teaching }} · {{ evaluation.group }} · échéance {{ evaluation.due }}</p><p class="mt-1! mb-0! text-sm">{{ evaluation.entered }} / {{ evaluation.expected }} notes renseignées</p></div>
        <Button label="Consulter" icon="pi pi-arrow-right" size="small" severity="secondary" @click="openEvaluation(evaluation)" />
      </li>
    </ul>
  </article>
</template>
