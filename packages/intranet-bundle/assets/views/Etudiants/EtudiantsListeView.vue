<script setup>
import { ref, onMounted, computed, onUnmounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import ButtonInfo from '@components/components/Buttons/ButtonInfo.vue';
import ButtonEdit from '@components/components/Buttons/ButtonEdit.vue';
import ButtonDelete from '@components/components/Buttons/ButtonDelete.vue';
import {ErrorView, ProfilEtudiant, SimpleSkeleton, HeaderComponent} from '@components';
import { getEtudiantsScolariteService, demissionEtudiantScolariteService, getAnneesService } from '@requests';
import { useToast } from 'primevue/usetoast';
import { useUsersStore, useAnneeUnivStore } from '@stores';
import { useEtudiantFilters } from '../../../../../shared/composables/filters/usersFilters/useEtudiantFilters.ts';

const toast = useToast();
const route = useRoute();
const hasError = ref(false);
const usersStore = useUsersStore();
const anneeUnivStore = useAnneeUnivStore();

const departementId = computed(() => usersStore.departementDefaut ? usersStore.departementDefaut.id : null);
const selectedAnneeUniversitaireId = computed(() => anneeUnivStore.selectedAnneeUniv?.id ?? null);

const anneesList = ref([]);
const isLoadingAnnees = ref(false);
const isLoadingStats = ref(false);

const isInitialLoading = ref(true);

const etudiants = ref([]);
const nbEtudiants = ref(0);
const loading = ref(false);
const page = ref(0);
const rowOptions = [30, 60, 120];

const limit = ref(rowOptions[0]);
const offset = computed(() => limit.value * page.value);
const FILTERS_DEBOUNCE_MS = 250;
let filtersDebounceTimeout = null;

const {filters, watchChanges} = useEtudiantFilters();
watchChanges(async() => {
  if (isInitialLoading.value) return;
  page.value = 0;
  if (filtersDebounceTimeout) {
    clearTimeout(filtersDebounceTimeout);
  }
  filtersDebounceTimeout = setTimeout(() => {
    getEtudiantsScolarite();
  }, FILTERS_DEBOUNCE_MS);
});

watch([departementId, selectedAnneeUniversitaireId], async ([newDepartementId, newAnneeUniversitaireId], [oldDepartementId, oldAnneeUniversitaireId]) => {
  if (isInitialLoading.value) return;

  if (!newDepartementId || !newAnneeUniversitaireId) {
    anneesList.value = [];
    etudiants.value = [];
    nbEtudiants.value = 0;
    return;
  }

  if (newDepartementId === oldDepartementId && newAnneeUniversitaireId === oldAnneeUniversitaireId) {
    return;
  }

  page.value = 0;
  await getAnnees();
  await getEtudiantsScolarite();
});

const showViewDialog = ref(false);
const showEditDialog = ref(false);
const selectedEtudiant = ref(null);

onMounted(async () => {
  isInitialLoading.value = true;
  try {
    await getAnnees();

    // Initialiser le filtre d'année depuis le query parameter si présent
    const anneeFromQuery = route.params.anneeId;
    if (anneeFromQuery) {
      const parsedAnnee = Number.parseInt(String(anneeFromQuery), 10);
      if (!Number.isNaN(parsedAnnee)) {
        filters.value.annee.value = parsedAnnee;
      }
    }

    await getEtudiantsScolarite();
  } catch (error) {
    console.error("Erreur lors de l'initialisation :", error);
    hasError.value = true;
  } finally {
    isInitialLoading.value = false;
  }
});

onUnmounted(() => {
  if (filtersDebounceTimeout) {
    clearTimeout(filtersDebounceTimeout);
  }
});

const getAnnees = async () => {
  isLoadingAnnees.value = true;
  isLoadingStats.value = true;

  if (!departementId.value || !selectedAnneeUniversitaireId.value) {
    anneesList.value = [];
    isLoadingAnnees.value = false;
    isLoadingStats.value = false;
    return;
  }

  try {
    const params = {
      departement: departementId.value,
      anneeUniversitaire: selectedAnneeUniversitaireId.value,
      actif: true,
    };

    const response = await getAnneesService(params, '/liste', false);
    anneesList.value = Array.isArray(response) ? response : [];
  } catch (error) {
    console.error('Erreur lors du chargement des années :', error);
    hasError.value = true;
  } finally {
    isLoadingAnnees.value = false;
    isLoadingStats.value = false;
  }
};

const getEtudiantsScolarite = async () => {
  if (!departementId.value || !selectedAnneeUniversitaireId.value) {
    etudiants.value = [];
    nbEtudiants.value = 0;
    return;
  }

  loading.value = true;
  const paramsListe = {
    departement: departementId.value,
    anneeUniversitaire: selectedAnneeUniversitaireId.value,
    actif: true,
    itemsPerPage: limit.value,
    page: page.value + 1,
    filters: filters.value,
  };
  const paramsCount = {
    departement: departementId.value,
    anneeUniversitaire: selectedAnneeUniversitaireId.value,
    actif: true,
    filters: filters.value,
  };
  try {
    etudiants.value = await getEtudiantsScolariteService(paramsListe, '/liste');
    nbEtudiants.value = await getEtudiantsScolariteService(paramsCount, '/count');
    // transformer le tableau en int
    nbEtudiants.value = Number.parseInt(String(nbEtudiants.value), 10);
  } catch (error) {
    console.error('Erreur lors du chargement des étudiants :', error);
    hasError.value = true;
  } finally {
    loading.value = false;
  }
};

const onPageChange = async event => {
  limit.value = event.rows;
  page.value = event.page;
  await getEtudiantsScolarite();
};

const viewEtudiant = async etudiant => {
  selectedEtudiant.value = etudiant;
  showViewDialog.value = true;
};

const editEtudiant = etudiant => {
  selectedEtudiant.value = etudiant;
  showEditDialog.value = true;
};

const deleteEtudiant = async etudiant => {
  try {
    await demissionEtudiantScolariteService(etudiant.id, true);
    await Promise.all([getEtudiantsScolarite(), getAnnees()]);
  } catch (error) {
    console.error('Erreur lors de la démission de l\'étudiant :', error);
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de marquer l\'étudiant comme démissionnaire. Nous faisons notre possible pour résoudre cette erreur au plus vite.',
      life: 5000,
    });
  }
};
</script>

