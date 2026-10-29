<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { HeaderComponent, Kpi, ErrorView, GlobalLoader } from '@components';
import { useUsersStore, useAnneeUnivStore } from '@stores';
import {
  getDepartementService,
  getDiplomesService,
  getAnneesService,
  getDepartementSemestresService,
  getPersonnelsService
} from '@requests';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import Badge from 'primevue/badge';

import ConfigurationDepartementTab from './ConfigurationDepartementTab.vue';
import ConfigurationDiplomeTab from './ConfigurationDiplomeTab.vue';
import ConfigurationAnneeTab from './ConfigurationAnneeTab.vue';
import ConfigurationSemestreTab from './ConfigurationSemestreTab.vue';

const userStore = useUsersStore();
const anneeUnivStore = useAnneeUnivStore();

const activeTab = ref('departement');
const isLoading = ref(true);
const hasError = ref(false);
const errorMessage = ref('');

const departement = ref(null);
const allDiplomes = ref([]);
const allAnnees = ref([]);
const allSemestres = ref([]);
const personnels = ref([]);

const currentDeptId = computed(() => userStore.departementDefaut?.id);

// Filtrer uniquement les éléments actifs
const activeDiplomes = computed(() => {
  return (allDiplomes.value || []).filter(d => d.actif !== false);
});

const activeAnnees = computed(() => {
  return (allAnnees.value || []).filter(a => a.actif === true);
});

const activeSemestres = computed(() => {
  return (allSemestres.value || []).filter(s => s.actif === true);
});

const loadConfigurationData = async () => {
  if (!currentDeptId.value) {
    isLoading.value = false;
    return;
  }

  isLoading.value = true;
  hasError.value = false;

  try {
    // 1. Récupérer le département
    const deptData = await getDepartementService(currentDeptId.value);
    departement.value = deptData;

    // 2. Années universitaires
    if (!anneeUnivStore.anneesUniv || anneeUnivStore.anneesUniv.length === 0) {
      await anneeUnivStore.getAllAnneesUniv();
    }

    // 3. Charger diplômes, années, semestres (uniquement actifs) et personnels en parallèle
    const [diplomesData, anneesData, semestresData, personnelsData] = await Promise.all([
      getDiplomesService({ departement: currentDeptId.value }),
      getAnneesService({ departement: currentDeptId.value, actif: true }),
      getDepartementSemestresService(currentDeptId.value, true),
      getPersonnelsService({ departement: currentDeptId.value, pagination: false }).catch(() => [])
    ]);

    allDiplomes.value = diplomesData || [];
    allAnnees.value = anneesData || [];
    allSemestres.value = semestresData || [];
    personnels.value = personnelsData || [];
  } catch (error) {
    console.error('Erreur chargement données configuration intranet:', error);
    hasError.value = true;
    errorMessage.value = error?.message || 'Erreur lors du chargement de la configuration.';
  } finally {
    isLoading.value = false;
  }
};

onMounted(async () => {
  await loadConfigurationData();
});

watch(currentDeptId, async (newDeptId) => {
  if (newDeptId) {
    await loadConfigurationData();
  }
});

const onDepartementUpdated = (updatedInfo) => {
  if (departement.value) {
    if (!departement.value.opt) departement.value.opt = {};
    departement.value.opt = { ...departement.value.opt, [updatedInfo.key]: updatedInfo.value };
  }
};

const onDiplomeUpdated = ({ diplomeId, key, value }) => {
  const d = allDiplomes.value.find(item => item.id === diplomeId);
  if (d) {
    if (key === 'anneeUniversitaire') {
      d.anneeUniversitaire = value;
    } else {
      if (!d.opt) d.opt = {};
      d.opt = { ...d.opt, [key]: value };
    }
  }
};

const onAnneeUpdated = ({ anneeId, key, value }) => {
  const a = allAnnees.value.find(item => item.id === anneeId);
  if (a) {
    if (!a.opt) a.opt = {};
    a.opt = { ...a.opt, [key]: value };
  }
};

const onSemestreUpdated = ({ semestreId, key, value }) => {
  const s = allSemestres.value.find(item => item.id === semestreId);
  if (s) {
    if (key === 'actif' || key === 'ppnActif') {
      s[key] = value;
    } else {
      if (!s.opt) s.opt = {};
      s.opt = { ...s.opt, [key]: value };
    }
  }
};
</script>

