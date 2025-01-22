<script setup>
import { useSemestreStore, useAnneeUnivStore } from "@stores";
import { computed, onMounted, ref, watch } from "vue";
import { SimpleSkeleton, ListSkeleton } from "@components";
import { getSemestrePreviService} from "@requests";

const semestreStore = useSemestreStore();
const anneeUnivStore = useAnneeUnivStore();
const departementId = localStorage.getItem("departement");

const semestresList = ref([]);
const selectedSemestre = ref(null);
const semestreDetails = ref(null);

const anneesUnivList = ref([]);
const selectedAnneeUniv = ref(null);
const matieresSemestre = ref([]);

const isLoadingSemestres = ref(false);
const isLoadingAnneesUniv = ref(false);
const isLoadingSemestreDetails = ref(false);
const isLoadingPrevisionnel = ref(true);

const previSemestre = ref(null);

const getSemestres = async () => {
  isLoadingSemestres.value = true;
  await semestreStore.getSemestresByDepartement(departementId, true);
  semestresList.value = semestreStore.semestres;
  if (semestresList.value.length > 0) {
    selectedSemestre.value = semestresList.value[0];
  }
  isLoadingSemestres.value = false;
};

const getSelectedSemestre = async (semestreId) => {
  if (semestreId) {
    isLoadingSemestreDetails.value = true;
    await semestreStore.getSemestre(semestreId);
    semestreDetails.value = semestreStore.semestre;
    isLoadingSemestreDetails.value = false;

    // todo : récupérer le prévisionnel de l'année universitaire
    previSemestre.value = await getSemestrePreviService(selectedSemestre.value.id);
    console.log(previSemestre);
  }
};

const getAnneesUniv = async () => {
  isLoadingAnneesUniv.value = true;
  await anneeUnivStore.getAllAnneesUniv();
  anneesUnivList.value = anneeUnivStore.anneesUniv.sort((a, b) => b.id - a.id);
  await anneeUnivStore.getCurrentAnneeUniv();
  selectedAnneeUniv.value = anneeUnivStore.anneeUniv;
  isLoadingAnneesUniv.value = false;
};

onMounted(async () => {
  await getSemestres();
  if (selectedSemestre.value) {
    await getSelectedSemestre(selectedSemestre.value.id);
  }
  await getAnneesUniv();
});

watch(selectedSemestre, async (newValue) => {
  if (newValue) {
    await getSelectedSemestre(newValue.id);
  }
});
</script>

<template>
  <div class="px-4 py-12">
    <div>
      <div class="flex justify-between gap-10">
        <div class="flex gap-6 w-1/2">
          <SimpleSkeleton v-if="isLoadingSemestres" class="w-1/2" />
          <IftaLabel v-else class="w-1/2">
            <Select
                v-model="selectedSemestre"
                :options="semestresList"
                optionLabel="libelle"
                placeholder="Sélectionner un semestre"
                class="w-full"
            />
            <label for="semestre">Semestre</label>
          </IftaLabel>
          <SimpleSkeleton v-if="isLoadingAnneesUniv" class="w-1/2" />
          <IftaLabel v-else class="w-1/2">
            <Select
                v-model="selectedAnneeUniv"
                :options="anneesUnivList"
                optionLabel="libelle"
                placeholder="Sélectionner une année universitaire"
                class="w-full"
            />
            <label for="anneeUniversitaire">Année universitaire</label>
          </IftaLabel>
        </div>
        <Button label="Saisir le prévisionnel" icon="pi pi-plus" />
      </div>
      <ListSkeleton
          v-if="isLoadingPrevisionnel"
          class="mt-6"
      />
      <div v-else>
      </div>
    </div>
  </div>
</template>

<style scoped>
.loader {
  text-align: center;
  font-size: 1.5rem;
  padding: 2rem;
}
</style>
