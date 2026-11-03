<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { ListSkeleton } from '@components';
import EnseignementAvancementDialog from './EnseignementAvancementDialog.vue';
import EnseignementEvaluationsDialog from './EnseignementEvaluationsDialog.vue';
import EnseignementAbsencesDialog from './EnseignementAbsencesDialog.vue';
import EnseignementPlanCoursDialog from './EnseignementPlanCoursDialog.vue';
import EnseignementPrevisionnelDialog from './EnseignementPrevisionnelDialog.vue';
import { getEnseignementsService } from '@requests';
import { useUsersStore } from '@stores';

const usersStore = useUsersStore();

const shortcuts = [
  { key: 'avancement', label: 'Avancement', icon: 'pi pi-map-marker' },
  { key: 'evaluations', label: 'Évaluations', icon: 'pi pi-pencil' },
  { key: 'absences', label: 'Absences', icon: 'pi pi-user-minus' },
  { key: 'plan', label: 'Plan de cours', icon: 'pi pi-book' },
  { key: 'previsionnel', label: 'Prévisionnel', icon: 'pi pi-calendar' },
];

const selectedTeaching = ref(null);
const dialogsVisible = ref({ evaluations: false, absences: false, plan: false, previsionnel: false });
const avancementVisible = ref(false);
const enseignementAvancement = ref(null);
const enseignements = ref([]);
const isLoadingEnseignements = ref(false);
const selectedDiplome = ref(null);
const selectedType = ref('tous');

const anneeUniv = localStorage.getItem('selectedAnneeUniv') ? JSON.parse(localStorage.getItem('selectedAnneeUniv')) : { id: null };
const anneeUniversitaire = anneeUniv?.id;

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

const getEnseignements = async () => {
  isLoadingEnseignements.value = true;
  try {
    const params = {
      personnel: usersStore.user.id,
      anneeUniversitaire: anneeUniv.id,
      departement: usersStore.departementDefaut?.id ?? null,
    };
    enseignements.value = await getEnseignementsService(params, '/dashboard');
  } catch (error) {
    console.error('Erreur lors de la récupération des enseignements :', error);
  } finally {
    isLoadingEnseignements.value = false;
  }
};

onMounted(async () => {
  await getEnseignements();
});

function openTeaching(teaching, shortcut) {
  if (shortcut === 'avancement') {
    enseignementAvancement.value = teaching;
    avancementVisible.value = true;
    return;
  }

  if (Object.hasOwn(dialogsVisible.value, shortcut)) {
    selectedTeaching.value = teaching;
    dialogsVisible.value[shortcut] = true;
  }
}
</script>

<template>
  <article class="card p-5! mb-0!">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
      <h3 class="m-0! text-lg!">Mes enseignements</h3>
      <span class="text-sm text-muted-color">{{ enseignements.length }} enseignements</span>
    </div>
    <ListSkeleton v-if="isLoadingEnseignements" :count="3" class="mb-4" />
    <Tabs v-else-if="diplomes.length" :value="selectedDiplome" scrollable @update:value="selectedDiplome = $event">
      <TabList aria-label="Diplômes de mes enseignements">
        <Tab v-for="diplome in diplomes" :key="diplome.id" :value="diplome.id">
          <span>{{ diplome.libelle }}</span>
          <span class="ml-2 text-xs text-muted-color">({{ diplome.enseignements.length }})</span>
        </Tab>
      </TabList>
      <TabPanels class="px-0! pb-0!">
        <TabPanel v-for="diplome in diplomes" :key="diplome.id" :value="diplome.id">
          <template v-if="diplome.id === selectedDiplome">
            <div v-if="typesDisponibles.length > 1" class="flex flex-wrap items-center gap-3 mb-4">
              <span id="types-enseignements" class="text-sm text-muted-color">Type d’enseignement</span>
              <SelectButton
                  :model-value="selectedType"
                  :options="filtresTypes"
                  option-label="label"
                  option-value="value"
                  :allow-empty="false"
                  aria-labelledby="types-enseignements"
                  size="small"
                  class="flex-wrap"
                  @update:modelValue="selectedType = $event"
              />
            </div>
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
              <article v-for="enseignement in enseignementsFiltres" :key="enseignement.id" class="p-4 rounded-xl border border-surface-200 dark:border-surface-700 flex flex-col gap-2">
                <div><span class="uppercase font-semibold text-xs text-muted-color">{{ enseignement.type }} - </span><span class="text-xs font-semibold text-primary">{{ enseignement.codeEnseignement }}</span></div>
                <h4 class="m-0! text-base!">{{ enseignement.libelle }}</h4>
                <div class="flex flex-wrap items-center gap-2 mt-auto">
                  <Button v-for="shortcut in shortcuts" :key="shortcut.key" :label="shortcut.label" :icon="shortcut.icon" severity="secondary" text size="small" :aria-label="`${shortcut.label} — ${enseignement.libelle}`" @click="openTeaching(enseignement, shortcut.key)" />
                </div>
              </article>
            </div>
          </template>
        </TabPanel>
      </TabPanels>
    </Tabs>
    <p v-else class="m-0! text-sm text-muted-color">Aucun enseignement disponible pour ce contexte.</p>

    <EnseignementEvaluationsDialog
        v-model:visible="dialogsVisible.evaluations"
        :enseignement="selectedTeaching"
        :annee-universitaire="anneeUniversitaire"
    />

    <EnseignementAbsencesDialog
        v-model:visible="dialogsVisible.absences"
        :enseignement="selectedTeaching"
        :annee-universitaire="anneeUniversitaire"
    />

    <EnseignementPlanCoursDialog
        v-model:visible="dialogsVisible.plan"
        :enseignement="selectedTeaching"
        :annee-universitaire="anneeUniversitaire"
    />

    <EnseignementPrevisionnelDialog
        v-model:visible="dialogsVisible.previsionnel"
        :enseignement="selectedTeaching"
        :annee-universitaire="anneeUniversitaire"
    />

    <EnseignementAvancementDialog
        v-model:visible="avancementVisible"
        :enseignement="enseignementAvancement"
        :annee-universitaire="anneeUniversitaire"
    />
  </article>
</template>
