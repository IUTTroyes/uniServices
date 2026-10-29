<script setup>
import { computed, ref } from 'vue';
import IndicateursDashboard from './IndicateursDashboard.vue';

// EdtEvent fictifs : edusignId null signifie que la séance n'est pas liée à Edusign.
const events = [
  { id: 1, date: '2026-10-26', time: '08:00 – 10:00', teaching: 'Algorithmique', teacher: 'Sophie Laurent', group: 'BUT 1 · TP A', edusignId: 'EDU-101' },
  { id: 2, date: '2026-10-27', time: '10:00 – 12:00', teaching: 'Développement web', teacher: 'Thomas Bernard', group: 'BUT 2 · TD 1', edusignId: null },
  { id: 3, date: '2026-10-28', time: '14:00 – 16:00', teaching: 'Bases de données', teacher: 'Julie Moreau', group: 'BUT 2 · TP B', edusignId: null },
  { id: 4, date: '2026-10-29', time: '08:00 – 10:00', teaching: 'Algorithmique', teacher: 'Sophie Laurent', group: 'BUT 1 · TP B', edusignId: 'EDU-104' },
  { id: 5, date: '2026-10-30', time: '10:00 – 12:00', teaching: 'Anglais', teacher: 'Marc Petit', group: 'BUT 3 · TD 1', edusignId: null },
  { id: 6, date: '2026-10-20', time: '08:00 – 10:00', teaching: 'Architecture', teacher: 'Thomas Bernard', group: 'BUT 1 · TD 1', edusignId: 'EDU-106' },
  { id: 7, date: '2026-10-22', time: '14:00 – 16:00', teaching: 'Gestion de projet', teacher: 'Julie Moreau', group: 'BUT 3 · TD 1', edusignId: null },
  { id: 8, date: '2026-11-02', time: '10:00 – 12:00', teaching: 'Développement web', teacher: 'Thomas Bernard', group: 'BUT 2 · TD 1', edusignId: null },
  { id: 9, date: '2026-11-03', time: '14:00 – 16:00', teaching: 'Algorithmique', teacher: 'Sophie Laurent', group: 'BUT 1 · TP A', edusignId: 'EDU-109' },
];
function mondayOf(date) {
  const day = new Date(date);
  day.setHours(12, 0, 0, 0);
  day.setDate(day.getDate() - (day.getDay() + 6) % 7);
  return day;
}
function localDateKey(date) {
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}
const weekStart = ref(mondayOf(new Date()));
const weekEnd = computed(() => { const date = new Date(weekStart.value); date.setDate(date.getDate() + 6); return date; });
const formatDate = value => new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'long' }).format(value);
const weekLabel = computed(() => `${formatDate(weekStart.value)} – ${formatDate(weekEnd.value)} ${weekEnd.value.getFullYear()}`);
const weekEvents = computed(() => events.filter(event => event.date >= localDateKey(weekStart.value) && event.date <= localDateKey(weekEnd.value)));
const missing = computed(() => weekEvents.value.filter(event => !event.edusignId));
const metrics = computed(() => [
  { label: 'Séances de la semaine', value: weekEvents.value.length, icon: 'pi pi-calendar' },
  { label: 'Liées à Edusign', value: weekEvents.value.length - missing.value.length, icon: 'pi pi-link' },
  { label: 'Sans identifiant Edusign', value: missing.value.length, icon: 'pi pi-exclamation-circle' },
  { label: 'Couverture', value: weekEvents.value.length ? `${Math.round((weekEvents.value.length - missing.value.length) / weekEvents.value.length * 100)} %` : '—', icon: 'pi pi-chart-bar' },
]);
function moveWeek(offset) { const date = new Date(weekStart.value); date.setDate(date.getDate() + offset * 7); weekStart.value = date; }
</script>

<template>
  <section class="card p-5! mb-0!" aria-labelledby="edusign-dashboard">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
      <h2 id="edusign-dashboard" class="m-0! text-lg! flex items-center gap-2"><i class="pi pi-link text-primary" aria-hidden="true" />Synchronisation Edusign</h2>
      <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Choix de la semaine">
        <Button icon="pi pi-chevron-left" aria-label="Semaine précédente" severity="secondary" outlined size="small" @click="moveWeek(-1)" />
        <span class="text-sm min-w-48 text-center" aria-live="polite">{{ weekLabel }}</span>
        <Button icon="pi pi-chevron-right" aria-label="Semaine suivante" severity="secondary" outlined size="small" @click="moveWeek(1)" />
        <Button label="Cette semaine" severity="secondary" text size="small" @click="weekStart = mondayOf(new Date())" />
      </div>
    </div>
    <IndicateursDashboard :items="metrics" />
    <h3 class="mt-6! mb-3! text-base!">Séances sans identifiant Edusign</h3>
    <DataTable :value="missing" data-key="id" striped-rows size="small" scrollable>
      <Column field="date" header="Date"><template #body="{ data }">{{ formatDate(new Date(`${data.date}T12:00:00`)) }}</template></Column>
      <Column field="time" header="Horaire" />
      <Column field="teaching" header="Enseignement" />
      <Column field="teacher" header="Enseignant" />
      <Column field="group" header="Groupe" />
      <template #empty>{{ weekEvents.length ? 'Toutes les séances de cette semaine sont liées à Edusign.' : 'Aucune séance de démonstration pour cette semaine.' }}</template>
    </DataTable>
  </section>
</template>
