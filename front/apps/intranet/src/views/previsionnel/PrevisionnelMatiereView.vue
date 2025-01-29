<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useSemestreStore, useAnneeUnivStore, useUsersStore, useEnseignementsStore } from '@stores';
import { SimpleSkeleton, ListSkeleton } from '@components';
import { getSemestrePreviService, buildSemestrePreviService, calcTotalHeures } from '@requests';
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
const semestreDetails = ref(null);

const anneesUnivList = ref([]);
const selectedAnneeUniv = ref(null);

const isLoadingSemestres = ref(false);
const isLoadingEnseignements = ref(false);
const isLoadingAnneesUniv = ref(false);
const isLoadingPrevisionnel = ref(true);

const previSemestre = ref(null);
const previGrouped = ref(null);

const totalCM = ref([]);
const totalTD = ref([]);
const totalTP = ref([]);
const totalProjet = ref([]);
const totalTotal = ref([]);

const size = ref({ label: 'Petit', value: 'small' });
const sizeOptions = ref([
  { label: 'Petit', value: 'small' },
  { label: 'Normal', value: 'null' },
  { label: 'Large', value: 'large' }
]);

const searchTerm = ref('');
const filters = ref({
  'enseignement.libelle': { value: null, matchMode: 'contains' }
});

const getSemestres = async () => {
  isLoadingSemestres.value = true;
  await semestreStore.getSemestresByDepartement(departementId, true);
  semestresList.value = semestreStore.semestres;
  if (semestresList.value.length > 0) {
    selectedSemestre.value = semestresList.value[0];
  }
  isLoadingSemestres.value = false;
};

const getEnseignements = async () => {
  isLoadingEnseignements.value = true;
  await enseignementStore.getMatieresSemestre(selectedSemestre.value.id);
  EnseignementsList.value = enseignementStore.enseignements;
  if (EnseignementsList.value.length > 0) {
    selectedEnseignement.value = EnseignementsList.value[0];
  }
  isLoadingEnseignements.value = false;
};

const getAnneesUniv = async () => {
  isLoadingAnneesUniv.value = true;
  await anneeUnivStore.getAllAnneesUniv();
  anneesUnivList.value = anneeUnivStore.anneesUniv.sort((a, b) => b.id - a.id);
  await anneeUnivStore.getCurrentAnneeUniv();
  selectedAnneeUniv.value = anneeUnivStore.anneeUniv;
  isLoadingAnneesUniv.value = false;
};

const getPrevi = async (semestreId) => {
  if (semestreId) {
    isLoadingPrevisionnel.value = true;
    await semestreStore.getSemestre(semestreId);
    semestreDetails.value = semestreStore.semestre;

    previSemestre.value = await getSemestrePreviService(selectedSemestre.value.id, selectedAnneeUniv.value.id);

    previSemestre.value.forEach((previ) => {
      previ.total = calcTotalHeures(previ.heures);
    });

    const { previGrouped: grouped, totalCM: cm, totalTD: td, totalTP: tp, totalProjet: projet, totalTotal: total } = await buildSemestrePreviService(previSemestre.value);
    previGrouped.value = grouped;
    totalCM.value = cm;
    totalTD.value = td;
    totalTP.value = tp;
    totalProjet.value = projet;
    totalTotal.value = total;

    isLoadingPrevisionnel.value = false;
  }
};

onMounted(async () => {
  await getSemestres();
  await getEnseignements();
  await getAnneesUniv();
});

watch([selectedSemestre, selectedAnneeUniv], async ([newSemestre, newAnneeUniv]) => {
  if (newSemestre && newAnneeUniv) {
    await getEnseignements();
    await getPrevi(newSemestre.id, newAnneeUniv.id);
  }
});

watch(searchTerm, (newTerm) => {
  filters.value['enseignement.libelle'].value = newTerm;
});

const columns = ref([
  ]);

const topHeaderCols = ref([
  ]);

const footerRows = ref([
  { footer: 'Synthèse', colspan: 19, class: '!text-center !font-bold'},
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
      <div v-if="previGrouped?.length > 0">
        <div class="flex w-full justify-between my-6">
          <SelectButton v-model="size" :options="sizeOptions" optionLabel="label" dataKey="label" />
<!--          <div class="flex justify-end">-->
<!--            <IconField>-->
<!--              <InputIcon>-->
<!--                <i class="pi pi-search" />-->
<!--              </InputIcon>-->
<!--              <InputText v-model="searchTerm" placeholder="Rechercher par matière" />-->
<!--            </IconField>-->
<!--          </div>-->
        </div>
        <PrevisionnelTable origin="previSemestreSynthese" :columns="columns" :topHeaderCols="topHeaderCols" :footerRows="footerRows" :footerCols="footerCols" :data="previGrouped" :filters="filters" :size="size.value" :headerTitle="`Prévisionnel du semestre ${selectedSemestre?.libelle}`" />
      </div>
      <Message v-else severity="error" icon="pi pi-times-circle">
        Aucun prévisionnel pour cette année universitaire et cette matière
      </Message>
    </div>
  </div>
</template>
