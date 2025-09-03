<script setup>
import { ref, onMounted, watch } from 'vue';

import router from "@/router";

const items = [
  { label: 'Import Apogée', icon: 'pi pi-upload', route: '/administration/etudiant/ajout/apogee' },
  { label: 'Import manuel', icon: 'pi pi-pencil', route: '/administration/etudiant/ajout/manuel' },
];

const activeTab = ref(localStorage.getItem('activeTab') || items[0].route);

const navigateTo = (route) => {
  activeTab.value = route;
  router.push(route);
};

watch(activeTab, (newTab) => {
  localStorage.setItem('activeTab', newTab);
});

onMounted(async () => {
  router.push(activeTab.value);
});
</script>

<template>
  <div class="card">
    <h2 class="text-2xl font-bold mb-4">Ajouter des étudiants</h2>
    <Divider/>
    <Tabs :value="activeTab" scrollable>
      <TabList>
        <router-link
            v-for="tab in items"
            :key="tab.label"
            :to="tab.route"
            custom
        >
          <Tab :value="tab.route" @click="navigateTo(tab.route)">
            <div class="flex items-center gap-2 text-inherit uppercase">
              <i :class="tab.icon" />
              <span>{{ tab.label }}</span>
            </div>
          </Tab>
        </router-link>
      </TabList>
    </Tabs>
    <router-view class="mt-6"></router-view>
  </div>
</template>

<style scoped></style>
