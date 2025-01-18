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
  await semestreStore.getSemestresByDepartement(departementId, );
  semestres.value = semestreStore.semestres;
  console.log(semestres.value);

  await anneeUnivStore.getAllAnneesUniv();
  anneesUniversitaires.value = anneeUnivStore.anneesUniv;
  // trier les années universitaires par ordre décroissant
  anneesUniversitaires.value.sort((a, b) => b.id - a.id);
});
</script>

<template>
  <div class="px-4 py-12">
    <div class="flex justify-between gap-10">
      <div class="flex gap-6 w-1/2">
        <Select v-model="selectedSemestre" :options="semestres" optionLabel="libelle" placeholder="Sélectionner un semestre" class="w-1/2" />
        <Select v-model="selectedAnnee" :options="anneesUniversitaires" optionLabel="libelle" placeholder="Sélectionner une année universitaire" class="w-1/2"/>
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
