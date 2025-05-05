<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useSemestreStore, useAnneeUnivStore, useUsersStore, useEnseignementsStore } from '@stores';
import { SimpleSkeleton, ListSkeleton } from '@components';
import { getAnneeUnivPreviService, getPersonnelsDepartementService, getPersonnelPreviService, updatePreviService } from '@requests';
import PrevisionnelTable from '@/components/Previsionnel/PrevisionnelTable.vue';
import createApiService from "@requests/apiService.js";
const previService = createApiService('/api/previsionnels');
import apiCall from "@helpers/apiCall.js";

const usersStore = useUsersStore();
const anneeUnivStore = useAnneeUnivStore();
const departementId = usersStore.departementDefaut.id;

const anneesUnivList = ref([]);
const selectedAnneeUniv = ref(null);

const isLoadingAnneesUniv = ref(false);
const isLoadingPrevisionnel = ref(true);
const isLoadingPrevisionnelForm = ref(true);
const isLoadingPersonnel = ref(false);

const personnelList = ref([]);
const selectedPersonnel = ref(null);

const previSemestreAnneeUniv = ref(null);
const previAnneeEnseignant = ref(null);

const size = ref({ label: 'Petit', value: 'small' });
const sizeOptions = ref([
  { label: 'Petit', value: 'small' },
  { label: 'Normal', value: 'null' },
  { label: 'Large', value: 'large' }
]);

const isEditing = ref(false);

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

const getPersonnelsDepartement = async () => {
  isLoadingPersonnel.value = true;
  try {
    personnelList.value = await getPersonnelsDepartementService(departementId);
  } catch (error) {
    console.error('Erreur lors du chargement des personnels:', error);
  } finally {
    // sélectionner le premier personnel de la liste
    if (personnelList.value.length > 0) {
      selectedPersonnel.value = personnelList.value[0];
    } else {
      selectedPersonnel.value = null;
    }
    isLoadingPersonnel.value = false;
    console.log('enseignant : ', selectedPersonnel.value)
  }
};

const getPrevi = async () => {
  isLoadingPrevisionnel.value = true;
  try {
    previSemestreAnneeUniv.value = await getAnneeUnivPreviService(
        departementId,
        selectedAnneeUniv.value.id
    );
  } catch (error) {
    console.error('Erreur lors du chargement des prévisionnels:', error);
  } finally {
    isLoadingPrevisionnel.value = false;
  }
};

const getPreviEnseignant = async () => {
  isLoadingPrevisionnelForm.value = true;
  try {
    previAnneeEnseignant.value = await getPersonnelPreviService(
        departementId,
        selectedAnneeUniv.value.id,
        selectedPersonnel.value.personnel.id
    );
  } catch (error) {
    console.error('Erreur lors du chargement des prévisionnels:', error);
  } finally {
    isLoadingPrevisionnelForm.value = false;
  }
}

onMounted(async () => {
  await getAnneesUniv();
});

watch(selectedAnneeUniv, async (newAnneeUniv) => {
  if (!newAnneeUniv) return;
  await getPrevi(newAnneeUniv.id);
  await getPersonnelsDepartement();
  await getPreviEnseignant();
});

watch(selectedPersonnel , async (newPersonnel) => {
  if (!newPersonnel) return;
  await getPreviEnseignant(newPersonnel.id);
});

const updateHeuresPrevi = async (previId, type, event) => {
  // transforme le nombre d'heures en nombre entier
  if (event === '') {
    event = 0;
  } else {
    event = parseInt(event);
  }
  const heures = event;
  const previ = previAnneeEnseignant.value[0].find((previ) => previ.id === previId);
  console.log('previ : ', previ)
  if (previ) {
    previ.heures[type] = heures;

    await updatePreviService(
        previ.id,
        {
          heures: {
            ...previ.heures,
            [type]: heures
          }
        }
    );
  }
};