<template>
  <div class="space-y-6">
    <HeaderComponent
      icon="pi pi-sliders-h"
      color="violet"
      titre="Configuration de l'Intranet"
      :description="`Paramétrage du département ${departement?.libelle || ''}, des diplômes, années et semestres actifs`"
      :show-back="true"
      back-url="/intranet/administration"
    />

    <GlobalLoader v-if="isLoading" />

    <ErrorView
      v-else-if="hasError"
      :message="errorMessage"
      @retry="loadConfigurationData"
    />

    <div v-else class="space-y-6">
      <!-- Barre KPI utilisant le composant officiel @components/Kpi.vue -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <Kpi
          label="Département"
          :value="departement?.sigle || departement?.libelle || 'Actif'"
          icon="pi pi-building"
          color="blue"
          description="Département de rattachement"
          @click="activeTab = 'departement'"
        />

        <Kpi
          label="Diplômes actifs"
          :value="activeDiplomes.length"
          icon="pi pi-graduation-cap"
          color="violet"
          description="Formations ouvertes"
          @click="activeTab = 'diplome'"
        />

        <Kpi
          label="Années actives"
          :value="activeAnnees.length"
          icon="pi pi-calendar-plus"
          color="emerald"
          description="Promotions d'études actives"
          @click="activeTab = 'annee'"
        />

        <Kpi
          label="Semestres actifs"
          :value="activeSemestres.length"
          icon="pi pi-list"
          color="amber"
          description="Semestres ouverts aux saisies"
          @click="activeTab = 'semestre'"
        />
      </div>

      <!-- Navigation par onglets -->
      <Tabs v-model:value="activeTab">
        <TabList class="bg-white dark:bg-slate-900 rounded-2xl p-1.5 border border-slate-200 dark:border-slate-800 shadow-xs">
          <Tab value="departement" class="flex items-center gap-2 px-4 py-2.5 font-semibold text-sm">
            <i class="pi pi-building text-base" />
            <span>Département</span>
          </Tab>
          <Tab value="diplome" class="flex items-center gap-2 px-4 py-2.5 font-semibold text-sm">
            <i class="pi pi-graduation-cap text-base" />
            <span>Diplômes</span>
            <Badge v-if="activeDiplomes.length > 0" :value="activeDiplomes.length" severity="secondary" />
          </Tab>
          <Tab value="annee" class="flex items-center gap-2 px-4 py-2.5 font-semibold text-sm">
            <i class="pi pi-calendar-plus text-base" />
            <span>Années</span>
            <Badge v-if="activeAnnees.length > 0" :value="activeAnnees.length" severity="secondary" />
          </Tab>
          <Tab value="semestre" class="flex items-center gap-2 px-4 py-2.5 font-semibold text-sm">
            <i class="pi pi-list text-base" />
            <span>Semestres</span>
            <Badge v-if="activeSemestres.length > 0" :value="activeSemestres.length" severity="secondary" />
          </Tab>
        </TabList>

        <TabPanels class="pt-6 bg-transparent! p-0!">
          <TabPanel value="departement">
            <ConfigurationDepartementTab
              v-if="departement"
              :departement="departement"
              :personnels="personnels"
              @updated="onDepartementUpdated"
            />
          </TabPanel>

          <TabPanel value="diplome">
            <ConfigurationDiplomeTab
              :diplomes="activeDiplomes"
              :annees-univ="anneeUnivStore.anneesUniv || []"
              :personnels="personnels"
              @updated="onDiplomeUpdated"
            />
          </TabPanel>

          <TabPanel value="annee">
            <ConfigurationAnneeTab
              :annees="activeAnnees"
              @updated="onAnneeUpdated"
            />
          </TabPanel>

          <TabPanel value="semestre">
            <ConfigurationSemestreTab
              :semestres="activeSemestres"
              :personnels="personnels"
              @updated="onSemestreUpdated"
            />
          </TabPanel>
        </TabPanels>
      </Tabs>
    </div>
  </div>
</template>

<style scoped>
</style>
