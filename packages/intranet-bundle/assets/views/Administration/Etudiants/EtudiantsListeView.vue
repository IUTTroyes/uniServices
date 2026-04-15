<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import ButtonInfo from '@components/components/Buttons/ButtonInfo.vue';
import ButtonEdit from '@components/components/Buttons/ButtonEdit.vue';
import ButtonDelete from '@components/components/Buttons/ButtonDelete.vue';
import {ErrorView, ProfilEtudiant} from '@components';
import { getEtudiantsScolariteService, demissionEtudiantScolariteService } from '@requests';
import { useToast } from 'primevue/usetoast';
import { useUsersStore, useAnneeStore, useAnneeUnivStore } from '@stores';
import { SimpleSkeleton } from '@components';
import { useEtudiantFilters } from '@composables/filters/usersFilters/useEtudiantFilters.ts';

const toast = useToast();
const route = useRoute();
const hasError = ref(false);
const usersStore = useUsersStore();
const anneeStore = useAnneeStore();
const anneeUnivStore = useAnneeUnivStore()

const departementId = computed(() => usersStore.departementDefaut ? usersStore.departementDefaut.id : null);

const anneesList = ref([]);
const isLoadingAnnees = ref(false);
const isLoadingStats = ref(false);

const isInitialLoading = ref(true);

const etudiants = ref([]);
const nbEtudiants = ref(0);
const loading = ref(true);
const page = ref(0);
const rowOptions = [30, 60, 120];

const limit = ref(rowOptions[0]);
const offset = computed(() => limit.value * page.value);

const {filters, watchChanges} = useEtudiantFilters();
watchChanges(async() => {
  if (isInitialLoading.value) return;
  page.value = 0;
  await getEtudiantsScolarite();
});

const showViewDialog = ref(false);
const showEditDialog = ref(false);
const selectedEtudiant = ref(null);

const selectedAnneeUniversitaire = anneeUnivStore.selectedAnneeUniv;

onMounted(async () => {
  isInitialLoading.value = true;
  try {
    departementId.value = usersStore.departementDefaut.id;
    await getAnnees();

    // Initialiser le filtre d'année depuis le query parameter si présent
    const anneeFromQuery = route.query.annee;
    if (anneeFromQuery) {
      filters.value.annee.value = parseInt(anneeFromQuery);
    }
  } catch (error) {
    console.error("Erreur lors de l'initialisation :", error);
    hasError.value = true;
  } finally {
    isInitialLoading.value = false;
  }
});

const getAnnees = async () => {
  isLoadingAnnees.value = true;
  isLoadingStats.value = true;
  if (anneeStore.annees && Array.isArray(anneeStore.annees) && anneeStore.annees.length > 0) {
    anneesList.value = anneeStore.annees;
    isLoadingAnnees.value = false;
    fetchAnneesStats();
  } else {
    try {
      const params = {
        departement: departementId.value,
        actif: true,
      };
      await anneeStore.getAnneesDepartement(params);
      anneesList.value = Array.isArray(anneeStore.annees) ? anneeStore.annees : [];
    } catch (error) {
      console.error('Erreur lors du chargement des années :', error);
      hasError.value = true;
    } finally {
      isLoadingAnnees.value = false;
      // Charger les stats de manière asynchrone sans bloquer
      fetchAnneesStats();
    }
  }
};

const fetchAnneesStats = async () => {
  isLoadingStats.value = true;
  try {
    const requests = anneesList.value.map(async (annee) => {
      try {
        const response = await getEtudiantsAnnee(annee.id);
        annee.etudiantsCount = response.totalItems;
      } catch (e) {
        console.error(`Erreur stats pour l'année ${annee.id}:`, e);
        annee.etudiantsCount = 0;
      }
    });
    await Promise.all(requests);
  } catch (error) {
    console.error('Erreur lors du chargement des statistiques :', error);
  } finally {
    isLoadingStats.value = false;
  }
};

const getEtudiantsAnnee = async (anneeId) => {
  const params = {
    departement: departementId.value,
    anneeUniversitaire: selectedAnneeUniversitaire.id,
    annee: anneeId,
    actif: true,
    limit: 1,
    page: 1,
    filters: {},
  };
  return await getEtudiantsScolariteService(params, '/mini', false);
};

const getEtudiantsScolarite = async () => {
  loading.value = true;
  const params = {
    departement: departementId.value,
    anneeUniversitaire: selectedAnneeUniversitaire.id,
    actif: true,
    itemsPerPage: limit.value,
    page: parseInt(page.value) + 1,
    filters: filters.value,
  };
  try {
    const response = await getEtudiantsScolariteService(params, '/administration');
    nbEtudiants.value = response.totalItems;
    etudiants.value = response.member;
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

const deleteEtudiant = etudiant => {
 try {
   demissionEtudiantScolariteService(etudiant.id, true);
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
  <div class="card">
    <h2 class="text-2xl! font-bold mb-4">Tous les étudiants inscrits dans le département</h2>

    <ErrorView v-if="hasError" />
    <div v-else>
      <div class="flex gap-4 w-full pb-6 overflow-x-auto">
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
        <Column field="anneeUniversitaire"></Column>
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
