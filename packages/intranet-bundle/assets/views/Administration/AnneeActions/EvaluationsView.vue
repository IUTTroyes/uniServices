<script setup>
import { computed, markRaw } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { HeaderComponent } from '@components';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import SuiviEvaluationsView from '@/views/Evaluations/SuiviEvaluationsView.vue';
import StructureGroupeView from '@/views/Groupes/StructureGroupeView.vue';

const route = useRoute();
const router = useRouter();

const tabs = [
  { title: 'Évaluations', value: 'evaluations', component: markRaw(SuiviEvaluationsView) },
  { title: 'Rattrapages', value: 'rattrapages', component: markRaw(StructureGroupeView) },
  { title: 'MCCC', value: 'mccc', component: markRaw(StructureGroupeView) },
];

const tabValues = tabs.map(tab => tab.value);

const activeTab = computed(() => {
  const tab = String(route.query.tab || 'evaluations');
  return tabValues.includes(tab) ? tab : 'evaluations';
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
  <div class="flex flex-col gap-4">
    <HeaderComponent
        icon="pi pi-book"
        titre="Notes et évaluations"
        description="Retrouvez les évaluations, rattrapages et modalités de contrôle des connaissances"
    />

    <Tabs :value="activeTab" @update:value="onTabChange">
      <TabList class="bg-transparent!">
        <Tab v-for="tab in tabs" :key="tab.value" :value="tab.value">
          {{ tab.title }}
        </Tab>
      </TabList>
      <TabPanels class="bg-transparent!">
        <TabPanel v-for="tab in tabs" :key="tab.value" :value="tab.value">
          <component :is="tab.component" />
        </TabPanel>
      </TabPanels>
    </Tabs>
  </div>
</template>
