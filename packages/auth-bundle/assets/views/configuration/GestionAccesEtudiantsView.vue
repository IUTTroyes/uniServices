<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import {
  HeaderComponent,
  PhotoUser,
  ApplicationBadge,
  EtudiantApplicationsDialog,
  EmptyState
} from '@components';
import {
  getEtudiantsScolariteService,
  getAllDepartementsService,
  getAnneesService
} from '@requests';
import { useAnneeUnivStore, useUsersStore } from '@stores';
import { useToast } from 'primevue/usetoast';

const toast = useToast();
const userStore = useUsersStore();
const anneeUnivStore = useAnneeUnivStore();

const selectedAnneeUniversitaireId = computed(() => anneeUnivStore.selectedAnneeUniv?.id ?? null);

// Départements
const departements = ref([]);
const selectedDepartementId = ref(null);
const isLoadingDepartements = ref(false);

// Années / Cohortes
const anneesList = ref([]);
const selectedAnneeId = ref(null);
const isLoadingAnnees = ref(false);

// Étudiants
const etudiants = ref([]);
const totalEtudiants = ref(0);
const selectedScolarites = ref([]);
const isLoadingEtudiants = ref(false);

// Pagination & Filtres
const page = ref(0);
const rowsPerPage = ref(30);
const rowsPerPageOptions = [30, 60, 100];
const searchQuery = ref('');

// Dialog d'affectation des applications
const showApplicationsDialog = ref(false);
const dialogEtudiants = ref([]);
const dialogInitialMode = ref('selection');

let searchDebounceTimer = null;

const selectedAnneeObj = computed(() => {
  return anneesList.value.find((a) => a.id === selectedAnneeId.value) || null;
});

// Charger la liste des départements
const loadDepartements = async () => {
  isLoadingDepartements.value = true;
  try {
    const list = await getAllDepartementsService();
    departements.value = Array.isArray(list) ? list : [];

    // Sélectionner par défaut le département de l'utilisateur connecté, sinon le premier
    if (departements.value.length > 0) {
      const userDeptId = userStore.departementDefaut?.id;
      const match = departements.value.find((d) => d.id === userDeptId);
      selectedDepartementId.value = match ? match.id : departements.value[0].id;
    }
  } catch (error) {
    console.error('Erreur lors du chargement des départements :', error);
  } finally {
    isLoadingDepartements.value = false;
  }
};

// Charger les années du département sélectionné
const loadAnnees = async () => {
  if (!selectedDepartementId.value) {
    anneesList.value = [];
    selectedAnneeId.value = null;
    return;
  }

  isLoadingAnnees.value = true;
  try {
    const res = await getAnneesService({
      departement: selectedDepartementId.value,
      actif: true,
    });
    anneesList.value = Array.isArray(res) ? res : [];
    selectedAnneeId.value = null; // Par défaut : toutes les promotions
  } catch (error) {
    console.error('Erreur lors du chargement des années :', error);
    anneesList.value = [];
  } finally {
    isLoadingAnnees.value = false;
  }
};

// Charger la liste paginée des étudiants
const loadEtudiants = async () => {
  if (!selectedDepartementId.value) {
    etudiants.value = [];
    totalEtudiants.value = 0;
    return;
  }

  isLoadingEtudiants.value = true;
  try {
    const params = {
      departement: selectedDepartementId.value,
      itemsPerPage: rowsPerPage.value,
      page: page.value + 1,
    };

    if (selectedAnneeUniversitaireId.value) {
      params.anneeUniversitaire = selectedAnneeUniversitaireId.value;
    }

    if (selectedAnneeId.value) {
      params.annee = selectedAnneeId.value;
    }

    const q = searchQuery.value.trim();
    if (q) {
      params.search = q;
    }

    const data = await getEtudiantsScolariteService(params);
    const items = Array.isArray(data) ? data : [];
    etudiants.value = items;
    // Si l'API retourne totalItems ou si on est en pagination simple
    totalEtudiants.value = items.length >= rowsPerPage.value ? 500 : (page.value * rowsPerPage.value + items.length);
  } catch (error) {
    console.error('Erreur lors du chargement des étudiants :', error);
    etudiants.value = [];
    totalEtudiants.value = 0;
  } finally {
    isLoadingEtudiants.value = false;
  }
};

