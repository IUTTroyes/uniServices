<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useSemestreStore, useAnneeUnivStore, useUsersStore, useEnseignementsStore } from '@stores';
import { SimpleSkeleton, ListSkeleton } from '@components';
import { getAnneeUnivPreviService } from '@requests';
import PrevisionnelTable from '@/components/Previsionnel/PrevisionnelTable.vue';

const usersStore = useUsersStore();
const anneeUnivStore = useAnneeUnivStore();
const departementId = usersStore.departementDefaut.id;

const anneesUnivList = ref([]);
const selectedAnneeUniv = ref(null);

const isLoadingAnneesUniv = ref(false);
const isLoadingPrevisionnel = ref(true);

const previSemestreAnneeUniv = ref(null);

const size = ref({ label: 'Petit', value: 'small' });
const sizeOptions = ref([
  { label: 'Petit', value: 'small' },
  { label: 'Normal', value: 'null' },
  { label: 'Large', value: 'large' }
]);

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

const getPrevi = async (anneeUnivId) => {
  if (anneeUnivId) {
    isLoadingPrevisionnel.value = true;
    await anneeUnivStore.getAnneeUniv(anneeUnivId);
    if (selectedAnneeUniv.value) {
      previSemestreAnneeUniv.value = await getAnneeUnivPreviService(
          departementId,
          selectedAnneeUniv.value.id
      );
    }

    console.log('previSemestreAnneeUniv', previSemestreAnneeUniv.value);

    isLoadingPrevisionnel.value = false;
  }
};

onMounted(async () => {
  await getAnneesUniv();
});

watch(selectedAnneeUniv, async (newAnneeUniv) => {
  if (!newAnneeUniv) return;
  await getPrevi(newAnneeUniv.id);
});

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
      </div>
      <Button label="Saisir le prévisionnel" icon="pi pi-plus" />
    </div>
    <ListSkeleton v-if="isLoadingPrevisionnel" class="mt-6" />
    <div v-else>

      <div v-if="previSemestreAnneeUniv[0].length > 0">
        <div class="flex w-full justify-between my-6">
          <SelectButton v-model="size" :options="sizeOptions" optionLabel="label" dataKey="label" />
        </div>
        <PrevisionnelTable
            origin="previMatiereSynthese"
            :columns="columns"
            :topHeaderCols="topHeaderCols"
            :additionalRows="additionalRows"
            :footerCols="footerCols"
            :footerRows="footerRows"
            :data="previSemestreAnneeUniv[0]"
            :size="size.value"
            :headerTitle="`Prévisionnel de l'année ${selectedAnneeUniv?.libelle}`"
            :headerTitlecolspan="1"/>
      </div>
      <Message v-else severity="error" icon="pi pi-times-circle">
        Aucun prévisionnel pour cette année universitaire avec ce semestre et cette matière
      </Message>
    </div>
  </div>
</template>

