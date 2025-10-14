<script setup>
import { ref, markRaw } from 'vue';
import EdtPerso from "@/components/Edt/EdtPerso.vue";
import EdtDepartement from "@/components/Edt/EdtDepartement.vue";

const tabs = ref([
  { title: 'Personnel', component: EdtPerso, value: '0' },
  { title: 'Département', component: EdtDepartement, value: '1' },
]);
</script>

<template>
  <div class="card">
    <div class="title">
      <h1 class="text-2xl font-bold">Emploi du temps</h1>
      <p class="text-muted-color">Consultez votre emploi du temps personnel ou celui de votre département.</p>
    </div>

    <Tabs value="0">
      <TabList>
        <Tab v-for="tab in tabs" :key="tab.value" :value="tab.value">
          {{ tab.title }}
        </Tab>
      </TabList>
      <TabPanels>
        <TabPanel v-for="tab in tabs" :key="tab.value" :value="tab.value">
          <component :is="tab.component" />
        </TabPanel>
      </TabPanels>
    </Tabs>

  </div>
</template>
