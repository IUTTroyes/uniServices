<script setup lang="ts">
import { showDanger, showSuccess } from '@helpers/toast.js';
import { useEtablissement } from '@/composables/useEtablissement';
import { computed, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { FilterMatchMode } from '@primevue/core/api';
import {
  PlusIcon,
  DocumentTextIcon,
  RocketLaunchIcon,
  PencilIcon,
  ExclamationCircleIcon,
  ChatBubbleLeftRightIcon,
  ChartBarIcon,
  DocumentDuplicateIcon,
  ArrowDownTrayIcon,
  EyeIcon,
  CalendarIcon
} from '@heroicons/vue/24/outline';
import { getQuestionnairesService, exportExcelService, enregistrerModeleService } from '@/requests/questionnaire_service';
import { useConfirmation } from '@composables/useConfirmation';
import { useSurveyStore } from '@/stores/survey';
import SurveyPreviewModal from '@/components/Questionnaire/SurveyPreviewModal.vue';
import DuplicateSurveyModal from '@/components/Questionnaire/DuplicateSurveyModal.vue';
import { formatDate } from '@/utils/date';
import { Card, EmptyState, HeaderComponent, Kpi, ListSkeleton } from '@components';
import ActionButtonVertical from '@components/components/ActionButtonVertical.vue';
import { getAllDepartementsService } from '@requests';
import { useUsersStore } from '@stores';
const { libelleGlobal } = useEtablissement();

const router = useRouter();
const surveyStore = useSurveyStore();
const userStore = useUsersStore();

const showDuplicateModal = ref(false);
const selectedSurveyToDuplicate = ref<any>(null);

function openDuplicateModal(survey: any) {
  selectedSurveyToDuplicate.value = survey;
  showDuplicateModal.value = true;
}

async function confirmDuplicateSurvey(payload: { newTitle: string }) {
  if (!selectedSurveyToDuplicate.value) return;

  try {
    const duplicate = await surveyStore.duplicateSurvey(
      selectedSurveyToDuplicate.value.uuid || selectedSurveyToDuplicate.value.id,
      payload.newTitle
    );
    showDuplicateModal.value = false;
    selectedSurveyToDuplicate.value = null;
    showSuccess(`"${duplicate?.title || payload.newTitle}" et l'ensemble de ses règles ont été dupliqués avec succès.`, 'Questionnaire dupliqué');
    await fetchQuestionnaires();
  } catch (error) {
    console.error('Failed to duplicate survey:', error);
    showDanger('Une erreur est survenue lors de la duplication.', 'Erreur');
  }
}

const statuts = [
  { label: 'Publié', value: 'published', severity: 'success' },
  { label: 'Brouillon', value: 'draft', severity: 'warn' },
  { label: 'Fermé', value: 'closed', severity: 'danger' },
];

const questionnaires = ref<any[]>([]);
const nbQuestionnaires = ref(0);
const loading = ref(true);
const showPreviewDialog = ref(false);
const selectedQuestionnaire = ref<any>(null);
const page = ref(0);
const rowOptions = [30, 60, 120];

const departements = ref<any[]>([]);

const departementFilterOptions = computed(() => [
  { label: 'Tous les départements', value: 'all' },
  { label: libelleGlobal.value, value: 'global' },
  ...departements.value.map(d => ({
    label: d.libelle || `Département ${d.id}`,
    value: d.id
  }))
]);

const limit = ref(rowOptions[0]);
const offset = computed(() => Number(limit.value * page.value));

const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS },
  titre: { value: null, matchMode: FilterMatchMode.CONTAINS },
  statut: { value: null, matchMode: FilterMatchMode.EQUALS },
  departement: { value: null, matchMode: FilterMatchMode.EQUALS }
});

