<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useSemestreStore, useAnneeUnivStore, useUsersStore, useEnseignementsStore } from '@stores';
import { SimpleSkeleton, ListSkeleton } from '@components';
import { getSemestreEnseignementPreviService, buildSemestreMatierePreviService } from '@requests';
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

const additionalRows = ref([
    [
      { footer: '', colspan: 1 },
      { footer: 'Nb h attendu', colspan: 1, class: '!bg-purple-400 !bg-opacity-20' },
      { footer: 'Nb h saisi', colspan: 1, class: '!bg-purple-400 !bg-opacity-20' },
      { footer: 'Diff.', colspan: 1, class: '!bg-purple-400 !bg-opacity-20' },
      { footer: 'Nb h attendu', colspan: 1, class: '!bg-green-400 !bg-opacity-20' },
      { footer: 'Nb h saisi', colspan: 1, class: '!bg-green-400 !bg-opacity-20' },
      { footer: 'Diff.', colspan: 1, class: '!bg-green-400 !bg-opacity-20' },
      { footer: 'Nb h attendu', colspan: 1, class: '!bg-amber-400 !bg-opacity-20' },
      { footer: 'Nb h saisi', colspan: 1, class: '!bg-amber-400 !bg-opacity-20' },
      { footer: 'Diff.', colspan: 1, class: '!bg-amber-400 !bg-opacity-20' },
    ],
  [
    { footer: 'Vérification du total d’heures par étudiant', colspan: 1 },
    { footer: '18', colspan: 1, class: '!bg-purple-400 !bg-opacity-20' },
    { footer: '18', colspan: 1, class: '!bg-purple-400 !bg-opacity-20' },
    { footer:'0', colspan: 1, class: '!bg-purple-400 !bg-opacity-20' },
    { footer:'18', colspan: 1, class: '!bg-green-400 !bg-opacity-20' },
    { footer: '18', colspan: 1, class: '!bg-green-400 !bg-opacity-20' },
    { footer:'0', colspan: 1, class: '!bg-green-400 !bg-opacity-20' },
    { footer:'18', colspan: 1, class: '!bg-amber-400 !bg-opacity-20' },
    { footer: '18', colspan: 1, class: '!bg-amber-400 !bg-opacity-20' },
    { footer:'0', colspan: 1, class: '!bg-amber-400 !bg-opacity-20' },
  ],
    [
      { footer: 'Synthèse', colspan: 19, class: '!text-center !font-bold' },
    ],
  [
    { footer: 'Total', colspan: 1 },
    { footer:'10', colspan: 3, class: '!bg-purple-400 !bg-opacity-20' },
    { footer: '10', colspan: 3, class: '!bg-green-400 !bg-opacity-20' },
    { footer: '10', colspan: 3, class: '!bg-amber-400 !bg-opacity-20' },
  ],
  [
    { footer: 'Total d\'heures par etudiant', colspan: 1 },
    { footer:'10', colspan: 3, class: '!bg-purple-400 !bg-opacity-20' },
    { footer: '10', colspan: 3, class: '!bg-green-400 !bg-opacity-20' },
    { footer: '10', colspan: 3, class: '!bg-amber-400 !bg-opacity-20' },
  ],
  [
    { footer: 'Total d\'heures équivalent TD', colspan: 1 },
    { footer:'10', colspan: 9, class: '!text-center' },
  ],
]);

const footerRows = ref([
  { footer: '', colspan: 19, class: '!text-center !font-bold' },
]);

const footerCols = computed(() => [

]);
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
      <Button label="Saisir le prévisionnel" icon="pi pi-plus" />
    </div>
    <ListSkeleton v-if="isLoadingPrevisionnel" class="mt-6" />
    <div v-else>
      <Message severity="info" icon="pi pi-info-circle" class="w-fit mx-auto my-2 px-3">
        <div class="flex gap-6">
          Nombre d'heures par étudiant attendu :
          <span class="font-bold">CM : {{ selectedEnseignement.heures.heures.CM.PN }} h</span>
          <span class="font-bold">TD : {{ selectedEnseignement.heures.heures.TD.PN }} h</span>
          <span class="font-bold">TP : {{ selectedEnseignement.heures.heures.TP.PN }} h</span>
        </div>
      </Message>

      <div v-if="previSemestreMatiere[0].length > 0">
        <div class="flex w-full justify-between my-6">
          <SelectButton v-model="size" :options="sizeOptions" optionLabel="label" dataKey="label" />
        </div>
        <PrevisionnelTable
            origin="previMatiereSynthese"
            :columns="columns"
            :topHeaderCols="topHeaderCols"
            :footerCols="footerCols"
            :footerRows="footerRows"
            :data="previSemestreMatiere[0]"
            :filters="filters"
            :size="size.value"
            :headerTitle="`Prévisionnel du semestre ${selectedSemestre?.libelle} pour la matière ${selectedEnseignement.libelle}`"
            :headerTitlecolspan="1"
            :additionalRows="additionalRows"
            :additionalFooterRows="additionalFooterRows"/>
      </div>
      <Message v-else severity="error" icon="pi pi-times-circle">
        Aucun prévisionnel pour cette année universitaire avec ce semestre et cette matière
      </Message>
    </div>
  </div>
</template>