// Changement de département -> recharger les années puis les étudiants
watch(selectedDepartementId, async () => {
  page.value = 0;
  selectedScolarites.value = [];
  await loadAnnees();
  await loadEtudiants();
});

// Changement d'année -> recharger les étudiants
watch(selectedAnneeId, async () => {
  page.value = 0;
  selectedScolarites.value = [];
  await loadEtudiants();
});

// Recherche texte avec debounce
watch(searchQuery, () => {
  clearTimeout(searchDebounceTimer);
  searchDebounceTimer = setTimeout(() => {
    page.value = 0;
    loadEtudiants();
  }, 400);
});

// Pagination
const onPageChange = (event) => {
  page.value = event.page;
  rowsPerPage.value = event.rows;
  loadEtudiants();
};

// Actions de gestion des applications
const openStudentApplications = (scolarite) => {
  dialogEtudiants.value = [scolarite];
  dialogInitialMode.value = 'selection';
  showApplicationsDialog.value = true;
};

const openBulkApplications = () => {
  if (selectedScolarites.value.length === 0) return;
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
  toast.add({
    severity: 'success',
    summary: 'Succès',
    detail: 'Les accès aux applications ont été mis à jour.',
    life: 4000,
  });
  selectedScolarites.value = [];
  await loadEtudiants();
};

const copyEmail = (email) => {
  if (!email) return;
  navigator.clipboard.writeText(email);
  toast.add({
    severity: 'info',
    summary: 'Email copié',
    detail: email,
    life: 2500,
  });
};

