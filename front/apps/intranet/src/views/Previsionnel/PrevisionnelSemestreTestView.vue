<script setup>
import {onMounted, ref, watch} from 'vue';
import {useAnneeUnivStore, useEnseignementsStore, useSemestreStore, useUsersStore} from "@stores";
import {SimpleSkeleton, ListSkeleton, ErrorView} from "@components";
import {getPersonnelsDepartementService, getSemestrePreviTestService} from "@requests";

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
const isLoadingPrevisionnel = ref(false);

const usersStore = useUsersStore();
const semestreStore = useSemestreStore();
const anneeUnivStore = useAnneeUnivStore();
const enseignementStore = useEnseignementsStore();

const anneesUnivList = ref([]);
const selectedAnneeUniv = ref(null);
const semestresList = ref([]);
const selectedSemestre = ref(null);
const previSemestre = ref(null);
const personnelsList = ref([]);
const enseignementsList = ref([]);

const departementId = usersStore.departementDefaut.id;

const heures = ref([])
const groupes= ref([])

const totalCM = ref([])
const totalTD = ref([])
const totalTP = ref([])

const hasError = ref(false);

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

const getPrevi = async (semestreId) => {
  if (semestreId) {
    isLoadingPrevisionnel.value = true;

    try {
      await semestreStore.getSemestre(semestreId);

      previSemestre.value = await getSemestrePreviTestService(selectedSemestre.value.id, selectedAnneeUniv.value.id);

      try {
        if (selectedSemestre.value) {
          await enseignementStore.getMatieresSemestre(selectedSemestre.value.id);
        }
        enseignementsList.value = enseignementStore.enseignements;
        if (enseignementsList.value.length > 0) {
          // construire chaque élément de la liste des matières avec d'abord le libellé de la matière puis le code
          enseignementsList.value = enseignementsList.value.map((enseignement) => ({
            ...enseignement,
            label: `${enseignement.codeEnseignement} - ${enseignement.libelle}`,
            value: enseignement
          }));
        }
      } catch (error) {
        console.error('Erreur lors du chargement des matières:', error);
      }
      try {
        personnelsList.value = await getPersonnelsDepartementService(departementId);
        personnelsList.value = personnelsList.value.map((personnel) => ({
          ...personnel,
          label: `${personnel.personnel.prenom} ${personnel.personnel.nom}`,
          value: personnel
        }));
      } catch (error) {
        console.error('Erreur lors du chargement des matières:', error);
      }

    } catch (error) {
      console.error('Erreur lors du chargement du prévisionnel:', error);
      hasError.value = true;
    } finally {
      heures.value = previSemestre.value[1];
      groupes.value = previSemestre.value[2];
      totalCM.value = calculTotal('CM');
      totalTD.value = calculTotal('TD');
      totalTP.value = calculTotal('TP');
      isLoadingPrevisionnel.value = false;
    }
  }
};

const getHeureValue = (type, enseignementId, enseignantId) => {
  if (heures.value && heures.value[enseignementId] && heures.value[enseignementId][enseignantId]) {
    return heures.value[enseignementId][enseignantId][type] !== undefined ? heures.value[enseignementId][enseignantId][type] : null;
  }
  return null;
};
const changeHeureValue = () => {
  console.log('changeHeureValue');
};
const getGroupeValue = (type, enseignementId, enseignantId) => {
  if (groupes.value && groupes.value[enseignementId] && groupes.value[enseignementId][enseignantId]) {
    return groupes.value[enseignementId][enseignantId][type] !== undefined ? groupes.value[enseignementId][enseignantId][type] : null;
  }
  return null;
};
const changeGroupeValue = () => {
  console.log('changeGroupeValue');
};

const calculTotal = (type) => {
  // pour chaque enseignement
  return enseignementsList.value.map(enseignement => {
    // pour chaque personnel
    return personnelsList.value.reduce((total, personnel) => {
      const heures = getHeureValue(type, enseignement.id, personnel.personnel.id);
      const groupes = getGroupeValue(type, enseignement.id, personnel.personnel.id);
      console.log(heures, groupes)
      return total + (parseFloat(heures) || 0);
    }, 0);
  });
};


