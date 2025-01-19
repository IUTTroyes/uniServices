<script setup>
import {useSemestreStore, useAnneeUnivStore} from "@stores";
import {onMounted, ref} from "vue";

const semestreStore = useSemestreStore();
const anneeUnivStore = useAnneeUnivStore();
const departementId = localStorage.getItem('departement');
const semestres = ref([]);
const selectedSemestre = ref(null);
const anneesUniversitaires = ref([]);
const selectedAnneeUniversitaire = ref(null);

onMounted(async () => {
  await semestreStore.getSemestresByDepartement(departementId, true);
  semestres.value = semestreStore.semestres;
  selectedSemestre.value = semestres.value[0];

  await anneeUnivStore.getAllAnneesUniv();
  anneesUniversitaires.value = anneeUnivStore.anneesUniv;
  // trier les années universitaires par ordre décroissant
  anneesUniversitaires.value.sort((a, b) => b.id - a.id);

  await anneeUnivStore.getCurrentAnneeUniv();
  selectedAnneeUniversitaire.value = anneeUnivStore.anneeUniv;
});
</script>

<template>
  <div class="px-4 py-12">
    <div class="flex justify-between gap-10">
      <div class="flex gap-6 w-1/2">
        <IftaLabel class="w-1/2">
        <Select v-model="selectedSemestre" :options="semestres" optionLabel="libelle" placeholder="Sélectionner un semestre" class="w-full"/>
          <label for="semestre">Semestre</label>
        </IftaLabel>
        <IftaLabel class="w-1/2">
          <Select v-model="selectedAnneeUniversitaire" :options="anneesUniversitaires" optionLabel="libelle" placeholder="Sélectionner une année universitaire" class="w-full"/>
          <label for="anneeUniversitaire">Année universitaire</label>
        </IftaLabel>
      </div>
      <Button label="Saisir le prévisionnel" icon="pi pi-plus" />
    </div>

<!--  todo: afficher les matières du selectedSemestre  -->
    <DataTable :value="semestres" tableStyle="min-width: 50rem">
      <Column field="libelle" header="Libelle"></Column>
    </DataTable>
  </div>
</template>

<style scoped>

</style>
