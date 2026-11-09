<script setup>
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { hasPermission } from '@utils/permissions';
import { HeaderComponent } from '@components';
import PlanCoursListView from './PlanCours/Personnel/PlanCoursListView.vue';
import EnseignantDashboardSection from '../components/Dashboard/EnseignantDashboardSection.vue';
import AbsenceListeView from './Absence/AbsenceListeView.vue';

import SuiviEvaluationsView from './Evaluations/SuiviEvaluationsView.vue';

const route = useRoute();
const router = useRouter();

const tabs = computed(() => {
  return [
    { title: 'Plans de cours', value: 'plans', icon: 'pi pi-book' },
    { title: 'Enseignements', value: 'enseignements', icon: 'pi pi-graduation-cap' },
    { title: 'Absences', value: 'absences', icon: 'pi pi-calendar-times' },
    { title: 'Évaluations', value: 'evaluations', icon: 'pi pi-list-check' },
  ];
});

const canViewEvaluations = computed(() => hasPermission('canViewAdministration'));

const tabValues = computed(() => tabs.value.map(tab => tab.value));

const activeTab = computed(() => {
  const tab = String(route.query.tab || 'plans');
  return tabValues.value.includes(tab) ? tab : 'plans';
});

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
          <PlanCoursListView v-if="tab.value === 'plans'" integre />
          <EnseignantDashboardSection v-else-if="tab.value === 'enseignements'" :afficher-suivi="false" />
          <AbsenceListeView v-else-if="tab.value === 'absences'" integre />
          <template v-else-if="tab.value === 'evaluations'">
            <SuiviEvaluationsView v-if="canViewEvaluations" integre />
            <Message v-else severity="info">Vous n’avez pas accès au suivi des évaluations.</Message>
          </template>
        </TabPanel>
      </TabPanels>
    </Tabs>
  </div>
</template>
