<script setup>
import { computed, ref, onMounted } from 'vue';
import MesEnseignementsSection from './EnseignantDashboardSection/MesEnseignementsSection.vue';
import NotesASaisirSection from './EnseignantDashboardSection/NotesASaisirSection.vue';
import SuiviAssiduiteSection from './EnseignantDashboardSection/SuiviAssiduiteSection.vue';
import ServicePrevisionnelSection from './EnseignantDashboardSection/ServicePrevisionnelSection.vue';

const props = defineProps({ afficherSuivi: { type: Boolean, default: true } });

const enseignements = ref([]);

onMounted(async () => {
});

const evaluations = [
  { id: 1, teaching: 'R1.01', label: 'Contrôle algorithmique', group: 'TP A', entered: 18, expected: 26, due: '30 octobre', status: 'À compléter' },
  { id: 2, teaching: 'R3.01', label: 'Projet interface web', group: 'TD 1', entered: 0, expected: 28, due: '3 novembre', status: 'À saisir' },
  { id: 3, teaching: 'R1.01', label: 'Travaux pratiques', group: 'TP B', entered: 26, expected: 26, due: '23 octobre', status: 'Terminée' },
];
const reminders = computed(() => evaluations.filter(item => item.entered < item.expected));
const assiduite = computed(() => enseignements.value
    .map(enseignement => ({
      id: enseignement.id,
      code: enseignement.codeEnseignement,
      libelle: enseignement.libelle,
      absences: enseignement.absences?.length ?? 0,
    }))
    .filter(item => item.absences > 0)
);
const totalAbsences = computed(() => assiduite.value.reduce((sum, item) => sum + item.absences, 0));
const workload = computed(() => {
  const planned = enseignements.value.reduce((sum, item) => sum + (item.planned ?? 0), 0);
  const completed = enseignements.value.reduce((sum, item) => sum + (item.completed ?? 0), 0);

  if (!planned) {
    return { planned, completed, remaining: 0, percentage: 0 };
  }

  return { planned, completed, remaining: planned - completed, percentage: Math.round(completed / planned * 100) };
});
const metrics = computed(() => [
  { label: 'Heures prévues', value: `${workload.value.planned} h`, icon: 'pi pi-calendar' },
  { label: 'Heures réalisées', value: `${workload.value.completed} h`, icon: 'pi pi-check-circle' },
  { label: 'Reste à réaliser', value: `${workload.value.remaining} h`, icon: 'pi pi-clock' },
  { label: 'Service réalisé', value: `${workload.value.percentage} %`, icon: 'pi pi-chart-bar' },
]);
</script>

<template>
  <section :aria-labelledby="props.afficherSuivi ? 'teacher-dashboard' : undefined" class="flex flex-col gap-4">
    <h2 v-if="props.afficherSuivi" id="teacher-dashboard" class="m-0! text-xl! flex items-center gap-2"><i class="pi pi-user text-primary" aria-hidden="true" />Mon activité d’enseignant</h2>
    <MesEnseignementsSection />

    <div v-if="props.afficherSuivi" class="grid grid-cols-1 xl:grid-cols-2 gap-4">
      <NotesASaisirSection :reminders="reminders" :enseignements="enseignements" />
      <SuiviAssiduiteSection :assiduite="assiduite" :total-absences="totalAbsences" />
    </div>
    <ServicePrevisionnelSection v-if="props.afficherSuivi" :metrics="metrics" :workload="workload" />
  </section>
</template>
