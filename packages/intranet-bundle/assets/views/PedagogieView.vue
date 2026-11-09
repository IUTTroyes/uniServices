<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { EmptyState, ErrorState, HeaderComponent } from '@components';
import { useAnneeStore, useAnneeUnivStore, useUsersStore } from '@stores';
import { getAnneesService, getSemestresService } from '@requests';
import ContexteSelection from '@/components/Structure/ContexteSelection.vue';
import PlanCoursListView from './PlanCours/Personnel/PlanCoursListView.vue';
import EnseignantDashboardSection from '../components/Dashboard/EnseignantDashboardSection.vue';
import AbsenceListeView from './Absence/AbsenceListeView.vue';

import SuiviEvaluationsView from './Evaluations/SuiviEvaluationsView.vue';

const route = useRoute();
const router = useRouter();

const anneeStore = useAnneeStore();
const anneeUnivStore = useAnneeUnivStore();
const usersStore = useUsersStore();
const annees = ref([]);
const isLoadingAnnees = ref(true);
const erreurAnnees = ref(false);
const departementId = computed(() => usersStore.departementDefaut?.id);
const anneeUniversitaireId = computed(() => anneeUnivStore.selectedAnneeUniv?.id ?? anneeUnivStore.anneeUniv?.id);
const annee = computed({
  get: () => annees.value.find(item => item.id === anneeStore.annee?.id) ?? annees.value[0],
  set: value => anneeStore.setSelectedAnnee(value),
});

watch([departementId, anneeUniversitaireId], async ([departement, anneeUniversitaire], _, onCleanup) => {
  let current = true;
  onCleanup(() => { current = false; });
  annees.value = [];
  erreurAnnees.value = false;
  isLoadingAnnees.value = Boolean(departement && anneeUniversitaire);
  if (!isLoadingAnnees.value) return;

  try {
    const result = await getAnneesService({ departement, anneeUniversitaire, actif: true }, '/liste', false);
    if (current) annees.value = Array.isArray(result) ? result : [];
  } catch {
    if (current) erreurAnnees.value = true;
  } finally {
    if (current) isLoadingAnnees.value = false;
  }
}, { immediate: true });

onMounted(() => {
  if (!anneeUniversitaireId.value) void anneeUnivStore.getCurrentAnneeUniv();
});

const tabs = computed(() => {
  return [
    { title: 'Plans de cours', value: 'plans', icon: 'pi pi-book' },
    { title: 'Enseignements', value: 'enseignements', icon: 'pi pi-graduation-cap' },
    { title: 'Absences', value: 'absences', icon: 'pi pi-calendar-times' },
    { title: 'Évaluations', value: 'evaluations', icon: 'pi pi-list-check' },
  ];
});

const tabValues = computed(() => tabs.value.map(tab => tab.value));

const activeTab = computed(() => {
  const tab = String(route.query.tab || 'plans');
  return tabValues.value.includes(tab) ? tab : 'plans';
});

const semestres = ref([]);
const semestreId = ref(null);
const isLoadingSemestres = ref(false);
const erreurSemestres = ref(false);
const semestre = computed(() => semestres.value.find(item => item.id === semestreId.value));
const anneeContexteId = computed(() => ['plans', 'enseignements'].includes(activeTab.value) ? annee.value?.id : undefined);

watch(anneeContexteId, async (id, _, onCleanup) => {
  let current = true;
  onCleanup(() => { current = false; });
  semestres.value = [];
  erreurSemestres.value = false;
  isLoadingSemestres.value = Boolean(id);
  if (!id) return;

  try {
    const result = await getSemestresService({ annee: id }, '/mini');
    if (!current) return;
    semestres.value = Array.isArray(result) ? result : [];
    if (!semestre.value) semestreId.value = semestres.value[0]?.id ?? null;
  } catch {
    if (current) erreurSemestres.value = true;
  } finally {
    if (current) isLoadingSemestres.value = false;
  }
}, { immediate: true });

const onTabChange = async (value) => {
  await router.replace({
    query: {
      ...route.query,
      tab: value,
    },
  });
};
</script>

<template>
  <div class="flex flex-col gap-6">
    <HeaderComponent icon="pi pi-book" titre="Pédagogie"
                     description="Retrouvez vos plans de cours, vos enseignements, les absences et les évaluations." :show-back="false" />
    <Tabs lazy :value="activeTab" @update:value="onTabChange">
      <TabList aria-label="Espaces pédagogiques">
        <Tab v-for="tab in tabs" :key="tab.value" :value="tab.value">
          <i :class="`${tab.icon} mr-2`" aria-hidden="true" />{{ tab.title }}
        </Tab>
      </TabList>
      <TabPanels class="p-0! pt-6! bg-transparent!">
        <TabPanel v-for="tab in tabs" :key="tab.value" :value="tab.value">
          <template v-if="tab.value === 'plans' || tab.value === 'enseignements'">
            <ContexteSelection titre="Semestre de travail" :valeur="semestre?.libelle">
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="flex flex-col gap-2">
                  <label for="pedagogie-annee" class="text-sm font-semibold text-muted-color">Année de formation</label>
                  <Select v-model="annee" input-id="pedagogie-annee" class="w-full sm:w-60"
                          option-label="libelle" :options="annees" :loading="isLoadingAnnees"
                          :disabled="isLoadingAnnees || !annees.length" placeholder="Choisir une année" />
                </div>
                <div class="flex flex-col gap-2">
                  <label for="pedagogie-semestre" class="text-sm font-semibold text-muted-color">Semestre</label>
                  <Select v-model="semestreId" input-id="pedagogie-semestre" class="w-full sm:w-60"
                          option-label="libelle" option-value="id" :options="semestres"
                          :loading="isLoadingSemestres" :disabled="isLoadingSemestres || !semestres.length"
                          placeholder="Choisir un semestre" />
                </div>
              </div>
            </ContexteSelection>
            <ErrorState v-if="erreurAnnees" compact :show-navigation="false" message="Impossible de charger les années de formation." />
            <EmptyState v-else-if="!isLoadingAnnees && !annee" compact title="Aucune année de formation disponible." />
            <template v-else-if="annee">
              <ErrorState v-if="erreurSemestres" compact :show-navigation="false" message="Impossible de charger les semestres." />
              <EmptyState v-else-if="!isLoadingSemestres && !semestre" compact title="Aucun semestre disponible." />
              <template v-else-if="semestre">
                <PlanCoursListView v-if="tab.value === 'plans'" :key="anneeUniversitaireId"
                                   :semestre-id="semestre.id" integre />
                <EnseignantDashboardSection v-else :key="'enseignements-' + anneeUniversitaireId" :annee-id="annee.id"
                                             :semestre-id="semestre.id" :afficher-suivi="false" />
              </template>
            </template>
          </template>
          <AbsenceListeView v-else-if="tab.value === 'absences'" integre />
          <SuiviEvaluationsView v-else-if="tab.value === 'evaluations'" integre />
        </TabPanel>
      </TabPanels>
    </Tabs>
  </div>
</template>
