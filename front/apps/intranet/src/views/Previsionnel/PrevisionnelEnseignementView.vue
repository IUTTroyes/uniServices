<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useSemestreStore, useAnneeUnivStore, useUsersStore, useEnseignementsStore } from '@stores';
import { SimpleSkeleton, ListSkeleton } from '@components';
import { getSemestreEnseignementPreviService } from '@requests';
import PrevisionnelTable from '@/components/Previsionnel/PrevisionnelTable.vue';

const usersStore = useUsersStore();
const semestreStore = useSemestreStore();
const enseignementStore = useEnseignementsStore();
const anneeUnivStore = useAnneeUnivStore();
const departementId = usersStore.departementDefaut.id;

const semestresList = ref([]);
const EnseignementsList = ref([]);
const selectedSemestre = ref(null);
const selectedEnseignement = ref(null);

const anneesUnivList = ref([]);
const selectedAnneeUniv = ref(null);

const isLoadingSemestres = ref(false);
const isLoadingEnseignements = ref(false);
const isLoadingAnneesUniv = ref(false);
const isLoadingPrevisionnel = ref(true);

const previSemestreMatiere = ref(null);

const isEditing = ref(false);

const size = ref({ label: 'Petit', value: 'small' });
const sizeOptions = ref([
  { label: 'Petit', value: 'small' },
  { label: 'Normal', value: 'null' },
  { label: 'Large', value: 'large' }
]);

const filters = ref({
  'enseignement.libelle': { value: null, matchMode: 'contains' }
});

const getSemestres = async () => {
  isLoadingSemestres.value = true;
  try {
    await semestreStore.getSemestresByDepartement(departementId, true);
    semestresList.value = semestreStore.semestres || [];
    if (semestresList.value.length > 0) {
      selectedSemestre.value = semestresList.value[0];
    }
  } catch (error) {
    console.error('Erreur lors du chargement des semestres:', error);
  } finally {
    isLoadingSemestres.value = false;
  }
};

const getEnseignements = async () => {
  isLoadingEnseignements.value = true;
  try {
    if (selectedSemestre.value) {
      await enseignementStore.getMatieresSemestre(selectedSemestre.value.id);
    }
    EnseignementsList.value = enseignementStore.enseignements;
    if (EnseignementsList.value.length > 0) {
      selectedEnseignement.value = EnseignementsList.value[0];
    } else {
      selectedEnseignement.value = null;
    }
  } catch (error) {
    console.error('Erreur lors du chargement des matières:', error);
  } finally {
    isLoadingEnseignements.value = false;
  }
};

const getAnneesUniv = async () => {
  isLoadingAnneesUniv.value = true;
  try {
    await anneeUnivStore.getAllAnneesUniv();
    anneesUnivList.value = anneeUnivStore.anneesUniv.sort((a, b) => b.id - a.id);
    await anneeUnivStore.getCurrentAnneeUniv();
    selectedAnneeUniv.value = anneeUnivStore.anneeUniv;
  } catch (error) {
    console.error('Erreur lors du chargement des années universitaires:', error);
  } finally {
    isLoadingAnneesUniv.value = false;
  }
};

const getPrevi = async (semestreId) => {
  if (semestreId) {
    isLoadingPrevisionnel.value = true;
    await semestreStore.getSemestre(semestreId);
    if (selectedEnseignement.value) {
      previSemestreMatiere.value = await getSemestreEnseignementPreviService(
          selectedSemestre.value.id,
          selectedEnseignement.value.id,
          selectedAnneeUniv.value.id
      );
    }

    console.log('previSemestreMatiere', previSemestreMatiere.value);

    isLoadingPrevisionnel.value = false;
  }
};

onMounted(async () => {
  await getSemestres();
  await getEnseignements();
  await getAnneesUniv();
});

watch([selectedSemestre, selectedAnneeUniv, selectedEnseignement], async ([newSemestre, newAnneeUniv, newEnseignement]) => {
  if (!newSemestre || !newAnneeUniv || !newEnseignement) return;
  await getPrevi(newSemestre.id, newAnneeUniv.id);
});
watch(selectedSemestre, async (newSemestre) => {
  await getEnseignements();
});

