<script setup>
import {ref, markRaw, onMounted} from 'vue';
import EdtPersonnel from "@/components/Edt/EdtPersonnel.vue";
import EdtDepartement from "@/components/Edt/EdtDepartement.vue";
import EdtEtudiant from "../components/Edt/EdtEtudiant.vue";
import EdtStatistiques from "../components/Edt/EdtStatistiques.vue";
import {useUsersStore} from "@stores";
import {HeaderComponent} from '@components';

const store = useUsersStore();

const tabs = ref([]);

onMounted( () => {
  if (store.isPersonnel) {
    tabs.value =[
      { title: 'Personnel', component: markRaw(EdtPersonnel), value: '0' },
      { title: 'Département', component: markRaw(EdtDepartement), value: '1' },
      { title: 'Statistiques', component: markRaw(EdtStatistiques), value: '2' },
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
  <HeaderComponent
      icon="pi pi-calendar"
      titre="Emploi du temps"
      description="Consultez votre emploi du temps, celui du département et les statistiques"
  />
  <div class="card">
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
