<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { ErrorView } from '@components';

import { useToast } from 'primevue/usetoast';
const toast = useToast();

import { getDepartementAnneesService } from '@requests';
import router from "@/router";

const items = [
  { label: 'Import Apogée', icon: 'pi pi-list', route: '/administration/etudiant/ajout/apogee' },
  { label: 'Import manuel', icon: 'pi pi-user', route: '/administration/etudiant/ajout/manuel' },
];

const navigateTo = (route) => {
  router.push(route);
};

import { useSemestreStore, useUsersStore } from '@stores';
import { SimpleSkeleton } from '@components';
const usersStore = useUsersStore();
const semestreStore = useSemestreStore();

const departementId = ref(null);
const semestresList = ref([]);
const anneesList = ref([]);

const isLoadingSemestres = ref(false);
const isLoadingAnnees = ref(false);

const hasError = ref(false);

const selectedAnneeUniversitaire = JSON.parse(localStorage.getItem('selectedAnneeUniv'));

const getSemestres = async () => {
  isLoadingSemestres.value = true;
  try {
    await semestreStore.getSemestresByDepartement(departementId.value, true);
    semestresList.value = Object.entries(
        semestreStore.semestres.reduce((acc, semestre) => {
          const annee = semestre.annee.libelle;
          if (!acc[annee]) {
            acc[annee] = [];
          }
          acc[annee].push({ label: semestre.libelle, value: semestre });
          return acc;
        }, {})
    ).map(([label, items]) => ({ label, items }));
  } catch (error) {
    console.error('Erreur lors du chargement des semestres :', error);
    hasError.value = true;
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de charger les semestres. Nous faisons notre possible pour résoudre cette erreur au plus vite.',
      life: 5000,
    });
  } finally {
    isLoadingSemestres.value = false;
  }
};

const getAnnees = async () => {
  isLoadingAnnees.value = true;
  try {
    anneesList.value = await getDepartementAnneesService(departementId.value, true);
  } catch (error) {
    console.error('Erreur lors du chargement des années :', error);
    hasError.value = true;
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de charger les années. Nous faisons notre possible pour résoudre cette erreur au plus vite.',
      life: 5000,
    });
  } finally {
    isLoadingAnnees.value = false;
  }
};

onMounted(async () => {
  if (items.length > 0) {
    router.push(items[0].route);
  }
  departementId.value = usersStore.departementDefaut.id;
  await getSemestres();
  await getAnnees();
});
</script>

<template>
  <ErrorView v-if="hasError" />
  <div v-else class="card">
    <h2 class="text-2xl font-bold mb-4">Ajouter des étudiants</h2>
    <Divider/>
    <Tabs value="/administration/etudiant/ajout/apogee" scrollable>
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
