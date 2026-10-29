<script setup>
import { computed } from 'vue';
import { PermissionGuard } from '@components';
import IndicateursDashboard from './IndicateursDashboard.vue';

// Historique fictif : la date de référence permet un aperçu cohérent et reproductible.
const referenceDate = '2026-10-29';
const absences = [
  { id: 1, student: 'Camille Martin', group: 'BUT 1 · TP A', date: '2026-10-29', time: '08:00 – 10:00', teaching: 'Algorithmique', status: 'Justifiée' },
  { id: 2, student: 'Alex Dubois', group: 'BUT 1 · TP A', date: '2026-10-29', time: '08:00 – 10:00', teaching: 'Algorithmique', status: 'En attente' },
  { id: 3, student: 'Lou Bernard', group: 'BUT 2 · TD 1', date: '2026-10-29', time: '10:00 – 12:00', teaching: 'Développement web', status: 'Non justifiée' },
  { id: 4, student: 'Noah Petit', group: 'BUT 2 · TD 2', date: '2026-10-29', time: '14:00 – 16:00', teaching: 'Bases de données', status: 'En attente' },
  { id: 5, student: 'Jade Moreau', group: 'BUT 3 · TD 1', date: '2026-10-28', time: '09:00 – 11:00', teaching: 'Gestion de projet', status: 'Justifiée' },
  { id: 6, student: 'Adam Laurent', group: 'BUT 1 · TP B', date: '2026-10-27', time: '14:00 – 16:00', teaching: 'Architecture', status: 'Justifiée' },
  { id: 7, student: 'Léa Robert', group: 'BUT 3 · TD 2', date: '2026-10-26', time: '10:00 – 12:00', teaching: 'Anglais', status: 'Non justifiée' },
];
const metrics = computed(() => [
  { label: 'Absences signalées', value: absences.length, icon: 'pi pi-user-minus' },
  { label: 'Justifiées', value: absences.filter(item => item.status === 'Justifiée').length, icon: 'pi pi-check-circle' },
  { label: 'En attente', value: absences.filter(item => item.status === 'En attente').length, icon: 'pi pi-clock' },
  { label: 'Non justifiées', value: absences.filter(item => item.status === 'Non justifiée').length, icon: 'pi pi-exclamation-circle' },
]);
const today = computed(() => absences.filter(item => item.date === referenceDate));
const severity = status => ({ 'Justifiée': 'success', 'En attente': 'warn', 'Non justifiée': 'danger' })[status];
</script>

<template>
  <section class="card p-5! mb-0!" aria-labelledby="absence-dashboard">
    <div class="flex flex-wrap justify-between items-center gap-2 mb-4">
      <h2 id="absence-dashboard" class="m-0! text-lg! flex items-center gap-2"><i class="pi pi-user-minus text-primary" aria-hidden="true" />Suivi des absences</h2>
      <span class="text-sm text-muted-color">Semaine du 26 octobre · département</span>
    </div>
    <IndicateursDashboard :items="metrics" />
    <PermissionGuard permission="isAbsence">
      <div class="mt-6">
        <h3 class="mt-0! mb-3! text-base!">Absences de la journée <span class="font-normal text-muted-color">· jeudi 29 octobre</span></h3>
        <DataTable :value="today" data-key="id" striped-rows size="small" scrollable>
          <Column field="student" header="Étudiant" />
          <Column field="group" header="Groupe" />
          <Column field="time" header="Horaire" />
          <Column field="teaching" header="Enseignement" />
          <Column header="Justification"><template #body="{ data }"><Tag :value="data.status" :severity="severity(data.status)" /></template></Column>
          <template #empty>Aucune absence signalée pour cette journée.</template>
        </DataTable>
      </div>
    </PermissionGuard>
  </section>
</template>
