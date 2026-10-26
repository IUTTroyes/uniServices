<script setup>
import { computed, markRaw } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { HeaderComponent } from '@components';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import AffectationGroupeView from '@/views/Groupes/AffectationGroupeView.vue';
import StructureGroupeView from '@/views/Groupes/StructureGroupeView.vue';

const route = useRoute();
const router = useRouter();

const tabs = [
  { title: 'Affectation', value: 'affectation', component: markRaw(AffectationGroupeView) },
  { title: 'Structure', value: 'structure', component: markRaw(StructureGroupeView) },
];

const tabValues = tabs.map(tab => tab.value);

const activeTab = computed(() => {
  const tab = String(route.query.tab || 'affectation');
  return tabValues.includes(tab) ? tab : 'affectation';
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
      icon="pi pi-users"
      titre="Groupes"
      description="Gérez l'affectation et la structure des groupes"
    />

      <Tabs :value="activeTab" @update:value="onTabChange" class="bg-transparent!">
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
