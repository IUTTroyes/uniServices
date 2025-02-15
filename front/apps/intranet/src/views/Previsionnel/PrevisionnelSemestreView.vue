<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import {useSemestreStore, useAnneeUnivStore, useUsersStore, useEnseignementsStore} from '@stores';
import { SimpleSkeleton, ListSkeleton } from '@components';
import { getSemestrePreviService, getPersonnelsDepartementService } from '@requests';
import PrevisionnelTable from '@/components/Previsionnel/PrevisionnelTable.vue';

const usersStore = useUsersStore();
const semestreStore = useSemestreStore();
const anneeUnivStore = useAnneeUnivStore();
const enseignementStore = useEnseignementsStore();
const departementId = usersStore.departementDefaut.id;

const semestresList = ref([]);
const selectedSemestre = ref(null);
const semestreDetails = ref(null);
const enseignementsList = ref([]);
const selectedEnseignement = ref(null);
const personnelsList = ref([]);
const selectedPersonnel = ref(null);


const anneesUnivList = ref([]);
const selectedAnneeUniv = ref(null);

const isLoadingSemestres = ref(false);
const isLoadingAnneesUniv = ref(false);
const isLoadingPrevisionnel = ref(true);

const previSemestre = ref(null);

const isEditing = ref(false);

const size = ref({ label: 'Petit', value: 'small' });
const sizeOptions = ref([
  { label: 'Petit', value: 'small' },
  { label: 'Normal', value: 'null' },
  { label: 'Large', value: 'large' }
]);

const searchTerm = ref('');
const filters = ref({
  'libelleEnseignement': { value: null, matchMode: 'contains' }
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
      console.log('enseignements', enseignementsList.value);
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

    console.log('personnels', personnelsList.value);
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
  filters.value['libelleEnseignement'].value = newTerm;
});


// ------------------------------------------------------------------------------------------------------------
// ---------------------------------------SYNTHESE------------------------------------------------
// ------------------------------------------------------------------------------------------------------------

