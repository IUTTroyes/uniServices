<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import ApcCompetence from "@/components/Pn/ApcCompetence.vue";
import { getReferentielCompetencesComplet } from '@requests';
import { SimpleSkeleton } from '@components';

interface Props {
  referentiel: {
    id?: number;
    libelle?: string;
    description?: string;
    anneePublication?: number;
  };
}

const props = defineProps<Props>();

const isLoadingReferentiel = ref(true);
const competences = ref<any[]>([]);
const activeCompetenceTab = ref<string>('');

const loadCompetences = async () => {
  if (!props.referentiel?.id) return;
  try {
    isLoadingReferentiel.value = true;
    competences.value = await getReferentielCompetencesComplet(props.referentiel.id);
    if (competences.value?.length > 0) {
      activeCompetenceTab.value = `c${competences.value[0].id}`;
    } else {
      activeCompetenceTab.value = '';
    }
  } catch (error) {
    console.error('Erreur chargement compétences du référentiel:', error);
    competences.value = [];
  } finally {
    isLoadingReferentiel.value = false;
  }
};

onMounted(async () => {
  await loadCompetences();
});

watch(
  () => props.referentiel?.id,
  async (newId, oldId) => {
    if (newId && newId !== oldId) {
      await loadCompetences();
    }
  }
);

const getCompetenceBadgeClass = (couleur?: string) => {
  switch (couleur) {
    case 'c1': return 'bg-blue-600 text-white';
    case 'c2': return 'bg-emerald-600 text-white';
    case 'c3': return 'bg-amber-600 text-white';
    case 'c4': return 'bg-rose-600 text-white';
    case 'c5': return 'bg-purple-600 text-white';
    case 'c6':
    default: return 'bg-cyan-600 text-white';
  }
};
</script>

<template>
  <div class="mt-4">
    <Tabs value="1">
      <TabList class="border-b border-surface-200 dark:border-surface-800">
        <Tab value="1">
          <i class="pi pi-list mr-2"></i>
          <span>Référentiel complet</span>
        </Tab>
        <Tab value="2">
          <i class="pi pi-sitemap mr-2"></i>
          <span>Référentiel par parcours</span>
        </Tab>
      </TabList>

      <TabPanels class="pt-4">
        <!-- Onglet 1 : Référentiel complet -->
        <TabPanel value="1">
          <div v-if="isLoadingReferentiel" class="space-y-4 py-4">
            <SimpleSkeleton class="w-1/3 h-8" />
            <div class="grid grid-cols-2 gap-4">
              <SimpleSkeleton class="h-40" />
              <SimpleSkeleton class="h-40" />
            </div>
            <SimpleSkeleton class="h-60" />
          </div>

          <div v-else-if="competences.length > 0">
            <!-- Onglets des compétences -->
            <Tabs :value="activeCompetenceTab || `c${competences[0].id}`" scrollable>
              <TabList class="bg-surface-50 dark:bg-surface-800/60 p-1.5 rounded-xl border border-surface-200 dark:border-surface-700">
                <Tab
                  v-for="competence in competences"
                  :key="competence.id"
                  :value="`c${competence.id}`"
                  class="flex items-center gap-2 px-3 py-2 rounded-lg transition-all"
                  @click="activeCompetenceTab = `c${competence.id}`"
                >
                  <span
                    class="w-2.5 h-2.5 rounded-full shrink-0"
                    :class="getCompetenceBadgeClass(competence.couleur)"
                  ></span>
                  <span class="font-medium text-sm">{{ competence.nomCourt || competence.libelle }}</span>
                </Tab>
              </TabList>

              <TabPanels>
                <TabPanel
                  v-for="competence in competences"
                  :key="competence.id"
                  :value="`c${competence.id}`"
                >
                  <ApcCompetence :competence="competence" />
                </TabPanel>
              </TabPanels>
            </Tabs>
          </div>

          <div
            v-else
            class="text-center py-12 px-4 border border-dashed rounded-2xl border-surface-300 dark:border-surface-700 bg-surface-50/50 dark:bg-surface-900/50"
          >
            <i class="pi pi-info-circle text-4xl text-muted-color mb-3"></i>
            <h4 class="font-semibold text-lg text-surface-800 dark:text-surface-100">
              Aucune compétence trouvée
            </h4>
            <p class="text-sm text-muted-color mt-1 max-w-md mx-auto">
              Ce référentiel ne comporte pas encore de compétences enregistrées. Vous pouvez lancer une synchronisation depuis ORéOF si ce référentiel est disponible.
            </p>
          </div>
        </TabPanel>

        <!-- Onglet 2 : Par parcours -->
        <TabPanel value="2">
          <div class="py-8 text-center border border-dashed rounded-2xl border-surface-300 dark:border-surface-700 bg-surface-50/50 dark:bg-surface-900/50">
            <i class="pi pi-sitemap text-4xl text-muted-color mb-3"></i>
            <h4 class="font-semibold text-base text-surface-800 dark:text-surface-100">
              Déclinaison par parcours
            </h4>
            <p class="text-sm text-muted-color mt-1">
              La visualisation par parcours est en cours de structuration.
            </p>
          </div>
        </TabPanel>
      </TabPanels>
    </Tabs>
  </div>
</template>

<style scoped>
</style>