watch([selectedSemestre, selectedAnneeUniv], async ([newSemestre, newAnneeUniv]) => {
  if (newSemestre && newAnneeUniv) {
    await getPrevi(newSemestre.id, newAnneeUniv.id);
  }
});
</script>

<template>
  <ErrorView v-if="hasError" />
  <div v-else class="flex gap-6 w-1/2 m-6">
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

  <SimpleSkeleton v-if="isLoadingPrevisionnel" class="w-1/2" />
  <Message v-else severity="info" class="w-fit my-6 mx-auto" icon="pi pi-info-circle">
    Cliquez sur une case pour en éditer le contenu
  </Message>
  <ListSkeleton v-if="isLoadingPrevisionnel" class="w-1/2" />
  <div v-else class="table-responsive overflow-auto text-sm">
    <table class="w-full border-collapse table">
      <thead class="sticky top-0 z-20">
      <tr>
        <th class="bg-primary-100 dark:bg-primary-950 sticky top-0 left-0">Enseignements</th>
        <th v-for="enseignement in enseignementsList" :key="enseignement.id" class="bg-primary-100 dark:bg-primary-950" colspan="6">{{enseignement.libelle}}</th>
      </tr>
      <tr>
        <th class="bg-primary-100 dark:bg-primary-950 sticky top-0 left-0">Type de groupe</th>
        <template v-for="enseignement in enseignementsList">
          <th class="bg-primary-100 dark:bg-primary-950" colspan="2">CM</th>
          <th class="bg-primary-100 dark:bg-primary-950" colspan="2">TD</th>
          <th class="bg-primary-100 dark:bg-primary-950" colspan="2">TP</th>
        </template>
      </tr>
      <tr>
        <th class="bg-primary-100 dark:bg-primary-950 sticky top-0 left-0">Nb Grp : CM {{selectedSemestre?.nbGroupesCm}}, TD {{selectedSemestre?.nbGroupesTd}}, TP {{selectedSemestre?.nbGroupesTp}} </th>
        <template v-for="enseignement in enseignementsList">
          <th class="bg-primary-100 dark:bg-primary-950 text-nowrap">Nb h</th>
          <th class="bg-primary-100 dark:bg-primary-950 text-nowrap">Nb gr</th>
          <th class="bg-primary-100 dark:bg-primary-950 text-nowrap">Nb h</th>
          <th class="bg-primary-100 dark:bg-primary-950 text-nowrap">Nb gr</th>
          <th class="bg-primary-100 dark:bg-primary-950 text-nowrap">Nb h</th>
          <th class="bg-primary-100 dark:bg-primary-950 text-nowrap">Nb gr</th>
        </template>
      </tr>
      <tr>
        <th class="bg-primary-100 dark:bg-primary-950 sticky top-0 left-0">Nb Hr attendu</th>
        <template v-for="enseignement in enseignementsList">
          <th class="bg-primary-100 dark:bg-primary-950">{{enseignement.heures.CM.PN}} h</th>
          <th class="bg-primary-100 dark:bg-primary-950">{{selectedSemestre?.nbGroupesCm}}</th>
          <th class="bg-primary-100 dark:bg-primary-950">{{enseignement.heures.TD.PN}} h</th>
          <th class="bg-primary-100 dark:bg-primary-950">{{selectedSemestre?.nbGroupesTd}}</th>
          <th class="bg-primary-100 dark:bg-primary-950">{{enseignement.heures.TP.PN}} h</th>
          <th class="bg-primary-100 dark:bg-primary-950">{{selectedSemestre?.nbGroupesTp}}</th>
        </template>
      </tr>
      </thead>
      <tbody>
      <tr v-for="(personnel, pIndex) in personnelsList" :key="personnel.id">
        <td class="sticky left-0 text-nowrap z-10 bg-gray-50 dark:bg-gray-800">{{ personnel.label }}</td>
        <template v-for="(enseignement, eIndex) in enseignementsList">
         <td v-if="enseignement.heures.CM.PN !== totalCM[eIndex]" class="!p-0 bg-amber-300 dark:bg-opacity-50">
            <!--            {{ getHeureValue('CM', enseignement.id, personnel.personnel.id) }}-->
            <input v-if="getHeureValue('CM', enseignement.id, personnel.personnel.id)"
                   v-model="heures[enseignement.id][personnel.personnel.id].CM"
                   type="number"
                   :min="0" :max="99"
                   class="w-full h-full text-center bg-transparent"/>
            <input v-else
                   placeholder=""
                   type="number"
                   :min="0" :max="99"
                   @change="changeHeureValue"
                   class="w-full p-2 bg-transparent"/>
          </td>
         <td v-else class="!p-0">
            <!--            {{ getHeureValue('CM', enseignement.id, personnel.personnel.id) }}-->
            <input v-if="getHeureValue('CM', enseignement.id, personnel.personnel.id)"
                   v-model="heures[enseignement.id][personnel.personnel.id].CM"
                   type="number"
                   :min="0" :max="99"
                   class="w-full h-full text-center bg-transparent"/>
            <input v-else
                   placeholder=""
                   type="number"
                   :min="0" :max="99"
                   @change="changeHeureValue"
                   class="w-full p-2 bg-transparent"/>
          </td>

          <td v-if="enseignement.heures.CM.PN !== totalCM[eIndex]" class="!p-0 bg-amber-300 dark:bg-opacity-50">
            <!--            {{ getGroupeValue('CM', enseignement.id, personnel.personnel.id) }}-->
            <input v-if="getGroupeValue('CM', enseignement.id, personnel.personnel.id)"
                   v-model="groupes[enseignement.id][personnel.personnel.id].CM"
                   type="number"
                   :min="0" :max="99"
                   class="w-full h-full text-center bg-transparent"/>
            <input v-else
                   placeholder=""
                   type="number"
                   :min="0" :max="99"
                   @change="changeGroupeValue"
                   class="w-full p-2 bg-transparent"/>
          </td>
          <td v-else class="bg-gray-50 dark:bg-gray-700 !p-0">
            <!--            {{ getGroupeValue('CM', enseignement.id, personnel.personnel.id) }}-->
            <input v-if="getGroupeValue('CM', enseignement.id, personnel.personnel.id)"
                   v-model="groupes[enseignement.id][personnel.personnel.id].CM"
                   type="number"
                   :min="0" :max="99"
                   class="w-full h-full text-center bg-transparent"/>
            <input v-else
                   placeholder=""
                   type="number"
                   :min="0" :max="99"
                   @change="changeGroupeValue"
                   class="w-full p-2 bg-transparent"/>
          </td>

          <td v-if="enseignement.heures.TD.PN !== totalTD[eIndex]" class="!p-0 bg-amber-300 dark:bg-opacity-50">
            <!--            {{ getHeureValue('TD', enseignement.id, personnel.personnel.id) }}-->
            <input v-if="getHeureValue('TD', enseignement.id, personnel.personnel.id)"
                   v-model="heures[enseignement.id][personnel.personnel.id].TD"
                   type="number"
                   :min="0" :max="99"
                   class="w-full h-full text-center bg-transparent"/>
            <input v-else
                   placeholder=""
                   type="number"
                   :min="0" :max="99"
                   @change="changeHeureValue"
                   class="w-full p-2 bg-transparent"/>
          </td>
          <td v-else class="!p-0">
            <!--            {{ getHeureValue('TD', enseignement.id, personnel.personnel.id) }}-->
            <input v-if="getHeureValue('TD', enseignement.id, personnel.personnel.id)"
                   v-model="heures[enseignement.id][personnel.personnel.id].TD"
                   type="number"
                   :min="0" :max="99"
                   class="w-full h-full text-center bg-transparent"/>
            <input v-else
                   placeholder=""
                   type="number"
                   :min="0" :max="99"
                   @change="changeHeureValue"
                   class="w-full p-2 bg-transparent"/>
          </td>

          <td v-if="enseignement.heures.TD.PN !== totalTD[eIndex]" class="!p-0 bg-amber-300 dark:bg-opacity-50">
            <!--            {{ getGroupeValue('TD', enseignement.id, personnel.personnel.id) }}-->
            <input v-if="getGroupeValue('TD', enseignement.id, personnel.personnel.id)"
                   v-model="groupes[enseignement.id][personnel.personnel.id].TD"
                   type="number"
                   :min="0" :max="99"
                   class="w-full h-full text-center bg-transparent"/>
            <input v-else
                   placeholder=""
                   type="number"
                   :min="0" :max="99"
                   @change="changeGroupeValue"
                   class="w-full p-2 bg-transparent"/>
          </td>
          <td v-else class="bg-gray-50 dark:bg-gray-700 !p-0">
            <!--            {{ getGroupeValue('TD', enseignement.id, personnel.personnel.id) }}-->
            <input v-if="getGroupeValue('TD', enseignement.id, personnel.personnel.id)"
                   v-model="groupes[enseignement.id][personnel.personnel.id].TD"
                   type="number"
                   :min="0" :max="99"
                   class="w-full h-full text-center bg-transparent"/>
            <input v-else
                   placeholder=""
                   type="number"
                   :min="0" :max="99"
                   @change="changeGroupeValue"
                   class="w-full p-2 bg-transparent"/>
          </td>

          <td v-if="enseignement.heures.TP.PN !== totalTP[eIndex]" class="!p-0 bg-amber-300 dark:bg-opacity-50">
            <!--            {{ getHeureValue('TP', enseignement.id, personnel.personnel.id) }}-->
            <input v-if="getHeureValue('TP', enseignement.id, personnel.personnel.id)"
                   v-model="heures[enseignement.id][personnel.personnel.id].TP"
                   type="number"
                   :min="0" :max="99"
                   class="w-full h-full text-center bg-transparent"/>
            <input v-else
                   placeholder=""
                   type="number"
                   :min="0" :max="99"
                   @change="changeHeureValue"
                   class="w-full p-2 bg-transparent"/>
          </td>
          <td v-else class="!p-0">
            <!--            {{ getHeureValue('TP', enseignement.id, personnel.personnel.id) }}-->
            <input v-if="getHeureValue('TP', enseignement.id, personnel.personnel.id)"
                   v-model="heures[enseignement.id][personnel.personnel.id].TP"
                   type="number"
                   :min="0" :max="99"
                   class="w-full h-full text-center bg-transparent"/>
            <input v-else
                   placeholder=""
                   type="number"
                   :min="0" :max="99"
                   @change="changeHeureValue"
                   class="w-full p-2 bg-transparent"/>
          </td>

          <td v-if="enseignement.heures.TP.PN !== totalTP[eIndex]" class="!p-0 bg-amber-300 dark:bg-opacity-50">
            <!--            {{ getGroupeValue('TP', enseignement.id, personnel.personnel.id) }}-->
            <input v-if="getGroupeValue('TP', enseignement.id, personnel.personnel.id)"
                   v-model="groupes[enseignement.id][personnel.personnel.id].TP"
                   type="number"
                   :min="0" :max="99"
                   class="w-full h-full text-center bg-transparent"/>
            <input v-else
                   placeholder=""
                   type="number"
                   :min="0" :max="99"
                   @change="changeGroupeValue"
                   class="w-full p-2 bg-transparent"/>
          </td>
          <td v-else class="bg-gray-50 dark:bg-gray-700 !p-0">
            <!--            {{ getGroupeValue('TP', enseignement.id, personnel.personnel.id) }}-->
            <input v-if="getGroupeValue('TP', enseignement.id, personnel.personnel.id)"
                   v-model="groupes[enseignement.id][personnel.personnel.id].TP"
                   type="number"
                   :min="0" :max="99"
                   class="w-full h-full text-center bg-transparent"/>
            <input v-else
                   placeholder=""
                   type="number"
                   :min="0" :max="99"
                   @change="changeGroupeValue"
                   class="w-full p-2 bg-transparent"/>
          </td>
        </template>
      </tr>
      </tbody>
      <tfoot class="sticky bottom-0 z-20">
      <tr>
        <td class="sticky left-0 text-nowrap z-10 bg-primary-100 dark:bg-primary-950">Total</td>
        <template v-for="(enseignement, eIndex) in enseignementsList">
          <td class="bg-white border !p-4" colspan="2">{{ totalCM[eIndex] }} h</td>
          <td class="bg-white border !p-4" colspan="2">{{ totalTD[eIndex] }} h</td>
          <td class="bg-white border !p-4" colspan="2">{{ totalTP[eIndex] }} h</td>
        </template>
      </tr>
      </tfoot>
    </table>
  </div>
</template>

<style scoped>
.table-responsive {
  max-height: 70vh;
}
table th, table td {
  border: 1px solid #ddd;
  padding: 8px;
}
table th {
  text-align: center;
}

</style>
