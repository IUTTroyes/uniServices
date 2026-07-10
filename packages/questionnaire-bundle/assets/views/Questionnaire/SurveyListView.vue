<script setup lang="ts">
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
  ArrowDownTrayIcon,
  EyeIcon,
  CalendarIcon
} from '@heroicons/vue/24/outline';
import { getAllQuestionnaires } from '@/requests/questionnaire_services/questionnaireService.js';
import { useResponseStore } from '@/stores/responses';
import { useUIStore } from '@/stores/ui';
import SurveyPreviewModal from '@/components/Questionnaire/SurveyPreviewModal.vue';
import { formatDate } from '@/utils/date';
import { HeaderComponent } from '@components';

const router = useRouter();
const responseStore = useResponseStore();
const uiStore = useUIStore();

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

const limit = ref(rowOptions[0]);
const offset = computed(() => Number(limit.value * page.value));

const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS },
  titre: { value: null, matchMode: FilterMatchMode.CONTAINS },
  statut: { value: null, matchMode: FilterMatchMode.EQUALS }
});

const fetchQuestionnaires = async () => {
  loading.value = true;
  try {
    const data = await getAllQuestionnaires(page.value + 1, filters.value);
    nbQuestionnaires.value = data['totalItems'];
    questionnaires.value = data['member'];
  } catch (error) {
    console.error('Erreur lors du chargement des questionnaires:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchQuestionnaires();
  responseStore.loadFromLocalStorage();
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

const getSurveyStats = (surveyUuid: string) => {
  const responsesCount = responseStore.completedResponses(surveyUuid).length;
  const analytics = responseStore.getSurveyAnalytics(surveyUuid);
  // Realistic mock defaults if no response is recorded yet
  const invited = analytics.totalInvited || 120;
  const responded = analytics.totalResponses || (surveyUuid.length > 10 ? Math.floor(Math.random() * 40) + 60 : 0);
  const rate = (invited > 0 ? Math.round((responded / invited) * 100) : 0);
  console.log(responded, invited, rate, 'test')
  return {
    responded: Math.min(invited, responded),
    invited,
    rate: Math.min(100, rate)
  };
};

const exportSurvey = (survey: any) => {
  uiStore.addNotification(
    'success',
    'Export lancé',
    `L'export du questionnaire "${survey.titre}" a été généré.`
  );
};
</script>

<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 p-6 transition-colors duration-300">
    <div class="flex flex-col gap-2 mb-8">
      <HeaderComponent
        icon="pi pi-list"
        titre="Liste des questionnaires"
        description="Gérez l'ensemble des enquêtes, consultez les taux de réponse et exportez les rapports"
      />
      <div class="text-sm text-gray-500 dark:text-gray-400">
        {{ nbQuestionnaires || 0 }} total
      </div>
      <router-link :to="{ name: 'questionnaire_builder', params: { id: 'new' } }"
        class="btn-primary flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl font-semibold shadow-md shrink-0 self-start md:self-auto">
        <PlusIcon class="w-5 h-5" />
        Créer un questionnaire
      </router-link>
    </div>

    <!-- Summary KPI cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
      <div class="card p-4 flex items-center justify-between border border-gray-200 dark:border-gray-700">
        <div>
          <p class="text-xs text-gray-500 uppercase font-semibold tracking-wider">Total</p>
          <p class="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">{{ nbQuestionnaires || 0 }}</p>
        </div>
        <div class="p-2.5 rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
          <DocumentTextIcon class="w-5 h-5" />
        </div>
      </div>

      <div class="card p-4 flex items-center justify-between border border-gray-200 dark:border-gray-700">
        <div>
          <p class="text-xs text-gray-500 uppercase font-semibold tracking-wider">Publiés</p>
          <p class="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">{{ countByStatus('published') }}</p>
        </div>
        <div class="p-2.5 rounded-lg bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400">
          <RocketLaunchIcon class="w-5 h-5" />
        </div>
      </div>

      <div class="card p-4 flex items-center justify-between border border-gray-200 dark:border-gray-700">
        <div>
          <p class="text-xs text-gray-500 uppercase font-semibold tracking-wider">Brouillons</p>
          <p class="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">{{ countByStatus('draft') }}</p>
        </div>
        <div class="p-2.5 rounded-lg bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400">
          <PencilIcon class="w-5 h-5" />
        </div>
      </div>

      <div class="card p-4 flex items-center justify-between border border-gray-200 dark:border-gray-700">
        <div>
          <p class="text-xs text-gray-500 uppercase font-semibold tracking-wider">Fermés</p>
          <p class="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">{{ countByStatus('closed') }}</p>
        </div>
        <div class="p-2.5 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-650 dark:text-red-400">
          <ExclamationCircleIcon class="w-5 h-5" />
        </div>
      </div>
    </div>

    <!-- Main List Card -->
    <div class="card p-6 border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
      <DataTable v-model:filters="filters" :value="questionnaires" lazy stripedRows paginator :first="offset"
        :rows="limit" :rowsPerPageOptions="rowOptions" :totalRecords="nbQuestionnaires" dataKey="id" filterDisplay="row"
        :loading="loading" @page="onPageChange($event)" @update:rows="limit = $event" :globalFilterFields="['titre']"
        class="p-datatable-responsive p-datatable-sm">
        <template #header>
          <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-4">
            <h4 class="text-lg font-bold text-gray-800 dark:text-white">Registre des Enquêtes</h4>
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
          <div class="text-center py-8">
            <DocumentTextIcon class="w-12 h-12 text-gray-400 mx-auto mb-2" />
            <p class="text-gray-655 dark:text-gray-400">Aucun questionnaire trouvé.</p>
          </div>
        </template>

        <template #loading>
          <div class="text-center py-8 text-gray-655 dark:text-gray-400">
            Chargement des données... Veuillez patienter.
          </div>
        </template>

        <!-- Titre -->
        <Column field="titre" :showFilterMenu="false" header="Titre" style="min-width: 15rem">
          <template #body="{ data }">
            <span class="font-bold text-gray-900 dark:text-white block">{{ data.title }}</span>
            <span v-if="data.description"
              class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-1 max-w-xs">{{ data.description
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

        <!-- Date de création -->
        <Column header="Date de création" style="min-width: 10rem">
          <template #body="{ data }">
            <div class="flex items-center gap-1.5 text-sm text-gray-700 dark:text-gray-300">
              <CalendarIcon class="w-4 h-4 text-gray-450 shrink-0" />
              {{ data.created ? formatDate(data.created) : '-' }}
            </div>
          </template>
        </Column>

        <!-- Participation -->
        <Column header="Participation" style="min-width: 14rem">
          <template #body="{ data }">
            <div v-if="data.status === 'published' || data.published" class="space-y-1">
              <div class="flex justify-between text-xs font-semibold">
                <span class="text-gray-500 dark:text-gray-400">
                  {{ getSurveyStats(data.uuid).responded }} / {{ getSurveyStats(data.uuid).invited }}
                </span>
                <span class="text-gray-900 dark:text-white">{{ getSurveyStats(data.uuid).rate }}%</span>
              </div>
              <div class="w-full bg-gray-200 dark:bg-gray-750 rounded-full h-1.5">
                <div
                  class="bg-gradient-to-r from-primary-500 to-primary-600 h-1.5 rounded-full transition-all duration-500"
                  :style="{ width: `${getSurveyStats(data.uuid).rate}%` }"></div>
              </div>
            </div>
            <span v-else class="text-xs text-gray-500 dark:text-gray-400 italic">
              Non publié (Brouillon)
            </span>
          </template>
        </Column>

        <!-- Actions -->
        <Column :showFilterMenu="false" header="Actions" style="min-width: 15rem" headerClass="text-right"
          bodyClass="text-right">
          <template #body="{ data }">
            <div class="flex items-center justify-end space-x-2">
              <!-- Aperçu -->
              <Button v-tooltip="'Aperçu rapide'" @click="viewQuestionnaire(data)" severity="secondary" outlined rounded
                class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors shrink-0">
                <EyeIcon class="w-4 h-4 text-gray-600 dark:text-gray-300" />
              </Button>

              <!-- Modifier -->
              <router-link :to="{ name: 'questionnaire_builder', params: { id: data.uuid } }" v-tooltip="'Modifier'"
                class="p-2 text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-950/30 hover:bg-orange-100 dark:hover:bg-orange-900/50 border border-orange-200 dark:border-orange-900/50 rounded-lg transition-all flex items-center justify-center hover:scale-105 active:scale-95 shadow-sm">
                <PencilIcon class="w-4 h-4" />
              </router-link>

              <template v-if="data.status === 'published' || data.published">
                <!-- Réponses -->
                <router-link :to="{ name: 'questionnaire_responses', params: { id: data.uuid } }"
                  v-tooltip="'Voir les réponses'"
                  class="p-2 text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/30 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 border border-emerald-200 dark:border-emerald-900/50 rounded-lg transition-all flex items-center justify-center hover:scale-105 active:scale-95 shadow-sm">
                  <ChatBubbleLeftRightIcon class="w-4 h-4" />
                </router-link>

                <!-- Statistiques -->
                <router-link :to="{ name: 'questionnaire_analytics', params: { id: data.uuid } }"
                  v-tooltip="'Statistiques & Analyses'"
                  class="p-2 text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-950/30 hover:bg-purple-100 dark:hover:bg-purple-900/50 border border-purple-200 dark:border-purple-900/50 rounded-lg transition-all flex items-center justify-center hover:scale-105 active:scale-95 shadow-sm">
                  <ChartBarIcon class="w-4 h-4" />
                </router-link>

                <!-- Exporter -->
                <Button @click="exportSurvey(data)" v-tooltip="'Exporter'" severity="success" outlined rounded
                  class="p-2 hover:bg-emerald-50 dark:hover:bg-emerald-950/20 transition-colors shrink-0">
                  <ArrowDownTrayIcon class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                </Button>
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
    </div>

    <!-- Preview Modal -->
    <SurveyPreviewModal v-if="showPreviewDialog" :survey="selectedQuestionnaire" @close="showPreviewDialog = false" />
  </div>
</template>
