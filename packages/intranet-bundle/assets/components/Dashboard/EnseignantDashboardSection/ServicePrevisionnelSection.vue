<script setup>
import IndicateursDashboard from '../IndicateursDashboard.vue';

defineProps({
  metrics: {
    type: Array,
    required: true,
  },
  workload: {
    type: Object,
    required: true,
  },
});
</script>

<template>
  <article class="card p-5! mb-0!">
    <h3 class="mt-0! mb-4! text-lg!">Mon service prévisionnel</h3>
    <IndicateursDashboard :items="metrics" />
    <ProgressBar :value="workload.percentage" :show-value="false" class="mt-4 h-2!" :aria-label="`${workload.percentage} % du service réalisé`" />
    <p class="mt-2! mb-0! text-sm text-muted-color">Heures d’enseignement de démonstration, hors primes et équivalences.</p>
  </article>
</template>
