<script setup>
import { ref, onMounted, computed, onUnmounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import ButtonInfo from '@components/components/Buttons/ButtonInfo.vue';
import ButtonEdit from '@components/components/Buttons/ButtonEdit.vue';
import ButtonDelete from '@components/components/Buttons/ButtonDelete.vue';
import {ProfilEtudiant, SimpleSkeleton, HeaderComponent, ApplicationBadge, EtudiantApplicationsDialog} from '@components';
import { getEtudiantsScolariteService, demissionEtudiantScolariteService, getAnneesService } from '@requests';
import { useToast } from 'primevue/usetoast';
import { useUsersStore, useAnneeUnivStore } from '@stores';
import { getAppTagInfo } from '@utils';
import { useEtudiantFilters } from '@composables/filters/usersFilters/useEtudiantFilters';
import { Button } from 'primevue';

const toast = useToast();
const route = useRoute();
const router = useRouter();
const hasError = ref(false);
const usersStore = useUsersStore();
const anneeUnivStore = useAnneeUnivStore();

const departementId = computed(() => usersStore.departementDefaut ? usersStore.departementDefaut.id : null);
const selectedAnneeUniversitaireId = computed(() => anneeUnivStore.selectedAnneeUniv?.id ?? null);

const anneesList = ref([]);
const isLoadingAnnees = ref(false);
const isLoadingStats = ref(false);
const annee = computed(() => {
  return anneesList.value.find(a => a.id === filters.value.annee.value) || null;
});

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

watch(() => filters.value.annee.value, async (newAnneeId, oldAnneeId) => {
  if (isInitialLoading.value) return;
  if (newAnneeId === oldAnneeId) return;

  const normalized = newAnneeId ? String(newAnneeId) : undefined;
  const current = route.params.anneeId ? String(route.params.anneeId) : undefined;
  if (normalized === current) return;

  const nextParams = {
    ...route.params,
  };

  if (normalized) {
    nextParams.anneeId = normalized;
  } else {
    delete nextParams.anneeId;
  }

  await router.replace({
    name: route.name,
    params: nextParams,
    query: route.query,
  });
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
const selectedScolarites = ref([]);
const showApplicationsDialog = ref(false);
const dialogEtudiants = ref([]);
const dialogInitialMode = ref('selection');

const currentAnnee = computed(() => {
  return anneesList.value.find(a => a.id === filters.value.annee?.value) || null;
});

const openStudentApplications = (scolarite) => {
  dialogEtudiants.value = [scolarite];
  dialogInitialMode.value = 'selection';
  showApplicationsDialog.value = true;
};

const openBulkApplications = () => {
  dialogEtudiants.value = selectedScolarites.value;
  dialogInitialMode.value = 'selection';
  showApplicationsDialog.value = true;
};

const openPromoApplications = () => {
  dialogEtudiants.value = [];
  dialogInitialMode.value = 'promo';
  showApplicationsDialog.value = true;
};

const onApplicationsSaved = async () => {
  selectedScolarites.value = [];
  await getEtudiantsScolarite();
};


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
  >
    <template #actions>
      <div class="flex items-center gap-2">
        <Button
          label="Affectation par promotion"
          icon="pi pi-sliders-h"
          severity="secondary"
          outlined
          size="small"
          @click="openPromoApplications"
        />
        <Button
          v-if="selectedScolarites.length > 0"
          :label="`Gérer les applications (${selectedScolarites.length})`"
          icon="pi pi-th-large"
          severity="primary"
          size="small"
          @click="openBulkApplications"
        />
      </div>
    </template>
  </HeaderComponent>

  <div class="card">
    <header class="card-header flex justify-between items-center w-full mb-6">
      <div>
        <p class="top-card-header">
          Liste
        </p>
        <div class="flex flex-col items-start">
          <p v-if="annee" class="uppercase text-xs font-bold mb-0! text-muted-color">
            {{ annee.libelle }}
          </p>
          <p v-else class="uppercase text-xs font-bold mb-0! text-muted-color">
            Toutes les années
          </p>
          <h2 class="mt-0!">
            Étudiants inscrits dans le département
          </h2>
        </div>
      </div>
      <div class="flex justify-end items-center">
        <router-link :to="{ name: 'ajout-etudiants-sans-scolarite' }">
          <Button label="Voir les étudiants sans scolarité" icon="pi pi-eye" severity="primary" />
        </router-link>
      </div>
    </header>
    <div class="card-body">
      <!-- Bulk Selection Toolbar -->
      <div
        v-if="selectedScolarites.length > 0"
        class="mb-3 p-3 rounded-xl bg-primary-50/60 dark:bg-primary-950/40 border border-primary-200 dark:border-primary-800 flex items-center justify-between gap-3 animate-fadein"
      >
        <div class="flex items-center gap-2">
          <i class="pi pi-check-circle text-primary font-bold" />
          <span class="text-sm font-semibold text-surface-900 dark:text-surface-100">
            <strong>{{ selectedScolarites.length }}</strong> étudiant(s) sélectionné(s)
          </span>
        </div>
        <div class="flex items-center gap-2">
          <Button
            label="Gérer les applications"
            icon="pi pi-th-large"
            size="small"
            severity="primary"
            @click="openBulkApplications"
          />
          <Button
            label="Désélectionner tout"
            icon="pi pi-times"
            size="small"
            text
            severity="secondary"
            @click="selectedScolarites = []"
          />
        </div>
      </div>

      <DataTable
          scrollHeight="800px"
          scrollable
          v-model:filters="filters"
          v-model:selection="selectedScolarites"
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
        <Column selectionMode="multiple" headerStyle="width: 3rem" />
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
        <Column header="Applications" style="min-width: 14rem">
          <template #body="{ data }">
            <div class="flex flex-wrap gap-1">
              <ApplicationBadge
                v-for="app in (data.etudiant?.applications || ['intranet'])"
                :key="app"
                :app="app"
                size="sm"
              />
            </div>
          </template>
        </Column>
        <Column :showFilterMenu="false" style="min-width: 14rem">
          <template #body="slotProps">
            <div class="flex items-center gap-1">
              <Button
                icon="pi pi-th-large"
                text
                rounded
                severity="help"
                size="small"
                v-tooltip.top="'Gérer les applications'"
                @click="openStudentApplications(slotProps.data)"
              />
              <ButtonInfo tooltip="Voir les détails de l'étudiant" @click="viewEtudiant(slotProps.data)" />
              <ButtonEdit tooltip="Modifier l'étudiant" @click="editEtudiant(slotProps.data)" />
              <ButtonDelete tooltip="Marquer l'étudiant comme démissionnaire" @confirm-delete="deleteEtudiant(slotProps.data)" />
            </div>
          </template>
        </Column>
        <template #footer> {{ nbEtudiants }} résultat(s).</template>
      </DataTable>

      <Dialog header=" " :visible="showViewDialog" modal :style="{ width: '90vw' }" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }" dismissable-mask :closable="true"
              :isVisible="showViewDialog"
              @update:visible="showViewDialog = $event">
        <ProfilEtudiant :etudiantId="selectedEtudiant.etudiant.id" :isVisible="showViewDialog" />
      </Dialog>

      <EtudiantApplicationsDialog
        v-model:isVisible="showApplicationsDialog"
        :etudiants="dialogEtudiants"
        :annee="currentAnnee"
        :anneesList="anneesList"
        :departementId="departementId"
        :anneeUniversitaireId="selectedAnneeUniversitaireId"
        :initialMode="dialogInitialMode"
        @saved="onApplicationsSaved"
      />
    </div>
  </div>
</template>

<style scoped></style>
