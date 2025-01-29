<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useSemestreStore, useAnneeUnivStore, useUsersStore } from '@stores';
import { SimpleSkeleton, ListSkeleton } from '@components';
import { getSemestrePreviService, buildSemestrePreviService, calcTotalHeures } from '@requests';
import PrevisionnelTable from '@/components/Previsionnel/PrevisionnelTable.vue';

const usersStore = useUsersStore();
const semestreStore = useSemestreStore();
const anneeUnivStore = useAnneeUnivStore();
const departementId = usersStore.departementDefaut.id;

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

const totalCM = ref([]);
const totalTD = ref([]);
const totalTP = ref([]);
const totalTotal = ref([]);

const size = ref({ label: 'Normal', value: 'null' });
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

const getPrevi = async (semestreId) => {
  if (semestreId) {
    isLoadingPrevisionnel.value = true;
    await semestreStore.getSemestre(semestreId);
    semestreDetails.value = semestreStore.semestre;

    previSemestre.value = await getSemestrePreviService(selectedSemestre.value.id, selectedAnneeUniv.value.id);

    previSemestre.value.forEach((previ) => {
      previ.total = calcTotalHeures(previ.heures);
    });

    previGrouped.value = (await buildSemestrePreviService(previSemestre.value)).previGrouped;
    console.log('previGrouped', previGrouped.value);


    isLoadingPrevisionnel.value = false;
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

watch(searchTerm, (newTerm) => {
  filters.value['enseignement.libelle'].value = newTerm;
});

const columns = ref([
  { header: 'Code', field: 'enseignement.codeEnseignement', sortable: true, colspan: 1 },
  { header: 'Nom', field: 'enseignement.libelle', sortable: true, colspan: 1 },
  { header: 'Type', field: 'enseignement.type', sortable: true, colspan: 1 },
  { header: 'Nb profs', field: 'personnel.length', colspan: 1 },
  { header: 'Maq.', field: 'heures.CM.Maquette', colspan: 1, class: '!bg-purple-400 !bg-opacity-20' },
  { header: 'Prévi.', field: 'heures.CM.Previ', colspan: 1, class: '!bg-purple-400 !bg-opacity-20' },
  { header: 'Diff.', field: 'heures.CM.Diff', sortable: true, colspan: 1, class: '!bg-purple-400 !bg-opacity-20', tag: true, tagClass: (value) => value === 0 ? '!bg-green-400 !text-white' : (value < 0 ? '!bg-amber-400 !text-white' : '!bg-red-400 !text-white'), tagSeverity: (value) => value === 0 ? 'success' : (value < 0 ? 'warn' : 'danger'), tagIcon: (value) => value === 0 ? 'pi pi-check' : (value < 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up') },
  { header: 'Maq.', field: 'heures.TD.Maquette', colspan: 1, class: '!bg-green-400 !bg-opacity-20' },
  { header: 'Prévi.', field: 'heures.TD.Previ', colspan: 1, class: '!bg-green-400 !bg-opacity-20' },
  { header: 'Diff.', field: 'heures.TD.Diff', sortable: true, colspan: 1, class: '!bg-green-400 !bg-opacity-20', tag: true, tagClass: (value) => value === 0 ? '!bg-green-400 !text-white' : (value < 0 ? '!bg-amber-400 !text-white' : '!bg-red-400 !text-white'), tagSeverity: (value) => value === 0 ? 'success' : (value < 0 ? 'warn' : 'danger'), tagIcon: (value) => value === 0 ? 'pi pi-check' : (value < 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up') },
  { header: 'Maq.', field: 'heures.TP.Maquette', colspan: 1, class: '!bg-amber-400 !bg-opacity-20' },
  { header: 'Prévi.', field: 'heures.TP.Previ', colspan: 1, class: '!bg-amber-400 !bg-opacity-20' },
  { header: 'Diff.', field: 'heures.TP.Diff', sortable: true, colspan: 1, class: '!bg-amber-400 !bg-opacity-20', tag: true, tagClass: (value) => value === 0 ? '!bg-green-400 !text-white' : (value < 0 ? '!bg-amber-400 !text-white' : '!bg-red-400 !text-white'), tagSeverity: (value) => value === 0 ? 'success' : (value < 0 ? 'warn' : 'danger'), tagIcon: (value) => value === 0 ? 'pi pi-check' : (value < 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up') },
  { header: 'Maq.', field: 'heures.Projet.Maquette', colspan: 1, class: '!bg-cyan-400 !bg-opacity-20' },
  { header: 'Prévi.', field: 'heures.Projet.Previ', colspan: 1, class: '!bg-cyan-400 !bg-opacity-20' },
  { header: 'Diff.', field: 'heures.Projet.Diff', sortable: true, colspan: 1, class: '!bg-cyan-400 !bg-opacity-20', tag: true, tagClass: (value) => value === 0 ? '!bg-green-400 !text-white' : (value < 0 ? '!bg-amber-400 !text-white' : '!bg-red-400 !text-white'), tagSeverity: (value) => value === 0 ? 'success' : (value < 0 ? 'warn' : 'danger'), tagIcon: (value) => value === 0 ? 'pi pi-check' : (value < 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up') },
  { header: 'Maq.', field: 'heures.Total.Maquette', colspan: 1 },
  { header: 'Prévi.', field: 'heures.Total.Previ', colspan: 1 },
  { header: 'Diff.', field: 'heures.Total.Diff', sortable: true, colspan: 1, tag: true, tagClass: (value) => value === 0 ? '!bg-green-400 !text-white' : (value < 0 ? '!bg-amber-400 !text-white' : '!bg-red-400 !text-white'), tagSeverity: (value) => value === 0 ? 'success' : (value < 0 ? 'warn' : 'danger'), tagIcon: (value) => value === 0 ? 'pi pi-check' : (value < 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up') },
]);

const topHeaderCols = ref([
  { header: 'CM', colspan: 3, class: '!bg-purple-400 !bg-opacity-20' },
  { header: 'TD', colspan: 3, class: '!bg-green-400 !bg-opacity-20' },
  { header: 'TP', colspan: 3, class: '!bg-amber-400 !bg-opacity-20' },
  { header: 'Projet', colspan: 3, class: '!bg-cyan-400 !bg-opacity-20' },
  { header: 'Total', colspan: 3 }
]);

const footerRows = ref([
  { footer: 'Synthèse', colspan: 16, class: '!text-center !font-bold'},
]);


//todo: passer dans le service :)
// faire le total des CM Maquette, Prévi, Diff
totalCM.value = computed(() => {
  const totalMaq = previGrouped.value.reduce((acc, previ) => acc + previ.heures.CM.Maquette, 0);
  const totalPrev = previGrouped.value.reduce((acc, previ) => acc + previ.heures.CM.Previ, 0);
  const totalDiff = previGrouped.value.reduce((acc, previ) => acc + previ.heures.CM.Diff, 0);
  return [totalMaq, totalPrev, totalDiff];
});

// faire le total des TD Maquette, Prévi, Diff
totalTD.value = computed(() => {
  const totalMaq = previGrouped.value.reduce((acc, previ) => acc + previ.heures.TD.Maquette, 0);
  const totalPrev = previGrouped.value.reduce((acc, previ) => acc + previ.heures.TD.Previ, 0);
  const totalDiff = previGrouped.value.reduce((acc, previ) => acc + previ.heures.TD.Diff, 0);
  return [totalMaq, totalPrev, totalDiff];
});

// faire le total des TP Maquette, Prévi, Diff
totalTP.value = computed(() => {
  const totalMaq = previGrouped.value.reduce((acc, previ) => acc + previ.heures.TP.Maquette, 0);
  const totalPrev = previGrouped.value.reduce((acc, previ) => acc + previ.heures.TP.Previ, 0);
  const totalDiff = previGrouped.value.reduce((acc, previ) => acc + previ.heures.TP.Diff, 0);
  return [totalMaq, totalPrev, totalDiff];
});

// faire le total des Total Maquette, Prévi, Diff
totalTotal.value = computed(() => {
  const totalMaq = previGrouped.value.reduce((acc, previ) => acc + previ.heures.Total.Maquette, 0);
  const totalPrev = previGrouped.value.reduce((acc, previ) => acc + previ.heures.Total.Previ, 0);
  const totalDiff = previGrouped.value.reduce((acc, previ) => acc + previ.heures.Total.Diff, 0);
  return [totalMaq, totalPrev, totalDiff];
});

// console.log('totalCM', totalCM.value);
const footerCols = ref([
  { footer: 'Total', colspan: 4 },
  { footer: totalCM.value[0], colspan: 1, class: '!bg-purple-400 !bg-opacity-20' },
  { footer: totalCM.value[1], colspan: 1, class: '!bg-purple-400 !bg-opacity-20' },
  { footer: totalCM.value[2], colspan: 1, class: '!bg-purple-400 !bg-opacity-20' },
  { footer: totalTD.value[0], colspan: 1, class: '!bg-green 400 !bg-opacity-20' },

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
          <div class="flex justify-end">
            <IconField>
              <InputIcon>
                <i class="pi pi-search" />
              </InputIcon>
              <InputText v-model="searchTerm" placeholder="Rechercher par matière" />
            </IconField>
          </div>
        </div>
        <PrevisionnelTable origin="previSemestreSynthese" :columns="columns" :topHeaderCols="topHeaderCols" :footerRows="footerRows" :footerCols="footerCols" :data="previGrouped" :filters="filters" :size="size.value" :headerTitle="`Prévisionnel du semestre ${selectedSemestre?.libelle}`" />
      </div>
      <Message v-else severity="error" icon="pi pi-times-circle">
        Aucun prévisionnel pour cette année universitaire et ce semestre
      </Message>
    </div>
  </div>
</template>
