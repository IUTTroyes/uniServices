<script setup>
import { onMounted, ref } from "vue";
import { getDepartementSemestresActifsService } from "@requests";
import SimpleSkeleton from "@components/loader/SimpleSkeleton.vue";

const semestresFc = ref([]);
const semestresFi = ref([]);
const selectedSemestre = ref(null);
const isLoading = ref(true);
const errorMessage = ref('');

onMounted(async () => {
  try {
    const departementId = localStorage.getItem('departement');
    const semestres = await getDepartementSemestresActifsService(departementId);
    semestresFc.value = semestres.semestresFc;
    semestresFi.value = semestres.semestresFi;
    if (semestresFi.value.length > 0) {
      selectedSemestre.value = semestresFi.value[0];
    }
  } catch (error) {
    errorMessage.value = 'Erreur lors de la récupération des semestres.';
  } finally {
    isLoading.value = false;
  }
});

const selectSemestre = (semestre) => {
  selectedSemestre.value = semestre;
};

const panelMenuItems = [
  { label: 'Étudiants', icon: 'pi pi-user', command: () => {}, items: [
      { label: 'Liste des étudiants', icon: 'pi pi-list', command: () => {} },
      { label: 'Ajouter des étudiants', icon: 'pi pi-plus-circle', command: () => {} },
    ] },
  { label: 'Groupes', icon: 'pi pi-users', command: () => {}, items: [
      { label: 'Composition des groupes', icon: 'pi pi-list',
        command: () => {} },
      { label: 'Structure des groupes', icon: 'pi pi-cog', command: () => {} },
    ] },
  { label: 'Absences', icon: 'pi pi-calendar', command: () => {}, items: [
      { label: 'Liste des absences', icon: 'pi pi-list',
        command: () => {} },
      { label: 'Liste des justificatifs', icon: 'pi pi-folder-open', command: () => {} },
      { label: 'Suivi des pointages de présence', icon: 'pi pi-eye', command: () => {} },
    ] },
  { label: 'Notes et Évaluations', icon: 'pi pi-book', command: () => {}, items: [
      { label: 'Liste des notes', icon: 'pi pi-list', command: () => {} },
      { label: 'Gestion des évaluations', icon: 'pi pi-cog', command: () => {} },
      { label: 'Demandes de rattrapages', icon: 'pi pi-history', command: () => {} },
      { label: 'Modalités du contrôle continu', icon: 'pi pi-map', command: () => {} },
    ] },
  { label: 'Fin de semestre', icon: 'pi pi-check', command: () => {}, items: [
      { label: 'Préparation de la sous-commission', icon: 'pi pi-calculator', command: () => {} },
      { label: 'Changement de semestre des étudiants', icon: 'pi pi-forward', command: () => {} },
    ] },
];
</script>

<template>
  <SimpleSkeleton v-if="isLoading" class="mt-4"/>
  <div v-else-if="errorMessage" class="error-message">{{ errorMessage }}</div>
  <div v-else class="flex justify-between gap-10">
    <Fieldset class="w-full">
      <template #legend>
        <div class="flex items-center pl-2">
          <i class="pi pi-briefcase bg-yellow-400 bg-opacity-20 rounded-full p-4 text-yellow-500"/>
          <div class="flex flex-col">
            <span class="font-bold px-2 capitalize">Semestres</span>
            <em class="text-muted-color px-2">Étudiants, absences, notes, fin de semestre</em>
          </div>
        </div>
      </template>
      <SimpleSkeleton v-if="isLoading" class="mt-4"/>
      <div v-else-if="errorMessage" class="error-message">{{ errorMessage }}</div>
      <div v-else class="flex gap-10 mt-4">
        <div class="w-1/2 flex gap-4">
          <ul class="w-1/2">
            <li class="font-bold text-lg">Formation Initiale</li>
            <li v-for="semestreFi in semestresFi" :key="semestreFi.id" @click="selectSemestre(semestreFi)" class="cursor-pointer w-full border-b p-1">
              <div class="hover:bg-primary-400 hover:bg-opacity-10 rounded-md w-full p-2" :class="{'bg-primary-400 bg-opacity-10': selectedSemestre && selectedSemestre.id === semestreFi.id}">{{ semestreFi.libelle }}</div>
            </li>
          </ul >
          <ul class="w-1/2">
            <li class="font-bold text-lg">Formation Continue</li>
            <li v-for="semestreFc in semestresFc" :key="semestreFc.id" @click="selectSemestre(semestreFc)" class="cursor-pointer w-full border-b p-1">
              <div class="hover:bg-primary-400 hover:bg-opacity-10 rounded-md w-full p-2" :class="{'bg-primary-400 bg-opacity-10': selectedSemestre && selectedSemestre.id === semestreFc.id}">{{ semestreFc.libelle }}</div>
            </li>
          </ul>
        </div>
        <div class="w-1/2 " v-if="selectedSemestre">
          <h3 class="font-bold text-xl mb-4">Actions pour {{ selectedSemestre.libelle }}</h3>
          <PanelMenu :model="panelMenuItems" multiple/>
        </div>
      </div>
    </Fieldset>
  </div>
</template>

<style scoped>
.error-message {
  color: red;
  text-align: center;
  font-size: 1.2em;
}
</style>