const updateGroupesPrevi = async (previId, type, event) => {
  // transforme le nombre de groupes en nombre entier
  if (event === '') {
    event = 0;
  } else {
    event = parseInt(event);
  }
  const groupes = event;
  const previ = previAnneeEnseignant.value[0].find((previ) => previ.id === previId);
  if (previ) {
    previ.groupes[type] = groupes;

    await updatePreviService(
        previ.id,
        {
          groupes: {
            ...previ.groupes,
            [type]: groupes
          }
        }
    );
  }
};

const duplicatePrevi = async (previId) => {
  try {
  const previToDuplicate = previAnneeEnseignant.value[0].find((previ) => previ.id === previId);
    const personnelIri = `/api/personnels/${previToDuplicate.idPersonnel}`;
    const enseignementIri = `/api/scol_enseignements/${previToDuplicate.idEnseignement}`;
    const anneeUnivIri = `/api/structure_annee_universitaires/${selectedAnneeUniv.value.id}`;
    const dataNewPrevi = {
      personnel: personnelIri,
      anneeUniversitaire: anneeUnivIri,
      referent: false,
      heures: {
        CM: previToDuplicate.heures['CM'],
        TD: previToDuplicate.heures['TD'],
        TP: previToDuplicate.heures['TP'],
        Projet: previToDuplicate.heures['Projet'],
      },
      groupes: {
        CM: previToDuplicate.groupes.CM,
        TD: previToDuplicate.groupes.TD,
        TP: previToDuplicate.groupes.TP,
        Projet: previToDuplicate.groupes.Projet,
      },
      enseignement: enseignementIri,
    };

    await apiCall(previService.create,[dataNewPrevi], 'Prévisionnel dupliqué', 'Une erreur est survenue lors de la duplication du prévisionnel');
  } catch (error) {
    console.error('Erreur lors de la duplication du prévisionnel:', error);
  } finally {
    await getPreviEnseignant()
  }
};

// ------------------------------------------------------------------------------------------------------------
// ---------------------------------------SYNTHESE------------------------------------------------
// ------------------------------------------------------------------------------------------------------------