const columns = ref([
  { header: 'Code', field: 'codeEnseignement', sortable: true, colspan: 1 },
  { header: 'Nom', field: 'libelleEnseignement', sortable: true, colspan: 1 },
  { header: 'Type', field: 'typeEnseignement', sortable: true, colspan: 1 },
  { header: 'Nb profs', field: 'personnels.length', colspan: 1 },

  { header: 'Maq.', field: 'heures.CM.Maquette', colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap', unit: ' h'},
  { header: 'Prévi.', field: 'heures.CM.Previsionnel', colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
  { header: 'Diff.', field: 'heures.CM.Diff', sortable: true, colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap', unit: ' h', tag: true, tagClass: (value) => value === 0 ? '!bg-green-400 !text-white' : (value < 0 ? '!bg-amber-400 !text-white' : '!bg-red-400 !text-white'), tagSeverity: (value) => value === 0 ? 'success' : (value < 0 ? 'warn' : 'danger'), tagIcon: (value) => value === 0 ? 'pi pi-check' : (value < 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up') },

  { header: 'Maq.', field: 'heures.TD.Maquette', colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
  { header: 'Prévi.', field: 'heures.TD.Previsionnel', colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
  { header: 'Diff.', field: 'heures.TD.Diff', sortable: true, colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap', unit: ' h', tag: true, tagClass: (value) => value === 0 ? '!bg-green-400 !text-white' : (value < 0 ? '!bg-amber-400 !text-white' : '!bg-red-400 !text-white'), tagSeverity: (value) => value === 0 ? 'success' : (value < 0 ? 'warn' : 'danger'), tagIcon: (value) => value === 0 ? 'pi pi-check' : (value < 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up') },

  { header: 'Maq.', field: 'heures.TP.Maquette', colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
  { header: 'Prévi.', field: 'heures.TP.Previsionnel', colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
  { header: 'Diff.', field: 'heures.TP.Diff', sortable: true, colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap', unit: ' h', tag: true, tagClass: (value) => value === 0 ? '!bg-green-400 !text-white' : (value < 0 ? '!bg-amber-400 !text-white' : '!bg-red-400 !text-white'), tagSeverity: (value) => value === 0 ? 'success' : (value < 0 ? 'warn' : 'danger'), tagIcon: (value) => value === 0 ? 'pi pi-check' : (value < 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up') },

  { header: 'Maq.', field: 'heures.Total.Maquette', colspan: 1, unit: ' h' },
  { header: 'Prévi.', field: 'heures.Total.Previsionnel', colspan: 1, unit: ' h' },
  { header: 'Diff.', field: 'heures.Total.Diff', sortable: true, colspan: 1, unit: ' h', tag: true, tagClass: (value) => value === 0 ? '!bg-green-400 !text-white' : (value < 0 ? '!bg-amber-400 !text-white' : '!bg-red-400 !text-white'), tagSeverity: (value) => value === 0 ? 'success' : (value < 0 ? 'warn' : 'danger'), tagIcon: (value) => value === 0 ? 'pi pi-check' : (value < 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up') },
]);

const topHeaderCols = ref([
  { header: 'CM', colspan: 3, class: '!bg-purple-400 !bg-opacity-20' },
  { header: 'TD', colspan: 3, class: '!bg-green-400 !bg-opacity-20' },
  { header: 'TP', colspan: 3, class: '!bg-amber-400 !bg-opacity-20' },
  { header: 'Total', colspan: 3 }
]);

const footerRows = ref([
  { footer: 'Synthèse', colspan: 19, class: '!text-center !font-bold'},
]);

const footerCols = computed(() => [
  { footer: 'Total', colspan: 4 },
  { footer: previSemestre.value[2].CM.Maquette, colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
  { footer: previSemestre.value[2].CM.Previsionnel, colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
  { footer: previSemestre.value[2].CM.Diff, colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap', unit: ' h', tag: true, tagClass: (value) => value === 0 ? '!bg-green-400 !text-white' : (value < 0 ? '!bg-amber-400 !text-white' : '!bg-red-400 !text-white'), tagSeverity: (value) => value === 0 ? 'success' : (value < 0 ? 'warn' : 'danger'), tagIcon: (value) => value === 0 ? 'pi pi-check' : (value < 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up') },
  { footer: previSemestre.value[2].TD.Maquette, colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
  { footer: previSemestre.value[2].TD.Previsionnel, colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
  { footer: previSemestre.value[2].TD.Diff, colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap', unit: ' h', tag: true, tagClass: (value) => value === 0 ? '!bg-green-400 !text-white' : (value < 0 ? '!bg-amber-400 !text-white' : '!bg-red-400 !text-white'), tagSeverity: (value) => value === 0 ? 'success' : (value < 0 ? 'warn' : 'danger'), tagIcon: (value) => value === 0 ? 'pi pi-check' : (value < 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up') },
  { footer: previSemestre.value[2].TP.Maquette, colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
  { footer: previSemestre.value[2].TP.Previsionnel, colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
  { footer: previSemestre.value[2].TP.Diff, colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap', unit: ' h', tag: true, tagClass: (value) => value === 0 ? '!bg-green-400 !text-white' : (value < 0 ? '!bg-amber-400 !text-white' : '!bg-red-400 !text-white'), tagSeverity: (value) => value === 0 ? 'success' : (value < 0 ? 'warn' : 'danger'), tagIcon: (value) => value === 0 ? 'pi pi-check' : (value < 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up') },
  { footer: previSemestre.value[2].Total.Maquette, colspan: 1, class: '!text-nowrap', unit: ' h' },
  { footer: previSemestre.value[2].Total.Previsionnel, colspan: 1, class: '!text-nowrap', unit: ' h' },
  { footer: previSemestre.value[2].Total.Diff, colspan: 1, unit: ' h', tag: true, tagClass: (value) => value === 0 ? '!bg-green-400 !text-white' : (value < 0 ? '!bg-amber-400 !text-white' : '!bg-red-400 !text-white'), tagSeverity: (value) => value === 0 ? 'success' : (value < 0 ? 'warn' : 'danger'), tagIcon: (value) => value === 0 ? 'pi pi-check' : (value < 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up') },
]);

// ------------------------------------------------------------------------------------------------------------
// ---------------------------------------FORMULAIRE------------------------------------------------
// ------------------------------------------------------------------------------------------------------------

const columnsForm = ref([
  { header: 'Matière', field: 'libelleEnseignement', sortable: true, colspan: 1 },
  { header: 'Intervenant', field: 'intervenant', sortable: true, colspan: 1 },

  { header: 'Nb H/Gr.', field: 'heures.CM.NbHrGrp', colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap', unit: ' h', form: true, formType:'text'},
  { header: 'Nb Gr.', field: 'heures.CM.NbGrp', colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap', form: true, formType:'text' },
  { header: 'Nb Seance/Gr.', field: 'heures.CM.NbSeanceGrp', sortable: false, colspan: 1, class: '!bg-purple-400 !bg-opacity-20', form: false },

  { header: 'Nb H/Gr.', field: 'heures.TD.NbHrGrp', colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap', unit: ' h', form: true, formType:'text' },
  { header: 'Nb Gr.', field: 'heures.TD.NbGrp', colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap', form: true, formType:'text' },
  { header: 'Nb Seance/Gr.', field: 'heures.TD.NbSeanceGrp', sortable: false, colspan: 1, class: '!bg-green-400 !bg-opacity-20', form: false },

  { header: 'Nb H/Gr.', field: 'heures.TP.NbHrGrp', colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap', unit: ' h', form: true, formType:'text' },
  { header: 'Nb Gr.', field: 'heures.TP.NbGrp', colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap', form: true, formType:'text' },
  { header: 'Nb Seance/Gr.', field: 'heures.TP.NbSeanceGrp', sortable: false, colspan: 1, class: '!bg-amber-400 !bg-opacity-20', form: false },
]);

const topHeaderColsForm = ref([
  { header: 'CM', colspan: 3, class: '!bg-purple-400 !bg-opacity-20' },
  { header: 'TD', colspan: 3, class: '!bg-green-400 !bg-opacity-20' },
  { header: 'TP', colspan: 3, class: '!bg-amber-400 !bg-opacity-20' },
]);

const additionalRowsForm = computed(() => [
  [
    { footer: 'Ajouter une entrée au prévisionnel', colspan: 2, class: '!text-center !font-bold'},
    { footer: enseignementsList.value, colspan: 3, form: true, formType: 'select', placeholder: 'Sélectionner une matière' },
    { footer: personnelsList.value, colspan: 3, form: true, formType: 'select', placeholder: 'Sélectionner un intervenant' },
    { footer: 'Ajouter', colspan: 3, button: true, buttonIcon: 'pi pi-plus', buttonAction: () => {}, buttonClass: () => '!w-full', buttonSeverity: () => 'success' },
  ],
]);

const footerRowsForm = ref([
  { footer: 'Synthèse', colspan: 19, class: '!text-center !font-bold'},
]);

const footerColsForm = computed(() => [
  { footer: 'Total', colspan: 2 },
  { footer: previSemestre.value[2].CM.Maquette, colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
  { footer: previSemestre.value[2].CM.Previsionnel, colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
  { footer: previSemestre.value[2].CM.Diff, colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap', unit: ' h', tag: true, tagClass: (value) => value === 0 ? '!bg-green-400 !text-white' : (value < 0 ? '!bg-amber-400 !text-white' : '!bg-red-400 !text-white'), tagSeverity: (value) => value === 0 ? 'success' : (value < 0 ? 'warn' : 'danger'), tagIcon: (value) => value === 0 ? 'pi pi-check' : (value < 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up') },
  { footer: previSemestre.value[2].TD.Maquette, colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
  { footer: previSemestre.value[2].TD.Previsionnel, colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
  { footer: previSemestre.value[2].TD.Diff, colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap', unit: ' h', tag: true, tagClass: (value) => value === 0 ? '!bg-green-400 !text-white' : (value < 0 ? '!bg-amber-400 !text-white' : '!bg-red-400 !text-white'), tagSeverity: (value) => value === 0 ? 'success' : (value < 0 ? 'warn' : 'danger'), tagIcon: (value) => value === 0 ? 'pi pi-check' : (value < 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up') },
  { footer: previSemestre.value[2].TP.Maquette, colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
  { footer: previSemestre.value[2].TP.Previsionnel, colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
  { footer: previSemestre.value[2].TP.Diff, colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap', unit: ' h', tag: true, tagClass: (value) => value === 0 ? '!bg-green-400 !text-white' : (value < 0 ? '!bg-amber-400 !text-white' : '!bg-red-400 !text-white'), tagSeverity: (value) => value === 0 ? 'success' : (value < 0 ? 'warn' : 'danger'), tagIcon: (value) => value === 0 ? 'pi pi-check' : (value < 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up') },
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
      <Button v-if="!isEditing" label="Saisir le prévisionnel" icon="pi pi-plus" @click="isEditing = !isEditing" />
      <Button v-else label="Afficher le prévisionnel" icon="pi pi-eye" @click="isEditing = !isEditing" />
    </div>
    <ListSkeleton v-if="isLoadingPrevisionnel" class="mt-6" />
    <div v-else>
      <div v-if="previSemestre[1].length > 0">
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
        <div v-if="!isEditing">
          <PrevisionnelTable origin="previSemestreSynthese" :columns="columns" :topHeaderCols="topHeaderCols" :footerRows="footerRows" :footerCols="footerCols" :data="previSemestre[1]" :filters="filters" :size="size.value" :headerTitle="`Prévisionnel du semestre ${selectedSemestre?.libelle}`"  :headerTitlecolspan="4"/>
        </div>
        <div v-else>
          <PrevisionnelTable
              origin="previSemestreForm"
              :columns="columnsForm"
              :topHeaderCols="topHeaderColsForm"
              :additionalRows="additionalRowsForm"
              :footerRows="footerRowsForm" :footerCols="footerColsForm"
              :data="previSemestre[0]"
              :filters="filters"
              :size="size.value"
              :headerTitle="`Prévisionnel du semestre ${selectedSemestre?.libelle}`"
              :headerTitlecolspan="2"/>
        </div>
      </div>
      <Message v-else severity="error" icon="pi pi-times-circle">
        Aucun prévisionnel pour cette année universitaire et ce semestre
      </Message>
    </div>
  </div>
</template>