<template>
  <HeaderComponent
      icon="pi pi-users"
      titre="Liste des étudiants"
      description="Consultez la liste des étudiants inscrits dans le département"
  />

  <div class="flex justify-around items-stretch mb-12">
    <!-- Use a dynamic inline width instead of an invalid Tailwind class that contains a Vue expression -->
    <div v-for="annee in anneesList" :key="annee.id" class="card h-full" :style="{ width: anneesList.length ? (100 / anneesList.length) + '%' : 'auto' }">
      <SimpleSkeleton v-if="isLoadingStats" />
      <div v-else>
        <div class="font-bold text-lg card-header text-center">
          {{ annee.libelle }}
        </div>
        <div class="text-lg font-bold card-body">
          {{annee.etudiantsCount}} étudiant(s)
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <ErrorView v-if="hasError" />
    <div v-else>
      <div class="flex gap-4 w-full pb-6 overflow-x-auto card-header">
        <SimpleSkeleton v-if="isLoadingAnnees" class="w-full" />
        <div v-for="annee in anneesList" :key="annee.id" class="bg-neutral-100 dark:bg-neutral-800 p-4 rounded-lg w-full min-w-48 flex items-center justify-center">
          <SimpleSkeleton v-if="isLoadingStats" />
          <div v-else>
            <div>
              {{ annee.libelle }}
            </div>
            <div class="text-lg font-bold">
              {{annee.etudiantsCount}} étudiant(s)
            </div>
          </div>
        </div>
      </div>

      <DataTable
          scrollHeight="800px"
          scrollable
          v-model:filters="filters"
          :value="etudiants"
          lazy
          stripedRows
          paginator
          :first="offset"
          :rows="limit"
          :rowsPerPageOptions="rowOptions"
          :totalRecords="nbEtudiants"
          dataKey="id"
          filterDisplay="row"
          :loading="loading"
          @page="onPageChange($event)"
          @update:rows="limit = $event"
          :globalFilterFields="['nom', 'prenom']"
      >
        <Column field="nom" :showFilterMenu="false" header="Nom" style="min-width: 12rem">
          <template #body="{ data }">
            {{ data.etudiant.nom }}
          </template>
          <template #filter="{ filterModel, filterCallback }">
            <InputText v-model="filterModel.value" type="text" @input="filterCallback()" placeholder="Filtrer par nom" />
          </template>
        </Column>
        <Column field="prenom" :showFilterMenu="false" header="Prénom" style="min-width: 12rem">
          <template #body="{ data }">
            {{ data.etudiant.prenom }}
          </template>
          <template #filter="{ filterModel, filterCallback }">
            <InputText v-model="filterModel.value" type="text" @input="filterCallback()" placeholder="Filtrer par prénom" />
          </template>
        </Column>
        <Column field="annee" :showFilterMenu="false" header="Année" style="min-width: 12rem">
          <template #body="{ data }">
            <div v-for="annee in data.annee" :key="annee.id">
              {{ annee.libelle }}
            </div>
          </template>
          <template #filter="{ filterModel, filterCallback }">
            <SimpleSkeleton v-if="isLoadingAnnees" class="w-1/3" />
            <Select
                v-else
                v-model="filters.annee.value"
                :show-clear="true"
                :options="anneesList"
                optionLabel="libelle"
                optionValue="id"
                placeholder="Sélectionner une année"
                class="w-full"
            >
              <template #optiongroup="slotProps">
                <div class="border-b">Année : {{ slotProps.option.libelle }}</div>
              </template>
            </Select>
          </template>
        </Column>
        <Column field="mailUniv" :showFilterMenu="false" header="Email" style="min-width: 12rem">
          <template #body="{ data }">
            {{ data.etudiant.mailUniv }}
          </template>
          <template #filter="{ filterModel, filterCallback }">
            <InputText v-model="filterModel.value" type="text" @input="filterCallback()" placeholder="Filtrer par email" />
          </template>
        </Column>
        <Column :showFilterMenu="false" style="min-width: 12rem">
          <template #body="slotProps">
            <ButtonInfo tooltip="Voir les détails de l'étudiant" @click="viewEtudiant(slotProps.data)" />
            <ButtonEdit tooltip="Modifier l'étudiant" @click="editEtudiant(slotProps.data)" />
            <ButtonDelete tooltip="Marquer l'étudiant comme démissionnaire" @confirm-delete="deleteEtudiant(slotProps.data)" />
          </template>
        </Column>
        <template #footer> {{ nbEtudiants }} résultat(s).</template>
      </DataTable>

      <Dialog header=" " :visible="showViewDialog" modal :style="{ width: '90vw' }" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }" dismissable-mask :closable="true"
              :isVisible="showViewDialog"
              @update:visible="showViewDialog = $event">
        <ProfilEtudiant :etudiantId="selectedEtudiant.etudiant.id" :isVisible="showViewDialog" />
      </Dialog>
    </div>
  </div>
</template>

<style scoped></style>