onMounted(async () => {
  await loadDepartements();
  await loadAnnees();
  await loadEtudiants();
});
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <HeaderComponent
      titre="Gestion des applications étudiants"
      description="Gérez et affectez les droits d'accès aux applications pour l'ensemble des étudiants, par sélection groupée ou par cohorte."
      icon="pi pi-graduation-cap"
      color="purple"
      :show-back="true"
      back-url="/configuration"
    >
      <template #actions>
        <div class="flex items-center gap-2">
          <Button
            label="Affecter par promotion"
            icon="pi pi-graduation-cap"
            severity="secondary"
            size="small"
            @click="openPromoApplications"
          />
          <Button
            v-if="selectedScolarites.length > 0"
            :label="`Gérer la sélection (${selectedScolarites.length})`"
            icon="pi pi-th-large"
            severity="primary"
            size="small"
            @click="openBulkApplications"
          />
        </div>
      </template>
    </HeaderComponent>

    <!-- Filtres & Sélecteurs -->
    <div class="card p-4 bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-800 rounded-2xl shadow-xs">
      <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
          <!-- Sélecteur Département -->
          <div class="flex items-center gap-2">
            <span class="text-xs font-bold uppercase tracking-wider text-muted-color">Département :</span>
            <Select
              v-model="selectedDepartementId"
              :options="departements"
              optionLabel="libelle"
              optionValue="id"
              placeholder="Sélectionnez un département"
              class="w-64"
              :loading="isLoadingDepartements"
            />
          </div>

          <!-- Sélecteur Année / Promo -->
          <div class="flex items-center gap-2">
            <span class="text-xs font-bold uppercase tracking-wider text-muted-color">Promotion :</span>
            <Select
              v-model="selectedAnneeId"
              :options="anneesList"
              optionLabel="libelle"
              optionValue="id"
              showClear
              placeholder="Toutes les promotions"
              class="w-56"
              :loading="isLoadingAnnees"
            />
          </div>
        </div>

        <!-- Recherche rapide -->
        <div class="w-full lg:w-80">
          <IconField>
            <InputIcon class="pi pi-search" />
            <InputText
              v-model="searchQuery"
              placeholder="Rechercher par nom, prénom, email..."
              class="w-full"
            />
          </IconField>
        </div>
      </div>
    </div>

    <!-- Tableau des étudiants -->
    <div class="card overflow-hidden">
      <DataTable
        v-model:selection="selectedScolarites"
        :value="etudiants"
        :loading="isLoadingEtudiants"
        dataKey="id"
        lazy
        paginator
        :rows="rowsPerPage"
        :first="page * rowsPerPage"
        :totalRecords="totalEtudiants"
        :rowsPerPageOptions="rowsPerPageOptions"
        @page="onPageChange"
        stripedRows
        responsiveLayout="scroll"
        class="w-full"
      >
        <template #empty>
          <div class="py-12">
            <EmptyState
              title="Aucun étudiant trouvé"
              description="Aucun étudiant ne correspond aux critères de recherche pour ce département."
              icon="pi pi-users"
            />
          </div>
        </template>

        <!-- Sélection checkbox -->
        <Column selectionMode="multiple" headerStyle="width: 3rem" />

        <!-- Photo -->
        <Column header="" style="width: 4rem">
          <template #body="{ data }">
            <PhotoUser
              :user-photo="data.etudiant?.photoName"
              class="rounded-full w-10 h-10 border border-surface-200 dark:border-surface-700 object-cover mx-auto"
            />
          </template>
        </Column>

        <!-- Identité -->
        <Column header="Étudiant" style="min-width: 14rem">
          <template #body="{ data }">
            <div class="flex flex-col">
              <span class="font-bold text-surface-900 dark:text-surface-100">
                {{ data.etudiant?.prenom }} {{ data.etudiant?.nom }}
              </span>
              <span class="text-xs text-muted-color font-mono">
                N° {{ data.etudiant?.num_etudiant || data.etudiant?.username || '-' }}
              </span>
            </div>
          </template>
        </Column>

        <!-- Email -->
        <Column header="Email universitaire" style="min-width: 15rem">
          <template #body="{ data }">
            <div
              v-if="data.etudiant?.mailUniv"
              class="inline-flex items-center gap-1.5 text-xs text-surface-700 dark:text-surface-300 hover:text-primary cursor-pointer transition-colors"
              title="Cliquer pour copier l'email"
              @click="copyEmail(data.etudiant.mailUniv)"
            >
              <span>{{ data.etudiant.mailUniv }}</span>
              <i class="pi pi-copy text-[11px] text-muted-color" />
            </div>
            <span v-else class="text-xs text-muted-color">-</span>
          </template>
        </Column>

        <!-- Promotion / Année -->
        <Column header="Formation / Année" style="min-width: 12rem">
          <template #body="{ data }">
            <div class="flex flex-col">
              <span class="text-xs font-semibold text-surface-900 dark:text-surface-100">
                {{ data.annee?.libelle || data.semestre?.libelle || '-' }}
              </span>
              <span v-if="data.diplome" class="text-[11px] text-muted-color truncate max-w-[200px]">
                {{ data.diplome.libelle }}
              </span>
            </div>
          </template>
        </Column>

        <!-- Applications autorisées avec les couleurs officielles -->
        <Column header="Applications autorisées" style="min-width: 18rem">
          <template #body="{ data }">
            <div class="flex flex-wrap gap-1.5 items-center">
              <ApplicationBadge
                v-for="app in (data.etudiant?.applications || ['intranet'])"
                :key="app"
                :app="app"
                size="sm"
                show-dot
              />
            </div>
          </template>
        </Column>

        <!-- Action individuelle -->
        <Column header="Actions" style="width: 6rem; text-align: right">
          <template #body="{ data }">
            <Button
              icon="pi pi-th-large"
              text
              rounded
              size="small"
              severity="help"
              title="Modifier les applications"
              @click="openStudentApplications(data)"
            />
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Dialogue d'affectation partagé -->
    <EtudiantApplicationsDialog
      :isVisible="showApplicationsDialog"
      :etudiants="dialogEtudiants"
      :annee="selectedAnneeObj"
      :anneesList="anneesList"
      :departementId="selectedDepartementId"
      :anneeUniversitaireId="selectedAnneeUniversitaireId"
      :initialMode="dialogInitialMode"
      @update:isVisible="showApplicationsDialog = $event"
      @saved="onApplicationsSaved"
    />
  </div>
</template>

<style scoped>
</style>
