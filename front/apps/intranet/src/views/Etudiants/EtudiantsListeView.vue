<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { FilterMatchMode } from '@primevue/core/api';
import ButtonInfo from '@components/components/Buttons/ButtonInfo.vue';
import ButtonEdit from '@components/components/Buttons/ButtonEdit.vue';
import ButtonDelete from '@components/components/Buttons/ButtonDelete.vue';
import { ErrorView } from '@components';
import { getDepartementAnneesService, getEtudiantsScolaritesDepartementService, getEtudiantsScolaritesAnneeService } from '@requests';

import ViewEtudiantDialog from '@/dialogs/etudiants/ViewEtudiantDialog.vue';
import EditEtudiantDialog from '@/dialogs/etudiants/EditEtudiantDialog.vue';

import AccessEtudiantDialog from '@/dialogs/etudiants/AccessEtudiantDialog.vue';
import { useToast } from 'primevue/usetoast';

const toast = useToast();

import { useSemestreStore, useUsersStore } from '@stores';
import { SimpleSkeleton } from '@components';
const usersStore = useUsersStore();

const departementId = ref(null);
const anneesList = ref([]);

const isLoadingAnnees = ref(false);
const isLoadingStats = ref(false);

const isUpdatingFilter = ref(false);
const hasError = ref(false);

const etudiants = ref([]);
const nbEtudiants = ref(0);
const loading = ref(true);
const page = ref(0);
const rowOptions = [30, 60, 120];

const limit = ref(rowOptions[0]);
const offset = computed(() => limit.value * page.value);

const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS },
  nom: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
  prenom: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
  mailUniv: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
  annee: { value: null, matchMode: FilterMatchMode.EQUALS },
});

const showViewDialog = ref(false);
const showEditDialog = ref(false);
const showAccessEditDialog = ref(false);
const selectedEtudiant = ref(null);

const selectedAnneeUniversitaire = JSON.parse(localStorage.getItem('selectedAnneeUniv'));

const getAnnees = async () => {
  isLoadingAnnees.value = true;
  isLoadingStats.value = true;
  try {
    anneesList.value = await getDepartementAnneesService(departementId.value, true);
  } catch (error) {
    console.error('Erreur lors du chargement des années :', error);
    hasError.value = true;
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de charger les années. Nous faisons notre possible pour résoudre cette erreur au plus vite.',
      life: 5000,
    });
  } finally {
    isLoadingAnnees.value = false;

    try {
      // pour chaque anneeList
      for (const annee of anneesList.value) {
        const etudiantsCount = await getEtudiantsAnnee(annee.id);
        annee.etudiantsCount = etudiantsCount.totalItems;
      }
    } catch (error) {
      console.error('Erreur lors de la sélection de l\'année par défaut :', error);
    } finally {
      isLoadingStats.value = false;
    }
  }
};

const getEtudiantsAnnee = async anneeId => {
  return await getEtudiantsScolaritesAnneeService(anneeId, false, true);
};

const getEtudiantsScolarite = async () => {
  loading.value = true;
  try {
    const response = await getEtudiantsScolaritesDepartementService(
        departementId.value,
        selectedAnneeUniversitaire.id,
        limit.value,
        parseInt(page.value) + 1,
        filters.value
    );
    etudiants.value = response.member;
    nbEtudiants.value = response.totalItems;

    etudiants.value.forEach(etudiant => {
      etudiant.annees = [
        ...new Set(
            etudiant.annee
        ),
      ];
    });
  } catch (error) {
    console.error('Erreur lors du chargement des étudiants :', error);
    hasError.value = true;
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de charger les étudiants. Nous faisons notre possible pour résoudre cette erreur au plus vite.',
      life: 5000,
    });
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  departementId.value = usersStore.departementDefaut.id;
  await getAnnees();
  await getEtudiantsScolarite();
});

const onPageChange = async event => {
  limit.value = event.rows;
  page.value = event.page;
  await getEtudiantsScolarite();
};

const viewEtudiant = etudiant => {
  selectedEtudiant.value = etudiant;
  showViewDialog.value = true;
};

const editEtudiant = etudiant => {
  selectedEtudiant.value = etudiant;
  showEditDialog.value = true;
};

const deleteEtudiant = etudiant => {
  console.log(etudiant);
};

watch(
      () => ({ ...filters.value }),
      async (newFilters, oldFilters) => {
        if (isUpdatingFilter.value) {
          isUpdatingFilter.value = false;
          return;
        }

        // Gestion spécifique pour le filtre année (objet ou id)
        const newAnnee = newFilters.annee.value;
        if (newAnnee && typeof newAnnee === 'object' && newAnnee.id) {
          isUpdatingFilter.value = true;
          filters.value.annee.value = newAnnee.id;
          await getEtudiantsScolarite();
        } else if (typeof newAnnee === 'number') {
          await getEtudiantsScolarite();
        } else if (newAnnee === null || newAnnee === undefined) {
          await getEtudiantsScolarite();
        } else {
          await getEtudiantsScolarite();
        }
        // Détection de changement sur les autres filtres
        if (
          newFilters.nom.value !== oldFilters.nom.value ||
          newFilters.prenom.value !== oldFilters.prenom.value ||
          newFilters.mailUniv.value !== oldFilters.mailUniv.value ||
          newFilters.global.value !== oldFilters.global.value
        ) {
          await getEtudiantsScolarite();
        }
      },
      { deep: true }
    );
</script>

<template>
  <ErrorView v-if="hasError" />
  <div v-else class="card">
    <h2 class="text-2xl font-bold mb-4">Tous les étudiants inscrits dans le département</h2>

    <div class="flex gap-4 w-full pb-6 overflow-x-auto">
      <SimpleSkeleton v-if="isLoadingAnnees" class="w-full" />
      <div v-for="annee in anneesList" :key="annee.id" class="bg-neutral-300 bg-opacity-20 p-4 rounded-lg w-full min-w-48 flex items-center justify-center">
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
      <Column field="annees" :showFilterMenu="false" header="Année" style="min-width: 12rem">
        <template #body="{ data }">
          <div v-for="annee in data.annees" :key="annee">
            {{ annee.libelle }}
          </div>
        </template>
        <template #filter="{ filterModel, filterCallback }">
          <SimpleSkeleton v-if="isLoadingAnnees" class="w-1/3" />
          <Select
              v-else
              v-model="filters.annee.value"
              :options="anneesList"
              optionLabel="libelle"
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

    <ViewEtudiantDialog
        :isVisible="showViewDialog"
        :etudiantSco="selectedEtudiant"
        @update:visible="showViewDialog = $event"
    />
    <EditEtudiantDialog
        :isVisible="showEditDialog"
        :etudiant="selectedEtudiant"
        @update:visible="showEditDialog = $event"
    />
    <AccessEtudiantDialog
        :isVisible="showAccessEditDialog"
        :etudiant="selectedEtudiant"
        @update:visible="showAccessEditDialog = $event"
    />
  </div>
</template>

<style scoped></style>
