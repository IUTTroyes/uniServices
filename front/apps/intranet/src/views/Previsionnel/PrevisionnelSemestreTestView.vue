<script setup>
import {onMounted, ref} from 'vue';
import {useAnneeUnivStore, useSemestreStore, useUsersStore} from "@stores";
import {SimpleSkeleton} from "@components";

// const tableau = ref([
//   [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
//   [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
//   [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
//   [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
//   [1.5, 2.5, 3.5, 4.5, 5.5, 6.5, 7.5, 8.5, 9.5, 10.5]
// ]);
const tableau = ref(Array.from({ length: 25 }, () => Array(40).fill(0)));


const isLoadingSemestres = ref(false);
const isLoadingAnneesUniv = ref(false);

const usersStore = useUsersStore();
const semestreStore = useSemestreStore();
const anneeUnivStore = useAnneeUnivStore();

const anneesUnivList = ref([]);
const selectedAnneeUniv = ref(null);
const semestresList = ref([]);
const selectedSemestre = ref(null);

const departementId = usersStore.departementDefaut.id;

const getSemestres = async () => {
  isLoadingSemestres.value = true;
  await semestreStore.getSemestresByDepartement(departementId, true);
  semestresList.value = semestreStore.semestres;
  if (semestresList.value.length > 0) {
    selectedSemestre.value = semestresList.value[0];
  }
  isLoadingSemestres.value = false;
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
</script>

<template>
  <div class="flex gap-6 w-1/2 m-6">
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

  <div class="table-responsive">
    <table class="table table-bordered table-striped">
      <thead>
      <tr>
        <th class="sticky-col"></th>
        <th v-for="j in 40" :key="j" class="sticky-header">matière {{ j }}</th>
      </tr>
      </thead>
      <tbody>
      <tr v-for="i in 25" :key="i">
        <td class="sticky-col">Prof {{ i }}</td>
        <td v-for="j in 40" :key="j" :id="`prof_${i}_mat_${j}`">
          {{ tableau[i - 1][j - 1] }}
        </td>
      </tr>
      </tbody>
    </table>
  </div>

<Divider/>

  <div class="m-6">
    <div class="table-responsive">
      <table class="table table-bordered table-striped">
        <thead>
        <tr>
          <th rowspan="2">Enseignant</th>
          <th colspan="3">Enseignement 1</th>
          <th colspan="3">Enseignement 2</th>
          <th colspan="3">Enseignement 3</th>
        </tr>
        <tr>
          <th>CM</th>
          <th>TD</th>
          <th>TP</th>
          <th>CM</th>
          <th>TD</th>
          <th>TP</th>
          <th>CM</th>
          <th>TD</th>
          <th>TP</th>
        </tr>
        <tr>
          <th></th>
          <th>1</th>
          <th>2</th>
          <th>3</th>
          <th>1</th>
          <th>2</th>
          <th>3</th>
          <th>1</th>
          <th>2</th>
          <th>3</th>
        </tr>
        </thead>
        <tbody>
        <tr>
          <td>Enseignant 1</td>
          <td>10</td>
          <td>20</td>
          <td>30</td>
          <td>15</td>
          <td>25</td>
          <td>35</td>
          <td>20</td>
          <td>30</td>
          <td>40</td>
        </tr>
        <tr>
          <td>Enseignant 2</td>
          <td>12</td>
          <td>22</td>
          <td>32</td>
          <td>17</td>
          <td>27</td>
          <td>37</td>
          <td>22</td>
          <td>32</td>
          <td>42</td>
        </tr>
        </tbody>
      </table>
    </div>
  </div>

</template>

<style scoped>
.table-responsive {
  overflow: auto;
  max-height: 600px;
}

.sticky-header {
  position: sticky;
  top: 0;
  background-color: #f2f2f2;
  z-index: 1;
}

.sticky-col {
  position: sticky;
  left: 0;
  background-color: #f2f2f2;
  z-index: 2;
}
.table {
  width: 100%;
  border-collapse: collapse;
}
.table th, .table td {
  border: 1px solid #ddd;
  padding: 8px;
}
.table th {
  background-color: #f2f2f2;
  text-align: center;
}
</style>
