<script setup>
import { computed } from 'vue';
import IndicateursDashboard from './IndicateursDashboard.vue';

// Évaluations fictives du département. Une évaluation partielle reste à finaliser.
const evaluations = [
  { id: 1, label: 'Contrôle algorithmique', teaching: 'R1.01', group: 'BUT 1 · TP A', teacher: 'Sophie Laurent', entered: 18, expected: 26, due: '30 octobre' },
  { id: 2, label: 'Projet interface web', teaching: 'R3.01', group: 'BUT 2 · TD 1', teacher: 'Thomas Bernard', entered: 0, expected: 28, due: '3 novembre' },
  { id: 3, label: 'Travaux pratiques', teaching: 'R1.01', group: 'BUT 1 · TP B', teacher: 'Sophie Laurent', entered: 26, expected: 26, due: '23 octobre' },
  { id: 4, label: 'Présentation de projet', teaching: 'SAÉ 5.01', group: 'BUT 3 · TD 1', teacher: 'Julie Moreau', entered: 24, expected: 24, due: '26 octobre' },
  { id: 5, label: 'Expression orale', teaching: 'R3.12', group: 'BUT 2 · TD 2', teacher: 'Marc Petit', entered: 0, expected: 27, due: '5 novembre' },
];
const metrics = computed(() => [
  { label: 'Évaluations prévues', value: evaluations.length, icon: 'pi pi-list' },
  { label: 'Saisie terminée', value: evaluations.filter(item => item.entered === item.expected).length, icon: 'pi pi-check-circle' },
  { label: 'Saisie partielle', value: evaluations.filter(item => item.entered > 0 && item.entered < item.expected).length, icon: 'pi pi-pencil' },
  { label: 'Non saisies', value: evaluations.filter(item => item.entered === 0).length, icon: 'pi pi-clock' },
]);
const pending = computed(() => evaluations.filter(item => item.entered < item.expected));
</script>

<template>
  <section class="card p-5! mb-0!" aria-labelledby="grades-dashboard">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
      <h2 id="grades-dashboard" class="m-0! text-lg! flex items-center gap-2"><i class="pi pi-pencil text-primary" aria-hidden="true" />Suivi des notes</h2>
      <span class="text-sm text-muted-color">Évaluations du département · semestre en cours</span>
    </div>
    <IndicateursDashboard :items="metrics" />
    <h3 class="mt-6! mb-3! text-base!">Évaluations à finaliser</h3>
    <DataTable :value="pending" data-key="id" size="small" striped-rows scrollable>
      <Column header="Évaluation"><template #body="{ data }"><span class="font-semibold">{{ data.label }}</span><span class="block text-xs text-muted-color">{{ data.teaching }} · {{ data.group }}</span></template></Column>
      <Column field="teacher" header="Enseignant" />
      <Column header="Saisie"><template #body="{ data }"><span class="text-sm">{{ data.entered }} / {{ data.expected }} notes</span><ProgressBar :value="Math.round(data.entered / data.expected * 100)" :show-value="false" class="h-1.5! mt-1 min-w-24" /></template></Column>
      <Column field="due" header="Échéance" />
      <Column header="Statut"><template #body="{ data }"><Tag :value="data.entered ? 'À compléter' : 'À saisir'" :severity="data.entered ? 'info' : 'warn'" /></template></Column>
      <template #empty>Toutes les évaluations sont renseignées.</template>
    </DataTable>
  </section>
</template>
