<script setup>
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { HeaderComponent, PermissionGuard } from '@components';
import { hasPermission } from '@utils/permissions';
import PlanCoursListView from './PlanCours/Personnel/PlanCoursListView.vue';
import EnseignantDashboardSection from '../components/Dashboard/EnseignantDashboardSection.vue';

const route = useRoute();
const router = useRouter();
const peutVoirEnseignements = computed(() => hasPermission('isEnseignant'));
const onglet = computed({
  get: () => route.query.onglet === 'enseignements' && peutVoirEnseignements.value ? 'enseignements' : 'plans',
  set: value => router.replace({ query: { ...route.query, onglet: value } }),
});
</script>

<template>
  <div class="flex flex-col gap-6">
    <HeaderComponent icon="pi pi-book" titre="Pédagogie"
                     description="Retrouvez vos plans de cours, vos enseignements et leur avancement." :show-back="false" />
    <Tabs v-model:value="onglet">
      <TabList aria-label="Espaces pédagogiques">
        <Tab value="plans"><i class="pi pi-book mr-2" aria-hidden="true" />Plans de cours</Tab>
        <Tab v-if="peutVoirEnseignements" value="enseignements"><i class="pi pi-graduation-cap mr-2" aria-hidden="true" />Enseignements</Tab>
      </TabList>
      <TabPanels class="p-0! pt-6! bg-transparent!">
        <TabPanel value="plans">
          <PlanCoursListView v-if="onglet === 'plans'" integre />
        </TabPanel>
        <TabPanel v-if="peutVoirEnseignements" value="enseignements">
          <PermissionGuard permission="isEnseignant">
            <EnseignantDashboardSection v-if="onglet === 'enseignements'" :afficher-suivi="false" />
          </PermissionGuard>
        </TabPanel>
      </TabPanels>
    </Tabs>
  </div>
</template>
