<script setup>
import {ref, markRaw, onMounted} from 'vue';
import EdtPersonnel from "@/components/Edt/EdtPersonnel.vue";
import EdtDepartement from "@/components/Edt/EdtDepartement.vue";
import EdtEtudiant from "../components/Edt/EdtEtudiant.vue";
import {useUsersStore} from "@stores/user_stores/userStore.js";

const store = useUsersStore();

const tabs = ref([]);

onMounted( () => {
  if (store.isPersonnel) {
    tabs.value =[
      { title: 'Personnel', component: markRaw(EdtPersonnel), value: '0' },
      { title: 'Département', component: markRaw(EdtDepartement), value: '1' },
    ];
  }
  if (store.isEtudiant) {
    tabs.value =[
      { title: 'Personnel', component: markRaw(EdtEtudiant), value: '0' },
    ];
  }
});

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