const fetchQuestionnaires = async () => {
  loading.value = true;
  try {
    // Les modèles ont leur propre page (bibliothèque).
    const apiParams: Record<string, any> = { estModele: false };

    const searchTitle = filters.value.titre?.value || filters.value.global?.value;
    if (searchTitle && String(searchTitle).trim()) {
      apiParams.title = String(searchTitle).trim();
    }

    if (filters.value.statut?.value) {
      const sVal = typeof filters.value.statut.value === 'object' ? filters.value.statut.value : filters.value.statut.value;
      if (sVal) apiParams.status = sVal;
    }

    const deptVal = typeof filters.value.departement?.value === 'object' ? filters.value.departement.value : filters.value.departement?.value;
    if (deptVal !== undefined && deptVal !== null && deptVal !== 'all') {
      if (deptVal === 'global') {
        apiParams['exists[departement]'] = 'false';
      } else {
        apiParams.departement = `/api/structure_departements/${deptVal}`;
      }
    }

    const data = await getQuestionnairesService(page.value + 1, apiParams);
    nbQuestionnaires.value = data['totalItems'];
    questionnaires.value = data['member'];
  } catch (error) {
    console.error('Erreur lors du chargement des questionnaires:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  try {
    const list = await getAllDepartementsService();
    if (Array.isArray(list) && list.length > 0) {
      departements.value = list;
    } else if (userStore.departements && userStore.departements.length > 0) {
      departements.value = userStore.departements;
    }
  } catch (e) {
    if (userStore.departements && userStore.departements.length > 0) {
      departements.value = userStore.departements;
    }
  }
  await fetchQuestionnaires();
});

async function onPageChange(event: any) {
  page.value = event.page;
  await fetchQuestionnaires();
}

watch(filters, async () => {
  page.value = 0;
  await fetchQuestionnaires();
}, { deep: true });

const viewQuestionnaire = (questionnaire: any) => {
  selectedQuestionnaire.value = questionnaire;
  showPreviewDialog.value = true;
};

const getStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    published: 'Publié',
    draft: 'Brouillon',
    closed: 'Fermé'
  };
  return labels[status] || status;
};

const getStatusSeverity = (status: string) => {
  const severities: Record<string, 'success' | 'warn' | 'danger' | 'info' | undefined> = {
    published: 'success',
    draft: 'warn',
    closed: 'danger'
  };
  return severities[status] || 'info';
};

const countByStatus = (status: string) => {
  if (!questionnaires.value) return 0;
  return questionnaires.value.filter((q: any) => q.status === status).length;
};

/** Participation réelle, fournie par l'API (totalInvited / totalResponses du questionnaire). */
const getSurveyStats = (surveyUuid: string) => {
  const survey = questionnaires.value.find((q: any) => q.uuid === surveyUuid);
  const invited = Number(survey?.totalInvited ?? 0);
  const responded = Number(survey?.totalResponses ?? 0);
  return {
    responded,
    invited,
    rate: invited > 0 ? Math.round((responded / invited) * 100) : 0,
  };
};

/** Copie réutilisable dans la bibliothèque de modèles (même département). */
const { confirmer } = useConfirmation();
const enregistrerCommeModele = async (survey: any) => {
  const ok = await confirmer({
    titre: 'Enregistrer comme modèle',
    message: `Une copie de « ${survey.title} » (sections et questions) rejoindra la bibliothèque de modèles du département.`,
    libelle: 'Enregistrer',
  });
  if (ok) {
    try {
      await enregistrerModeleService(survey.uuid, { title: survey.title });
    } catch (e) {
      // apiCall a affiché l'erreur.
    }
  }
};

/** Export Excel des résultats (questionnaires publiés ou clôturés). */
const exportEnCours = ref<string | null>(null);
const exportSurvey = async (survey: any) => {
  exportEnCours.value = survey.uuid;
  try {
    await exportExcelService(survey.uuid);
  } catch (e) {
    showDanger(`Les résultats de « ${survey.title} » n'ont pas pu être exportés.`, 'Export impossible');
  } finally {
    exportEnCours.value = null;
  }
};
</script>

