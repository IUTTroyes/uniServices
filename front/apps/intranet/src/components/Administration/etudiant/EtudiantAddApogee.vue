<script setup>
import {onMounted, ref, watch} from 'vue';
import { getDepartementAnneesService, getAnneeSemestresService, getAllAnneesUniversitairesService } from "@requests";
import {ErrorView, ListSkeleton} from "@components";
import { useUsersStore, useSemestreStore } from "@stores";

const userStore = useUsersStore();
const annees = ref([]);
const selectedAnnee = ref(null);
const semestres = ref([]);
const selectedSemestre = ref(null);
const anneesUniv = ref([]);
const selectedAnneeUniv = ref(null);

const isLoadingAnnees = ref(true);
const isLoadingSemestres = ref(false);
const isLoadingAnneesUniv = ref(true);

const getAnneesUniv = async () => {
  try {
    isLoadingAnneesUniv.value = true;
    anneesUniv.value = await getAllAnneesUniversitairesService();
    if (anneesUniv.value.length > 0) {
      selectedAnneeUniv.value = anneesUniv.value[0];
    }
  } catch (error) {
    console.error('Erreur lors du chargement des années universitaires :', error);
  } finally {
    isLoadingAnneesUniv.value = false;
  }
};

const getAnnees = async () => {
  try {
    isLoadingAnnees.value = true;
    const departementId = userStore.departementDefaut.id;

    annees.value = await getDepartementAnneesService(departementId, true);
  } catch (error) {
    console.error('Erreur lors du chargement des années :', error);
  } finally {
    console.log(annees.value)
    isLoadingAnnees.value = false;
  }
};

const getSemestresSelectedAnnee = async () => {
  try {
    semestres.value = [];
    isLoadingSemestres.value = true;
    semestres.value = await getAnneeSemestresService(selectedAnnee.value.id);
  } catch (error) {
    console.error('Erreur lors du chargement des semestres :', error);
  } finally {
    isLoadingSemestres.value = false;
    console.log(semestres.value)
  }
};

watch(selectedAnnee, async (newValue) => {
  if (newValue) {
    await getSemestresSelectedAnnee()
  }
})

watch(selectedAnneeUniv, async (newValue) => {
  if (newValue) {
    console.log(newValue)
  }
})

onMounted(async() => {
  await getAnneesUniv();
  await getAnnees();
});

</script>

<template>
  <div class="flex flex-col gap-4">
      <em class="text-lg font-medium text-muted-color">
        Importer les étudiants depuis Apogée
      </em>

    <div class="text-lg font-medium border p-4 w-full text-center mx-auto rounded-md flex flex-col gap-2">
      <div class="font-medium text-lg">
        Sélectionner une année universitaire
      </div>
      <ListSkeleton v-if="isLoadingAnneesUniv" class="w-full"/>
      <SelectButton v-else :options="anneesUniv" v-model="selectedAnneeUniv" class="w-full justify-center" optionLabel="libelle" optionValue="id"/>
    </div>
    <div class="w-full flex gap-2">
      <div class="text-lg font-medium border p-4 w-1/2 text-center mx-auto rounded-md flex flex-col gap-2">
        <div class="font-medium text-lg">
          Sélectionner une année
        </div>
        <ListSkeleton v-if="isLoadingAnnees" class="w-full"/>
        <Button v-else :severity="selectedAnnee && selectedAnnee.id === annee.id ? 'primary' : 'secondary'" v-for="annee in annees" :key="annee.id" class="w-full" @click="selectedAnnee = annee">
          {{annee.libelle}}
        </Button>
      </div>
      <div class="text-lg font-medium border p-4 w-1/2 text-center mx-auto rounded-md flex flex-col gap-2">
        <div class="font-medium text-lg">
          Sélectionner un semestre
        </div>
        <ListSkeleton v-if="isLoadingSemestres" class="w-full"/>
        <Button v-else-if="semestres.length > 0" :severity="selectedSemestre && selectedSemestre.id === semestre.id ? 'primary' : 'secondary'" v-for="semestre in semestres" :key="semestre.id" class="w-full" @click="selectedSemestre = semestre">
          {{semestre.libelle}}
        </Button>
        <div v-else class="flex items-center justify-center h-full">
          <Message severity="warn" icon="pi pi-info-circle">
            Veuillez d'abord sélectionner une année
          </Message>
        </div>
      </div>
    </div>
    <div class="text-lg font-medium border p-4 w-full text-center mx-auto rounded-md flex flex-col gap-2">
      <div class="flex items-center justify-center h-full">
        <Button severity="primary" class="w-full" :disabled="!selectedAnnee || !selectedSemestre">
          Importer les étudiants
        </Button>
      </div>
    </div>
  </div>
</template>

<style scoped>

</style>
