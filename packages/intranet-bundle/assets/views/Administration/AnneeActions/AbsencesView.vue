<script setup>
import { computed, markRaw } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { HeaderComponent } from '@components';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import AbsenceListeView from '@/views/Absence/AbsenceListeView.vue';
import AbsenceJustificatifListeView from '@/views/Absence/AbsenceJustificatifListeView.vue';
import SuiviPointageView from '@/views/Absence/SuiviPointageView.vue';

const route = useRoute();
const router = useRouter();

const tabs = [
  { title: 'Liste des absences', value: 'liste', component: markRaw(AbsenceListeView) },
  { title: 'Justificatifs', value: 'justificatifs', component: markRaw(AbsenceJustificatifListeView) },
  { title: 'Pointages', value: 'pointages', component: markRaw(SuiviPointageView) },
];

const tabValues = tabs.map(tab => tab.value);

const activeTab = computed(() => {
  const tab = String(route.query.tab || 'liste');
  return tabValues.includes(tab) ? tab : 'liste';
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
      icon="pi pi-calendar"
      titre="Absences"
      description="Consultez les absences et justificatifs et réaliser le contrôle des pointages"
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