<template>
  <div class="space-y-6">
    <HeaderComponent icon="pi pi-list" titre="Liste des questionnaires"
      description="Gérez l'ensemble des enquêtes, consultez les taux de réponse et exportez les rapports">
      <template #actions>
        <ActionButtonVertical :to="{ name: 'questionnaire_builder', params: { id: 'new' } }" :icon="PlusIcon"
          label="Créer un questionnaire" severity="primary" />
      </template>
    </HeaderComponent>

    <!-- Summary KPI cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
      <Kpi label="Total" :value="nbQuestionnaires || 0" :icon="DocumentTextIcon" color="blue" />
      <Kpi label="Publiés" :value="countByStatus('published')" :icon="RocketLaunchIcon" color="green" />
      <Kpi label="Brouillons" :value="countByStatus('draft')" :icon="PencilIcon" color="yellow" />
      <Kpi label="Fermés" :value="countByStatus('closed')" :icon="ExclamationCircleIcon" color="red" />
    </div>

    <!-- Main List Card -->
    <Card body-class="overflow-hidden">
      <DataTable v-model:filters="filters" :value="questionnaires" lazy stripedRows paginator :first="offset"
        :rows="limit" :rowsPerPageOptions="rowOptions" :totalRecords="nbQuestionnaires" dataKey="id" filterDisplay="row"
        :loading="loading" @page="onPageChange($event)" @update:rows="limit = $event" :globalFilterFields="['titre']"
        class="p-datatable-responsive ui-table-compact">
        <template #header>
          <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-4">
            <h3 class="text-lg font-bold text-gray-800 dark:text-white">Registre des Enquêtes</h3>
            <IconField>
              <InputIcon>
                <i class="pi pi-search" />
              </InputIcon>
              <InputText v-model="filters['global'].value" placeholder="Rechercher un titre..."
                class="py-2 px-3 rounded-lg shadow-sm border border-gray-300 dark:border-gray-700" />
            </IconField>
          </div>
        </template>

        <template #empty>
          <EmptyState title="Aucun questionnaire trouvé" description="Modifiez vos filtres ou créez un questionnaire." icon="pi pi-search" compact />
        </template>

        <template #loading>
          <ListSkeleton />
        </template>

        <!-- Titre -->
        <Column field="titre" :showFilterMenu="false" header="Titre" style="min-width: 15rem">
          <template #body="{ data }">
            <span class="font-bold text-gray-900 dark:text-white block">{{ data.title }}</span>
            <span v-if="data.description"
              class="text-sm text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-1 max-w-xs">{{ data.description
              }}</span>
          </template>
          <template #filter="{ filterModel, filterCallback }">
            <InputText v-model="filterModel.value" type="text" @input="filterCallback()" placeholder="Filtrer par titre"
              class="w-full text-sm border border-gray-300 dark:border-gray-700 rounded p-1 px-2 focus:ring-1 focus:ring-primary-500" />
          </template>
        </Column>

        <!-- Statut -->
        <Column field="statut" header="Statut" :showFilterMenu="false" style="min-width: 10rem">
          <template #body="{ data }">
            <Tag :value="getStatusLabel(data.status)" :severity="getStatusSeverity(data.status)" />
          </template>
          <template #filter="{ filterModel, filterCallback }">
            <Select v-model="filterModel.value" @change="filterCallback()" :options="statuts" placeholder="Filtrer"
              style="min-width: 10rem" :showClear="true" class="border border-gray-300 dark:border-gray-700">
              <template #value="slotProps">
                <div v-if="slotProps.value" class="flex items-center">
                  <Tag :value="slotProps.value.label" :severity="slotProps.value.severity" />
                </div>
                <span v-else class="text-gray-500 text-sm">Choisir</span>
              </template>
              <template #option="slotProps">
                <Tag :value="slotProps.option.label" :severity="slotProps.option.severity" />
              </template>
            </Select>
          </template>
        </Column>

        <!-- Département / Portée -->
        <Column field="departement" header="Département" :showFilterMenu="false" style="min-width: 12rem">
          <template #body="{ data }">
            <span
              v-if="data.departement"
              class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-sm font-semibold shadow-2xs border"
              :style="{
                backgroundColor: data.departement.couleur ? `${data.departement.couleur}15` : '#f0fdf4',
                color: data.departement.couleur || '#16a34a',
                borderColor: data.departement.couleur ? `${data.departement.couleur}40` : '#bbf7d0'
              }"
            >
              <span
                class="w-2 h-2 rounded-full shrink-0"
                :style="{ backgroundColor: data.departement.couleur || '#16a34a' }"
              ></span>
              <span class="truncate max-w-[130px]">{{ data.departement.libelle || `Dpt ${data.departement.id}` }}</span>
            </span>
            <span
              v-else
              class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-sm font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-2xs"
            >
              <span class="w-2 h-2 rounded-full bg-slate-400 shrink-0"></span>
              <span>{{ libelleGlobal }}</span>
            </span>
          </template>
          <template #filter="{ filterModel, filterCallback }">
            <Select
              v-model="filterModel.value"
              @change="filterCallback()"
              :options="departementFilterOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Tous"
              style="min-width: 11rem"
              :showClear="true"
              class="border border-gray-300 dark:border-gray-700 text-sm"
            >
              <template #value="slotProps">
                <span v-if="slotProps.value" class="text-sm font-semibold">
                  {{ slotProps.value === 'global' ? libelleGlobal : (departements.find(d => d.id === slotProps.value)?.libelle || slotProps.value) }}
                </span>
                <span v-else class="text-gray-500 text-sm">Tous</span>
              </template>
            </Select>
          </template>
        </Column>

        <!-- Date de création -->
        <Column header="Date de création" style="min-width: 10rem">
          <template #body="{ data }">
            <div class="flex items-center gap-1.5 text-sm text-gray-700 dark:text-gray-300">
              <CalendarIcon class="w-4 h-4 text-gray-400 shrink-0" />
              {{ formatDate(data.createdAt || data.created) }}
            </div>
          </template>
        </Column>

        <!-- Participation -->
        <Column header="Participation" style="min-width: 14rem">
          <template #body="{ data }">
            <div v-if="data.status === 'published' || data.published" class="space-y-1">
              <div class="flex justify-between text-sm font-semibold">
                <span class="text-gray-500 dark:text-gray-400">
                  {{ getSurveyStats(data.uuid).responded }} / {{ getSurveyStats(data.uuid).invited }}
                </span>
                <span class="text-gray-900 dark:text-white">{{ getSurveyStats(data.uuid).rate }}%</span>
              </div>
              <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                <div
                  class="bg-gradient-to-r from-primary-500 to-primary-600 h-1.5 rounded-full transition-all duration-500"
                  :style="{ width: `${getSurveyStats(data.uuid).rate}%` }"></div>
              </div>
            </div>
            <span v-else class="text-sm text-gray-500 dark:text-gray-400 italic">
              Non publié (Brouillon)
            </span>
          </template>
        </Column>

        <!-- Actions -->
        <Column :showFilterMenu="false" header="Actions" style="min-width: 15rem" headerClass="text-right"
          bodyClass="text-right">
          <template #body="{ data }">
            <div class="flex flex-wrap items-center justify-end gap-1">
              <Button v-tooltip="'Aperçu rapide'" aria-label="Aperçu rapide" icon="pi pi-eye" severity="secondary" text size="small" @click="viewQuestionnaire(data)" />
              <Button v-tooltip="'Dupliquer le questionnaire'" aria-label="Dupliquer le questionnaire" icon="pi pi-copy" severity="secondary" text size="small" @click="openDuplicateModal(data)" />
              <Button v-tooltip="'Enregistrer comme modèle'" aria-label="Enregistrer comme modèle" icon="pi pi-bookmark" severity="secondary" text size="small" @click="enregistrerCommeModele(data)" />
              <Button as="router-link" :to="{ name: 'questionnaire_builder', params: { id: data.uuid } }" v-tooltip="'Modifier'" aria-label="Modifier le questionnaire" icon="pi pi-pencil" severity="secondary" text size="small" />
              <template v-if="data.status === 'published' || data.published">
                <Button as="router-link" :to="{ name: 'questionnaire_responses', params: { id: data.uuid } }" v-tooltip="'Voir les réponses'" aria-label="Voir les réponses" icon="pi pi-comments" severity="secondary" text size="small" />
                <Button as="router-link" :to="{ name: 'questionnaire_analytics', params: { id: data.uuid } }" v-tooltip="'Statistiques et analyses'" aria-label="Statistiques et analyses" icon="pi pi-chart-bar" severity="secondary" text size="small" />
                <Button v-tooltip="'Exporter les résultats (Excel)'" aria-label="Exporter les résultats" icon="pi pi-download" severity="secondary" text size="small" :disabled="data.status === 'draft'" :loading="exportEnCours === data.uuid" @click="exportSurvey(data)" />
              </template>
            </div>
          </template>
        </Column>

        <template #footer>
          <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">
            {{ nbQuestionnaires }} résultat(s).
          </div>
        </template>
      </DataTable>
    </Card>

    <!-- Preview Modal -->
    <SurveyPreviewModal v-if="showPreviewDialog" :survey="selectedQuestionnaire" @close="showPreviewDialog = false" />

    <!-- Duplicate Survey Modal -->
    <DuplicateSurveyModal
      v-if="showDuplicateModal && selectedSurveyToDuplicate"
      :survey="selectedSurveyToDuplicate"
      @close="showDuplicateModal = false; selectedSurveyToDuplicate = null"
      @confirm="confirmDuplicateSurvey"
    />
  </div>
</template>
