<script setup>
import {computed, onMounted, ref, watch} from 'vue';
import {ErrorView, ListSkeleton} from "@components";
import {useToast} from "primevue/usetoast";
import {useAnneeStore, useEnseignementsStore, useUsersStore} from "@stores";
import {
  getPersonnelPreviService,
  getAnneeUnivPreviService
} from "@requests";
import PrevisionnelTable from "@/components/Previsionnel/PrevisionnelTable.vue";

const toast = useToast();
const hasError = ref(false);
const anneeUniv = localStorage.getItem('selectedAnneeUniv') ? JSON.parse(localStorage.getItem('selectedAnneeUniv')) : { id: null };
const usersStore = useUsersStore();
const enseignementStore = useEnseignementsStore();
const departementId = usersStore.departementDefaut.id;
const isLoadingPrevisionnel = ref(true);
const previPersonnels = ref([]);
const previPersonnelsOriginal = ref([]);
const isEditing = ref(false);
const size = ref({ label: 'Petit', value: 'small' });
const sizeOptions = ref([
  { label: 'Petit', value: 'small' },
  { label: 'Normal', value: 'null' },
  { label: 'Large', value: 'large' }
]);
const searchTerm = ref('');
const filters = ref({
  'libelle': { value: null, matchMode: 'contains' }
});
watch(searchTerm, (newTerm) => {
  filters.value['libelle'].value = newTerm;
});

onMounted(async () => {
  await getPreviPersonnels();
});

const getPreviPersonnels = async () => {
  try {
    const params = {
      departement: departementId,
      anneeUniversitaire: anneeUniv.id,
    }
    previPersonnels.value = await getAnneeUnivPreviService(params);
    previPersonnelsOriginal.value = JSON.parse(JSON.stringify(previPersonnels.value));

  } catch (error) {
    hasError.value = true;
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors du chargement des données.' });
  } finally {
    console.log(previPersonnels.value)
    isLoadingPrevisionnel.value = false;
  }
}

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
  { header: 'CM', field: 'heures.CM', sortable: true, colspan: 1, class: '!bg-purple-400/20 !text-nowrap', unit: ' h' },
  { header: 'TD', field: 'heures.TD', sortable: true, colspan: 1, class: '!bg-green-400/20 !text-nowrap', unit: ' h' },
  { header: 'TP', field: 'heures.TP', sortable: true, colspan: 1, class: '!bg-amber-400/20 !text-nowrap', unit: ' h' },
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
  { header: '', colspan: 7 }
]);

const additionalRows = computed(() => [
  [
    { footer: 'Synthèse', colspan: 19, class: '!text-center !font-bold'},
  ],
  [
    { footer: 'Total', colspan: 3, class: 'font-bold' },
    { footer: previPersonnels.value[1].TotalCM, colspan: 1, class: '!bg-purple-400/20 !text-nowrap', unit: ' h' },
    { footer: previPersonnels.value[1].TotalTD, colspan: 1, class: '!bg-green-400/20 !text-nowrap', unit: ' h' },
    { footer: previPersonnels.value[1].TotalTP, colspan: 1, class: '!bg-amber-400/20 !text-nowrap', unit: ' h' },
    { footer: previPersonnels.value[1].TotalTotal, colspan: 1, class: '!text-nowrap', unit: ' h' },
    { footer: '', colspan: 1 },
  ],
  [
    { footer: 'Répartition', colspan: 19, class: '!text-center !font-bold'},
  ],
  [
    { footer: '', colspan: 3 },
    { footer: 'Permanent', colspan: 2, class: 'font-bold' },
    { footer: 'Vacataire', colspan: 2, class: 'font-bold' },
    { footer: 'Autre', colspan: 2, class: 'font-bold' },
  ],
  [
    { footer: 'Répartition du total d\'heures entre les catégories', colspan: 3 },
    { footer: previPersonnels.value[2].Permanent, colspan: 2, unit: ' %' },
    { footer: previPersonnels.value[2].Vacataire, colspan: 2, unit: ' %' },
    { footer: previPersonnels.value[2].Autre, colspan: 2, unit: ' %' },
  ],
]);

const footerCols = computed(() => [
]);
</script>

<template>
  <ErrorView v-if="hasError" />
  <div v-else class="px-4 flex flex-col">
    <ListSkeleton v-if="isLoadingPrevisionnel" class="mt-6" />
    <div v-else>
      <div class="flex w-full justify-between my-6">
        <SelectButton v-model="size" :options="sizeOptions" optionLabel="label" dataKey="label" />
        <div class="flex justify-end">
          <IconField>
            <InputIcon>
              <i class="pi pi-search" />
            </InputIcon>
            <InputText v-model="searchTerm" placeholder="Rechercher par enseignant" />
          </IconField>
        </div>
      </div>
      <div v-if="!isEditing"></div>
      <PrevisionnelTable
          origin="previEnseignantsSynthese"
          :columns="columns"
          :topHeaderCols="topHeaderCols"
          :additionalRows="additionalRows"
          :footerCols="footerCols"
          :filters="filters"
          :data="previPersonnels[0]"
          :size="size.value"
          :headerTitle="`Prévisionnel de l'année`"
          :headerTitlecolspan="1"/>
    </div>
  </div>
</template>
