<script setup>
import { computed, ref, onMounted, watch } from 'vue';
import IndicateursDashboard from './IndicateursDashboard.vue';
import EnseignementAvancementDialog from '../EnseignementAvancementDialog.vue';
import MesEnseignementsSection from './EnseignantDashboardSection/MesEnseignementsSection.vue';
import NotesASaisirSection from './EnseignantDashboardSection/NotesASaisirSection.vue';
import SuiviAssiduiteSection from './EnseignantDashboardSection/SuiviAssiduiteSection.vue';
import ServicePrevisionnelSection from './EnseignantDashboardSection/ServicePrevisionnelSection.vue';
import { getEnseignementsService } from '@requests';
import {useUsersStore} from '@stores';

const props = defineProps({ afficherSuivi: { type: Boolean, default: true } });

const enseignements = ref([]);
const isLoadingEnseignements = ref(true);
const usersStore = useUsersStore();
const anneeUniv = localStorage.getItem('selectedAnneeUniv') ? JSON.parse(localStorage.getItem('selectedAnneeUniv')) : { id: null };
const departement = usersStore.departementDefaut;
const selectedDiplome = ref(null);
const selectedType = ref('tous');

onMounted(async () => {
  try {
    await getEnseignements();
  } catch (error) {
    console.error('Erreur lors de la récupération des enseignements :', error);
  }
});

const getEnseignements = async () => {
  isLoadingEnseignements.value = true;
  try {
    const params = {
      personnel: usersStore.user.id,
      anneeUniversitaire: anneeUniv.id,
      departement: departement?.id ?? null,
    };
    enseignements.value = await getEnseignementsService(params, '/dashboard');
  } catch (error) {
    console.error('Erreur lors de la récupération des enseignements :', error);
  } finally {
    isLoadingEnseignements.value = false;
  }
};

const diplomes = computed(() => {
  const diplomesMap = new Map();
  enseignements.value.forEach(enseignement => {
    if (!enseignement?.diplome?.id) {
      return;
    }

    if (!diplomesMap.has(enseignement.diplome.id)) {
      diplomesMap.set(enseignement.diplome.id, { ...enseignement.diplome, enseignements: [] });
    }
    diplomesMap.get(enseignement.diplome.id).enseignements.push(enseignement);
  });

  return Array.from(diplomesMap.values());
});

watch(diplomes, (newDiplomes) => {
  if (!newDiplomes.length) {
    selectedDiplome.value = null;
    return;
  }

  if (!newDiplomes.some(diplome => diplome.id === selectedDiplome.value)) {
    selectedDiplome.value = newDiplomes[0].id;
  }
}, { immediate: true });

watch(selectedDiplome, () => {
  selectedType.value = 'tous';
});

const enseignementsDiplomeSelectionne = computed(() => {
  const diplome = diplomes.value.find(item => item.id === selectedDiplome.value);
  return diplome?.enseignements ?? [];
});

const typesDisponibles = computed(() => {
  const types = new Set(
      enseignementsDiplomeSelectionne.value
          .map(enseignement => enseignement?.type)
          .filter(Boolean)
  );
  return Array.from(types);
});

const filtresTypes = computed(() => [
  { label: 'Tous', value: 'tous' },
  ...typesDisponibles.value.map(type => ({ label: type, value: type }))
]);

const enseignementsFiltres = computed(() => {
  if (selectedType.value === 'tous') {
    return enseignementsDiplomeSelectionne.value;
  }

  return enseignementsDiplomeSelectionne.value.filter(enseignement => enseignement?.type === selectedType.value);
});

