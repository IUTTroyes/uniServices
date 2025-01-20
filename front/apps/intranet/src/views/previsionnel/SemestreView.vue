<script setup>
import {useSemestreStore, useAnneeUnivStore} from "@stores";
import {computed, onMounted, ref, watch} from "vue";
import {SimpleSkeleton, ListSkeleton} from "@components";

const semestreStore = useSemestreStore();
const anneeUnivStore = useAnneeUnivStore();
const departementId = localStorage.getItem('departement');
const semestres = ref([]);
const selectedSemestre = ref(null);
const semestre = ref(null);
const anneesUniversitaires = ref([]);
const selectedAnneeUniversitaire = ref(null);
const matieresSemestre = ref([]);

const isLoadingSemestres = ref(true);
const isLoadingAnneesUniv = ref(true);
const isLoadingSemestre = ref(true);
const isLoadingPrevi = ref(true);

onMounted(async () => {
  isLoadingSemestres.value = true;
  await semestreStore.getSemestresByDepartement(departementId, true);
  semestres.value = semestreStore.semestres;
  isLoadingSemestres.value = false;

  isLoadingSemestre.value = true;
  await semestreStore.getSemestre(semestres.value[0].id);
  selectedSemestre.value = semestres.value[0];
  semestre.value = semestreStore.semestre;
  isLoadingSemestre.value = false;

  isLoadingAnneesUniv.value = true;
  await anneeUnivStore.getAllAnneesUniv();
  anneesUniversitaires.value = anneeUnivStore.anneesUniv;
  anneesUniversitaires.value.sort((a, b) => b.id - a.id);
  isLoadingAnneesUniv.value = false;

  await anneeUnivStore.getCurrentAnneeUniv();
  selectedAnneeUniversitaire.value = anneeUnivStore.anneeUniv;
});

watch(selectedSemestre, async (newValue) => {
  if (newValue) {
    isLoadingSemestre.value = true;
    await semestreStore.getSemestre(newValue.id);
    semestre.value = semestreStore.semestre;
    isLoadingSemestre.value = false;
  }
});

// todo : récupérer le prévi de l'année univ & du semestre
</script>

<template>
  <div class="px-4 py-12">
    <div>
      <div class="flex justify-between gap-10">
        <div class="flex gap-6 w-1/2">
          <SimpleSkeleton v-if="isLoadingSemestres" class="w-1/2"/>
          <IftaLabel v-else class="w-1/2">
            <Select v-model="selectedSemestre" :options="semestres" optionLabel="libelle" placeholder="Sélectionner un semestre" class="w-full"/>
            <label for="semestre">Semestre</label>
          </IftaLabel>
          <SimpleSkeleton v-if="isLoadingAnneesUniv" class="w-1/2"/>
          <IftaLabel v-else class="w-1/2">
            <Select v-model="selectedAnneeUniversitaire" :options="anneesUniversitaires" optionLabel="libelle" placeholder="Sélectionner une année universitaire" class="w-full"/>
            <label for="anneeUniversitaire">Année universitaire</label>
          </IftaLabel>
        </div>
        <Button label="Saisir le prévisionnel" icon="pi pi-plus" />
      </div>

      <ListSkeleton v-if="isLoadingSemestre || isLoadingPrevi" class="mt-6"/>
<!--      <DataTable v-else :value="matieresSemestre" tableStyle="min-width: 50rem">-->
<!--        <Column field="codeMatiere" header="code"></Column>-->
<!--        <Column field="libelle" header="libelle"></Column>-->
<!--        <Column field="type" header="type"></Column>-->
<!--      </DataTable>-->
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
