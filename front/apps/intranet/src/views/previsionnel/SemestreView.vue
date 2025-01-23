<script setup>
import { useSemestreStore, useAnneeUnivStore } from "@stores";
import { computed, onMounted, ref, watch } from "vue";
import { SimpleSkeleton, ListSkeleton } from "@components";
import { getSemestrePreviService, buildSemestrePreviService } from "@requests";

const semestreStore = useSemestreStore();
const anneeUnivStore = useAnneeUnivStore();
const departementId = localStorage.getItem("departement");

const semestresList = ref([]);
const selectedSemestre = ref(null);
const semestreDetails = ref(null);

const anneesUnivList = ref([]);
const selectedAnneeUniv = ref(null);

const isLoadingSemestres = ref(false);
const isLoadingAnneesUniv = ref(false);
const isLoadingPrevisionnel = ref(true);

const previSemestre = ref(null);
const previGrouped = ref(null);

const getSemestres = async () => {
  isLoadingSemestres.value = true;
  await semestreStore.getSemestresByDepartement(departementId, true);
  semestresList.value = semestreStore.semestres;
  if (semestresList.value.length > 0) {
    selectedSemestre.value = semestresList.value[0];
  }
  isLoadingSemestres.value = false;
};

const getPrevi = async (semestreId) => {
  if (semestreId) {
    isLoadingPrevisionnel.value = true;
    await semestreStore.getSemestre(semestreId);
    semestreDetails.value = semestreStore.semestre;

    previSemestre.value = await getSemestrePreviService(selectedSemestre.value.id, selectedAnneeUniv.value.id);
    isLoadingPrevisionnel.value = false;

    previGrouped.value = await buildSemestrePreviService(previSemestre.value);
    // console.log('groupedPrevi', previGrouped.value);
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
  await getAnneesUniv();
});

watch([selectedSemestre, selectedAnneeUniv], async ([newSemestre, newAnneeUniv]) => {
  if (newSemestre && newAnneeUniv) {
    await getPrevi(newSemestre.id, newAnneeUniv.id);
  }
});
</script>

<template>
  <div class="px-4 py-12 flex flex-col gap-6">
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
      <ListSkeleton v-if="isLoadingPrevisionnel" class="mt-6" />
      <div v-else>
        <DataTable v-if="previGrouped?.length > 0" :value="previGrouped" tableStyle="min-width: 50rem">
          <Column field="enseignement.codeEnseignement" header="Code" />
          <Column field="enseignement.libelle" header="Nom" />
          <Column field="enseignement.type" header="Type" />
          <Column field="personnel.length" header="Nb intervenants" />
          <Column class="bg-purple-50" field="heures.heures.CM" header="CM" />
          <Column class="bg-green-50" field="heures.heures.TD" header="TD" />
          <Column class="bg-amber-50" field="heures.heures.TP" header="TP" />
          <Column field="heures.heures.CM" header="Total" />
        </DataTable>

        <Message v-else severity="error" icon="pi pi-times-circle">
          Aucun prévisionnel pour cette année universitaire et ce semestre
        </Message>
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
