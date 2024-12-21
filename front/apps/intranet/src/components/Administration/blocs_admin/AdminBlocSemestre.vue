<script setup>
import { onMounted, ref } from "vue";
import { getServiceDepartementSemestresActifs } from "common-requests";
import SimpleSkeleton from "@/components/Loader/SimpleSkeleton.vue";

const semestresFc = ref([]);
const semestresFi = ref([]);
const selectedSemestre = ref(null);
const isLoading = ref(true);
const errorMessage = ref('');

onMounted(async () => {
  try {
    const departementId = localStorage.getItem('departement');
    const semestres = await getServiceDepartementSemestresActifs(departementId);
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
      <div v-else class="flex gap-10 mt-6">
        <div class="w-1/2 flex gap-4">
          <ul class="w-1/2">
            <li class="font-bold text-lg">Formation Initiale</li>
            <li v-for="semestreFi in semestresFi" :key="semestreFi.id" @click="selectSemestre(semestreFi)" class="cursor-pointer w-full border-b p-1">
              <div class="hover:bg-surface-400 hover:bg-opacity-10 rounded-md w-full p-2" :class="{'bg-surface-400 bg-opacity-10': selectedSemestre && selectedSemestre.id === semestreFi.id}">{{ semestreFi.libelle }}</div>
            </li>
          </ul >
          <ul class="w-1/2">
            <li class="font-bold text-lg">Formation Continue</li>
            <li v-for="semestreFc in semestresFc" :key="semestreFc.id" @click="selectSemestre(semestreFc)" class="cursor-pointer w-full border-b p-1">
              <div class="hover:bg-surface-400 hover:bg-opacity-10 rounded-md w-full p-2" :class="{'bg-surface-400 bg-opacity-10': selectedSemestre && selectedSemestre.id === semestreFc.id}">{{ semestreFc.libelle }}</div>
            </li>
          </ul>
        </div>
        <div class="w-1/2" v-if="selectedSemestre">
          <h3 class="font-bold text-xl mb-4">Actions pour {{ selectedSemestre.libelle }}</h3>
          <PanelMenu :model="[
          { label: 'Étudiants', icon: 'pi pi-user', command: () => {} },
          { label: 'Groupes', icon: 'pi pi-users', command: () => {} },
          { label: 'Absences', icon: 'pi pi-calendar', command: () => {} },
          { label: 'Notes', icon: 'pi pi-book', command: () => {} },
          { label: 'Fin de semestre', icon: 'pi pi-check', command: () => {} },
        ]" multiple class="custom-panelmenu-header" />
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