const mesEnseignementsModel = computed(() => ({
  enseignements: enseignements.value,
  isLoadingEnseignements: isLoadingEnseignements.value,
  diplomes: diplomes.value,
  selectedDiplome: selectedDiplome.value,
  typesDisponibles: typesDisponibles.value,
  filtresTypes: filtresTypes.value,
  selectedType: selectedType.value,
  enseignementsFiltres: enseignementsFiltres.value,
  shortcuts,
}));

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
const shortcuts = [
  { key: 'avancement', label: 'Avancement', icon: 'pi pi-map-marker' },
  { key: 'evaluations', label: 'Évaluations', icon: 'pi pi-pencil' },
  { key: 'absences', label: 'Absences', icon: 'pi pi-user-minus' },
  { key: 'plan', label: 'Plan de cours', icon: 'pi pi-book' },
  { key: 'previsionnel', label: 'Prévisionnel', icon: 'pi pi-calendar' },
];
const avancementVisible = ref(false);
const enseignementAvancement = ref(null);
const selectedTeaching = ref(null);
const selectedShortcut = ref(null);
const dialogVisible = ref(false);
function openTeaching(teaching, shortcut) {
  if (shortcut === 'avancement') {
    enseignementAvancement.value = teaching;
    avancementVisible.value = true;
    return;
  }

  selectedTeaching.value = teaching;
  selectedShortcut.value = shortcuts.find(item => item.key === shortcut);
  dialogVisible.value = true;
}
</script>

<template>
  <section :aria-labelledby="props.afficherSuivi ? 'teacher-dashboard' : undefined" class="flex flex-col gap-4">
    <h2 v-if="props.afficherSuivi" id="teacher-dashboard" class="m-0! text-xl! flex items-center gap-2"><i class="pi pi-user text-primary" aria-hidden="true" />Mon activité d’enseignant</h2>
    <MesEnseignementsSection
        :model="mesEnseignementsModel"
        @update:selected-diplome="selectedDiplome = $event"
        @update:selected-type="selectedType = $event"
        @open-teaching="openTeaching"
    />

    <div v-if="props.afficherSuivi" class="grid grid-cols-1 xl:grid-cols-2 gap-4">
      <NotesASaisirSection :reminders="reminders" :enseignements="enseignements" @open-teaching="openTeaching" />
      <SuiviAssiduiteSection :assiduite="assiduite" :total-absences="totalAbsences" />
    </div>
    <ServicePrevisionnelSection v-if="props.afficherSuivi" :metrics="metrics" :workload="workload" />

    <EnseignementAvancementDialog v-model:visible="avancementVisible" :enseignement="enseignementAvancement" :annee-universitaire="anneeUniv?.id" />

    <Dialog v-model:visible="dialogVisible" modal :header="`${selectedShortcut?.label ?? ''} · ${selectedTeaching?.id ?? ''}`" class="w-full max-w-2xl mx-4">
      <template v-if="selectedTeaching">
        <p class="mt-0! font-semibold">{{ selectedTeaching.label }} · {{ selectedTeaching.groups }}</p>
        <template v-if="selectedShortcut?.key === 'notes'">
          <div v-for="evaluation in evaluations.filter(item => item.teaching === selectedTeaching.id)" :key="evaluation.id" class="p-3 mb-2 rounded-lg bg-surface-50 dark:bg-surface-800 flex justify-between gap-3"><span>{{ evaluation.label }} · {{ evaluation.group }}</span><span>{{ evaluation.entered }} / {{ evaluation.expected }} notes</span></div>
          <p v-if="!evaluations.some(item => item.teaching === selectedTeaching.id)" class="text-muted-color">Aucune évaluation prévue pour cet enseignement.</p>
        </template>
        <template v-else-if="selectedShortcut?.key === 'absences'">
          <div v-for="absence in selectedTeaching.absences" :key="absence.student" class="p-3 mb-2 rounded-lg bg-surface-50 dark:bg-surface-800 flex flex-wrap justify-between gap-2"><span>{{ absence.student }} · {{ absence.date }}</span><Tag :value="absence.status" :severity="absence.status === 'Justifiée' ? 'success' : 'warn'" /></div>
          <p v-if="!selectedTeaching.absences.length" class="text-muted-color">Aucune absence signalée pour cet enseignement.</p>
        </template>
        <p v-else-if="selectedShortcut?.key === 'plan'">{{ selectedTeaching.plan }}</p>
        <IndicateursDashboard v-else :items="[{ label: 'Prévues', value: `${selectedTeaching.planned} h` }, { label: 'Réalisées', value: `${selectedTeaching.completed} h` }, { label: 'Restantes', value: `${selectedTeaching.planned - selectedTeaching.completed} h` }]" />
        <p class="mb-0! mt-4! text-xs text-muted-color">Aperçu de démonstration · aucune modification enregistrée.</p>
      </template>
    </Dialog>
  </section>
</template>