watch(isEditing, async (newIsEditing) => {
  if (!newIsEditing) {
    isLoadingPrevisionnel.value === true;
    await getPrevi(selectedSemestre.value.id, selectedAnneeUniv.value.id);
    isLoadingPrevisionnel.value === false;
  }
});

const columns = ref([
  { header: 'Intervenant', field: 'personnel.display', sortable: true, colspan: 1 },
  { header: 'Nb H/Gr.', field: 'heures.CM.NbHrGrp', colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap', unit: ' h'},
  { header: 'Nb Gr.', field: 'heures.CM.NbGrp', colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap'},
  { header: 'Nb Seance/Gr.', field: 'heures.CM.NbSeanceGrp', colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap'},
  { header: 'Nb H/Gr.', field: 'heures.TD.NbHrGrp', colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap', unit: ' h'},
  { header: 'Nb Gr.', field: 'heures.TD.NbGrp', colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap'},
  { header: 'Nb Seance/Gr.', field: 'heures.TD.NbSeanceGrp', colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap'},
  { header: 'Nb H/Gr.', field: 'heures.TP.NbHrGrp', colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap', unit: ' h'},
  { header: 'Nb Gr.', field: 'heures.TP.NbGrp', colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap'},
  { header: 'Nb Seance/Gr.', field: 'heures.TP.NbSeanceGrp', colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap'},
]);

const topHeaderCols = ref([
  { header: 'CM', colspan: 3, class: '!bg-purple-400 !bg-opacity-20' },
  { header: 'TD', colspan: 3, class: '!bg-green-400 !bg-opacity-20' },
  { header: 'TP', colspan: 3, class: '!bg-amber-400 !bg-opacity-20' }
]);

const additionalRows = computed(() => [
  [
    { footer: 'Synthèse', colspan: 19, class: '!text-center !font-bold'},
  ],
  [
    { footer: '', colspan: 1, class: '!text-center !font-bold'},
    { footer: 'Nb hr attendu', colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap' },
    { footer: 'Nb hr saisi', colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap' },
    { footer: 'Diff', colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap' },
    { footer: 'Nb hr attendu', colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap' },
    { footer: 'Nb hr saisi', colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap' },
    { footer: 'Diff', colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap' },
    { footer: 'Nb hr attendu', colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap' },
    { footer: 'Nb hr saisi', colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap' },
    { footer: 'Diff', colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap' },
  ],
  [
    { footer: 'Vérification du total d\'heures par étudiant', colspan: 1 },
    { footer: previSemestreMatiere.value[1].CM.NbHrAttendu, colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
    { footer: previSemestreMatiere.value[1].CM.NbHrSaisi, colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
    { footer: previSemestreMatiere.value[1].CM.Diff, colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap', unit: ' h', tag: true, tagClass: (value) => value === 0 ? '!bg-green-400 !text-white' : (value < 0 ? '!bg-amber-400 !text-white' : '!bg-red-400 !text-white'), tagSeverity: (value) => value === 0 ? 'success' : (value < 0 ? 'warn' : 'danger'), tagIcon: (value) => value === 0 ? 'pi pi-check' : (value < 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up') },
    { footer: previSemestreMatiere.value[1].TD.NbHrAttendu, colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
    { footer: previSemestreMatiere.value[1].TD.NbHrSaisi, colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
    { footer: previSemestreMatiere.value[1].TD.Diff, colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap', unit: ' h', tag: true, tagClass: (value) => value === 0 ? '!bg-green-400 !text-white' : (value < 0 ? '!bg-amber-400 !text-white' : '!bg-red-400 !text-white'), tagSeverity: (value) => value === 0 ? 'success' : (value < 0 ? 'warn' : 'danger'), tagIcon: (value) => value === 0 ? 'pi pi-check' : (value < 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up') },
    { footer: previSemestreMatiere.value[1].TP.NbHrAttendu, colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
    { footer: previSemestreMatiere.value[1].TP.NbHrSaisi, colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
    { footer: previSemestreMatiere.value[1].TP.Diff, colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap', unit: ' h', tag: true, tagClass: (value) => value === 0 ? '!bg-green-400 !text-white' : (value < 0 ? '!bg-amber-400 !text-white' : '!bg-red-400 !text-white'), tagSeverity: (value) => value === 0 ? 'success' : (value < 0 ? 'warn' : 'danger'), tagIcon: (value) => value === 0 ? 'pi pi-check' : (value < 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up') },
  ],
  [
    { footer: 'Total d\'heures par type de groupe', colspan: 1},
    { footer: previSemestreMatiere.value[2].TotalCM, colspan: 3, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap !text-center', unit: ' h' },
    { footer: previSemestreMatiere.value[2].TotalTD, colspan: 3, class: '!bg-green-400 !bg-opacity-20 !text-nowrap !text-center', unit: ' h' },
    { footer: previSemestreMatiere.value[2].TotalTP, colspan: 3, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap !text-center', unit: ' h' },
  ],
  [
    { footer: '', colspan: 1},
    { footer: 'Classique', colspan: 4, class: '!text-nowrap !text-center font-bold' },
    { footer: 'Équivalent TD', colspan: 5, class: '!text-nowrap !text-center font-bold' },
  ],
  [
    { footer: 'Total d\'heures', colspan: 1},
    { footer: previSemestreMatiere.value[3].TotalClassique, colspan: 4, class: '!text-nowrap !text-center', unit: ' h' },
    { footer: previSemestreMatiere.value[3].TotalTd, colspan: 5, class: '!text-nowrap !text-center', unit: ' h' },
  ],
]);

const footerCols = computed(() => [
]);
</script>

<template>
  <div class="px-4 flex flex-col">
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
        <SimpleSkeleton v-if="isLoadingEnseignements" class="w-1/2" />
        <IftaLabel v-else class="w-1/2">
          <Select
              v-model="selectedEnseignement"
              :options="EnseignementsList"
              optionLabel="libelle"
              placeholder="Sélectionner une matière"
              class="w-full"
          />
          <label for="semestre">Matière</label>
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
      <Button v-if="!isEditing" label="Saisir le prévisionnel" icon="pi pi-plus" @click="isEditing = !isEditing" />
      <Button v-else label="Afficher le prévisionnel" icon="pi pi-eye" @click="isEditing = !isEditing" />
    </div>
    <ListSkeleton v-if="isLoadingPrevisionnel" class="mt-6" />
    <div v-else>
      <Message severity="info" icon="pi pi-info-circle" class="w-fit mx-auto my-2 px-3">
        <div class="flex gap-6">
          Nombre d'heures par étudiant attendu :
          <span class="font-bold">CM : {{ selectedEnseignement.heures.CM.PN }} h</span>
          <span class="font-bold">TD : {{ selectedEnseignement.heures.TD.PN }} h</span>
          <span class="font-bold">TP : {{ selectedEnseignement.heures.TP.PN }} h</span>
        </div>
      </Message>

      <div v-if="previSemestreMatiere[0].length > 0">
        <div class="flex w-full justify-between my-6">
          <SelectButton v-model="size" :options="sizeOptions" optionLabel="label" dataKey="label" />
        </div>
        <div v-if="!isEditing">
          <PrevisionnelTable
              origin="previMatiereSynthese"
              :columns="columns"
              :topHeaderCols="topHeaderCols"
              :additionalRows="additionalRows"
              :footerCols="footerCols"
              :data="previSemestreMatiere[0]"
              :filters="filters"
              :size="size.value"
              :headerTitle="`Prévisionnel du semestre ${selectedSemestre?.libelle} pour la matière ${selectedEnseignement.libelle}`"
              :headerTitlecolspan="1"/>
        </div>
        <div v-else>
          <PrevisionnelTable
              origin="previMatiereForm"
              :columns="columns"
              :topHeaderCols="topHeaderCols"
              :additionalRows="additionalRows"
              :footerCols="footerCols"
              :data="previSemestreMatiere[0]"
              :filters="filters"
              :size="size.value"
              :headerTitle="`Prévisionnel du semestre ${selectedSemestre?.libelle} pour la matière ${selectedEnseignement.libelle}`"
              :headerTitlecolspan="1"/>
        </div>
      </div>
      <Message v-else-if="previSemestreMatiere < 1 || previSemestreMatiere[0].length < 1" severity="error" icon="pi pi-times-circle">
        Aucun prévisionnel pour cette année universitaire avec ce semestre et cette matière
      </Message>
    </div>
  </div>
</template>

