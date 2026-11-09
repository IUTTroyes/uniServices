<script setup>
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { HeaderComponent } from '@components';
import PlanCoursListView from './PlanCours/Personnel/PlanCoursListView.vue';
import EnseignantDashboardSection from '../components/Dashboard/EnseignantDashboardSection.vue';
import AbsenceListeView from './Absence/AbsenceListeView.vue';

const route = useRoute();
const router = useRouter();

const tabs = computed(() => {
  return [
    { title: 'Plans de cours', value: 'plans', icon: 'pi pi-book' },
    { title: 'Enseignements', value: 'enseignements', icon: 'pi pi-graduation-cap' },
  ];
});

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
                     description="Retrouvez vos plans de cours, vos enseignements et leur avancement." :show-back="false" />
    <Tabs :value="activeTab" @update:value="onTabChange">
      <TabList aria-label="Espaces pédagogiques">
        <Tab v-for="tab in tabs" :key="tab.value" :value="tab.value">
          <i :class="`${tab.icon} mr-2`" aria-hidden="true" />{{ tab.title }}
        </Tab>
      </TabList>
      <TabPanels class="p-0! pt-6! bg-transparent!">
        <TabPanel v-for="tab in tabs" :key="tab.value" :value="tab.value">
          <PlanCoursListView v-if="tab.value === 'plans'" integre />
          <template v-else>
            <EnseignantDashboardSection :afficher-suivi="false" />
            <AbsenceListeView />
          </template>
        </TabPanel>
      </TabPanels>
    </Tabs>
  </div>
</template>