const columns = ref([
  { header: 'Intervenant', field: 'libelle', sortable: true, colspan: 1 },
  {
    header: 'Catégorie',
    field: 'personnel',
    sortable: false,
    colspan: 1,
    tag: true,
    tagClass: (value) => {
      return value.class;
    },
    tagSeverity: (value) => {
      return value.statutSeverity;
    },
    tagIcon: (value) => {
      return value.icon;
    },
    tagContent: (value) => {
      return value.statut;
    }
  },
  { header: 'Service', field: 'service', sortable: true, colspan: 1, unit: ' h' },
  { header: 'CM', field: 'heures.CM', sortable: true, colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
  { header: 'TD', field: 'heures.TD', sortable: true, colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
  { header: 'TP', field: 'heures.TP', sortable: true, colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
  { header: 'Total', field: 'heures.Total', sortable: true, colspan: 1, class: '!text-nowrap', unit: ' h' },
  {
    header: 'Diff.',
    field: 'heures.Diff',
    sortable: false,
    colspan: 1,
    class: '!text-nowrap',
    unit: ' h',
    tag: true,
    tagClass: (value) => {
      if (typeof value === 'string' && value.includes('autre département')) {
        return '!bg-gray-100 !text-gray-800';
      }
      if (typeof value === 'string' && value.includes('Dépassement')) {
        return '!bg-amber-400 !text-white';
      } else {
        return value === 0 ? '!bg-blue-400 !text-white' : (value < 0 ? '!bg-red-400 !text-white' : '!bg-green-400 !text-white');
      }
    },
    tagSeverity: (value) => {
      if (typeof value === 'string' && value.includes('autre département')) {
        return 'secondary';
      }
      if (typeof value === 'string' && value.includes('Dépassement')) {
        return 'warn';
      } else {
        return value === 0 ? 'success' : (value < 0 ? 'warn' : 'success');
      }
    },
    tagIcon: (value) => {
      if (typeof value === 'string' && value.includes('autre département')) {
        return '';
      }
      if (typeof value === 'string' && value.includes('Dépassement')) {
        return 'pi pi-exclamation-triangle';
      } if (typeof value === 'string' && value.includes('Peut rester')) {
        return 'pi pi-check';
      } else {
        return value === 0 ? 'pi pi-check' : (value < 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up');
      }
    }
  },
]);

const topHeaderCols = ref([
]);

const additionalRows = computed(() => [
  [
    { footer: 'Synthèse', colspan: 19, class: '!text-center !font-bold'},
  ],
  [
    { footer: 'Total', colspan: 3 },
    { footer: previSemestreAnneeUniv.value[1].TotalCM, colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
    { footer: previSemestreAnneeUniv.value[1].TotalTD, colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
    { footer: previSemestreAnneeUniv.value[1].TotalTP, colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
    { footer: previSemestreAnneeUniv.value[1].TotalTotal, colspan: 1, class: '!text-nowrap', unit: ' h' },
    { footer: '', colspan: 1 },
  ],
  [
    { footer: 'Répartition', colspan: 19, class: '!text-center !font-bold'},
  ],
  [
    { footer: '', colspan: 3 },
    { footer: 'Permanent', colspan: 1 },
    { footer: 'Vacataire', colspan: 1 },
    { footer: 'Autre', colspan: 1 },
    { footer: '', colspan: 2 },
  ],
  [
    { footer: 'Répartition du total d\'heures entre les catégories', colspan: 3 },
    { footer: previSemestreAnneeUniv.value[2].Permanent, colspan: 1, unit: ' %' },
    { footer: previSemestreAnneeUniv.value[2].Vacataire, colspan: 1, unit: ' %' },
    { footer: previSemestreAnneeUniv.value[2].Autre, colspan: 1, unit: ' %' },
    { footer: '', colspan: 2 },
  ],
]);

const footerCols = computed(() => [
]);

// ------------------------------------------------------------------------------------------------------------
// ---------------------------------------FORMULAIRE------------------------------------------------
// ------------------------------------------------------------------------------------------------------------

const columnsForm = ref([
  { header: 'Matière/ressource/SAE', field: 'libelleEnseignement', sortable: true, colspan: 1, class: '!text-wrap !w-1/3' },

  { header: 'Nb H/Gr.', field: 'heures.CM', colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap', unit: ' h', form: true, formType:'text', id: 'id', type: 'CM', formAction: (previId, type, event) => { updateHeuresPrevi(previId, type, event) } },
  { header: 'Nb Gr.', field: 'groupes.CM', colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap', form: true, formType:'text', id: 'id', type: 'CM', formAction: (previId, type, event) => { updateGroupesPrevi(previId, type, event) } },

  { header: 'Nb H/Gr.', field: 'heures.TD', colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap', unit: ' h', form: true, formType:'text', id: 'id', type: 'TD', formAction: (previId, type, event) => { updateHeuresPrevi(previId, type, event) } },
  { header: 'Nb Gr.', field: 'groupes.TD', colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap', form: true, formType:'text', id: 'id', type: 'TD', formAction: (previId, type, event) => { updateGroupesPrevi(previId, type, event) } },

  { header: 'Nb H/Gr.', field: 'heures.TP', colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap', unit: ' h', form: true, formType:'text', id: 'id', type: 'TP', formAction: (previId, type, event) => { updateHeuresPrevi(previId, type, event) } },
  { header: 'Nb Gr.', field: 'groupes.TP', colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap', form: true, formType:'text', id: 'id', type: 'TP', formAction: (previId, type, event) => { updateGroupesPrevi(previId, type, event) } },

  { header: 'Dupliquer', field: '', colspan: 1, button: true, buttonIcon: 'pi pi-copy', id: 'id', buttonAction: (id) => {duplicatePrevi(id)}, buttonClass: () => '!w-full', buttonSeverity: () => 'warn' },
  { header: 'Supprimer', field: '', colspan: 1, button: true, buttonIcon: 'pi pi-trash', id: 'id', buttonAction: (id) => {deletePrevi(id)}, buttonClass: () => '!w-full', buttonSeverity: () => 'danger' },
]);

const topHeaderColsForm = ref([
  { header: 'CM', colspan: 2, class: '!bg-purple-400 !bg-opacity-20' },
  { header: 'TD', colspan: 2, class: '!bg-green-400 !bg-opacity-20' },
  { header: 'TP', colspan: 2, class: '!bg-amber-400 !bg-opacity-20' },
]);

const additionalRowsForm = computed(() => [
  [
    { footer: 'Synthèse', colspan: 7, class: '!text-center !font-bold'},
  ],
  [
    { footer: 'Total', colspan: 1 },
    { footer: previSemestreAnneeUniv.value[1].TotalCM, colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
    { footer: previSemestreAnneeUniv.value[1].TotalTD, colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
    { footer: previSemestreAnneeUniv.value[1].TotalTP, colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
    { footer: previSemestreAnneeUniv.value[1].TotalTotal, colspan: 1, class: '!text-nowrap', unit: ' h' },
    { footer: '', colspan: 1 },
  ],
]);
</script>

<template>
  <div class="px-4 py-12 flex flex-col gap-6">
    <div class="flex justify-between gap-10">
      <div class="flex gap-6 w-1/2">
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

        <SimpleSkeleton v-if="isLoadingPersonnel" class="w-1/2" />
        <IftaLabel v-else class="w-1/2">
          <Select
              v-model="selectedPersonnel"
              :options="personnelList"
              optionLabel="personnel.display"
              placeholder="Sélectionner un enseignant"
              class="w-full"
          />
          <label for="anneeUniversitaire">Enseignant</label>
        </IftaLabel>
      </div>
      <Button v-if="!isEditing" label="Saisir le prévisionnel" icon="pi pi-plus" @click="isEditing = !isEditing" />
      <Button v-else label="Afficher le prévisionnel" icon="pi pi-eye" @click="isEditing = !isEditing" />
    </div>
    <ListSkeleton v-if="isLoadingPrevisionnel" class="mt-6" />
    <div v-else>

      <div v-if="previSemestreAnneeUniv[0].length > 0">
        <div class="flex w-full justify-between my-6">
          <SelectButton v-model="size" :options="sizeOptions" optionLabel="label" dataKey="label" />
        </div>
        <div v-if="!isEditing">
          <PrevisionnelTable
              origin="previEnseignantsSynthese"
              :columns="columns"
              :topHeaderCols="topHeaderCols"
              :additionalRows="additionalRows"
              :footerCols="footerCols"
              :data="previSemestreAnneeUniv[0]"
              :size="size.value"
              :headerTitle="`Prévisionnel de l'année ${selectedAnneeUniv?.libelle}`"
              :headerTitlecolspan="1"/>
        </div>
        <div v-else>
          <ListSkeleton v-if="isLoadingPrevisionnelForm" class="mt-6" />
          <PrevisionnelTable
              v-else
              origin="previEnseignantForm"
              :columns="columnsForm"
              :topHeaderCols="topHeaderColsForm"
              :additionalRows="additionalRowsForm"
              :footerCols="footerCols"
              :data="previAnneeEnseignant[0]"
              :size="size.value"
              :headerTitle="`${selectedPersonnel.personnel.display} | Prévisionnel de l'année ${selectedAnneeUniv?.libelle}`"
              :headerTitlecolspan="1"/>
        </div>
      </div>
      <Message v-else-if="previAnneeEnseignant[0] < 1" severity="error" icon="pi pi-times-circle">
        Aucun prévisionnel pour cette année universitaire.
      </Message>
    </div>
  </div>
</template>

