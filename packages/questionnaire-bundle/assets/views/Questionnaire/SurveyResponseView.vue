<script setup lang="ts">
import { useConfirmation } from '@composables/useConfirmation';
import { cloturerQuestionnaireService } from '@/requests/questionnaire_service';
import { showDanger, showInfo, showSuccess, showWarn } from '@helpers/toast.js';
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import { FilterMatchMode } from '@primevue/core/api';
import {
  ChartBarIcon,
  UserPlusIcon,
  ArrowDownTrayIcon,
  UserGroupIcon,
  ChatBubbleLeftRightIcon,
  CheckCircleIcon,
  ClockIcon,
  MagnifyingGlassIcon,
  TableCellsIcon,
  Squares2X2Icon,
  BellIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline';
import { useSurveyStore } from '@/stores/survey';
import { useResponseStore } from '@/stores/responses';
import InviteParticipantsModal from '@/components/Questionnaire/InviteParticipantsModal.vue';
import ResponseDetailModal from '@/components/Questionnaire/ResponseDetailModal.vue';
import ReminderSendModal from '@/components/Questionnaire/ReminderSendModal.vue';
import ReminderHistoryModal from '@/components/Questionnaire/ReminderHistoryModal.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import type { Response } from '@/types/survey';
import { initiales } from '@/utils/participants';
import { exportCsv } from '@helpers/downloadCsv.js';
import { formatRelativeTime, formatDuration, formatDateTime } from '@/utils/date';
import { HeaderComponent, Kpi, Card, EmptyState, ActionButtonVertical } from '@components';

const route = useRoute();
const surveyStore = useSurveyStore();
const responseStore = useResponseStore();

const surveyId = route.params.id as string;
const survey = computed(() => {
  const current = surveyStore.currentSurvey;
  if (!current) return null;
  return {
    ...current,
    sections: surveyStore.currentSections,
  };
});
const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS },
  participantName: { value: null, matchMode: FilterMatchMode.CONTAINS },
  statusLabel: { value: null, matchMode: FilterMatchMode.EQUALS },
  remindersStatus: { value: null, matchMode: FilterMatchMode.EQUALS },
});
const showInviteModal = ref(false);
const showResponseDetail = ref(false);
const selectedResponse = ref<Response | null>(null);
const showHistoryModal = ref(false);
const historyTargetResponse = ref<any | null>(null);

// --- Selection & Bulk actions ---
const selectedResponses = ref<any[]>([]);

const selectedPendingResponses = computed(() =>
  selectedResponses.value.filter(r => !r.completed)
);

const filteredPendingCount = computed(() => {
  const currentList = filteredResponses.value && filteredResponses.value.length > 0
    ? filteredResponses.value
    : responsesWithDetails.value;
  return currentList.filter(r => !r.completed).length;
});

const isAllSelected = computed(() => {
  const currentList = filteredResponses.value && filteredResponses.value.length > 0
    ? filteredResponses.value
    : responsesWithDetails.value;
  return currentList.length > 0 && selectedResponses.value.length === currentList.length;
});

const isAllPendingSelected = computed(() => {
  const currentList = filteredResponses.value && filteredResponses.value.length > 0
    ? filteredResponses.value
    : responsesWithDetails.value;
  const pendings = currentList.filter(r => !r.completed);
  return pendings.length > 0 && pendings.every(p => selectedResponses.value.some(s => s.id === p.id));
});

function selectAllRows() {
  const currentList = filteredResponses.value && filteredResponses.value.length > 0
    ? filteredResponses.value
    : responsesWithDetails.value;
  selectedResponses.value = [...currentList];
}

function selectAllPending() {
  const currentList = filteredResponses.value && filteredResponses.value.length > 0
    ? filteredResponses.value
    : responsesWithDetails.value;
  const pendings = currentList.filter(r => !r.completed);
  selectedResponses.value = pendings;

  if (pendings.length > 0) {
    showInfo(`${pendings.length} participant${pendings.length > 1 ? 's à relancer sélectionnés' : ' à relancer sélectionné'}.`, 'Sélection mise à jour');
  } else {
    showInfo('Tous les participants affichés ont déjà répondu au questionnaire.', 'Aucun relançable');
  }
}

function clearSelection() {
  selectedResponses.value = [];
}

function exportSelectedCsv() {
  if (selectedResponses.value.length === 0) return;
  const headers = ['Nom', 'Email', 'Statut', 'Progression (%)', 'Relances (nb)', 'Dernière relance', 'Dernière activité'];
  const rows = selectedResponses.value.map(r => [
    r.participantName || '',
    r.participantEmail || '',
    r.statusLabel || '',
    r.progress !== undefined ? String(r.progress) : '',
    r.remindersCount !== undefined ? String(r.remindersCount) : '0',
    r.remindedAt ? new Date(r.remindedAt).toLocaleString('fr-FR') : '',
    r.lastActivity ? new Date(r.lastActivity).toLocaleString('fr-FR') : ''
  ]);

  const csvContent = [headers, ...rows]
    .map(row => row.map(val => `"${String(val).replace(/"/g, '""')}"`).join(';'))
    .join('\r\n');

  // BOM : ouverture correcte des accents dans un tableur.
  exportCsv({ content: '\uFEFF' + csvContent, fileName: `reponses_selection_${surveyId}.csv` });

  showSuccess(`${selectedResponses.value.length} ligne${selectedResponses.value.length > 1 ? 's exportées' : ' exportée'}.`, 'Export CSV généré');
}

// --- Reminder modal ---
const showReminderModal = ref(false);
const reminderTargets = ref<any[]>([]);
const pendingResponses = computed(() =>
  responses.value.filter(r => !r.completed)
);

function openReminderModal(targets: Response[]) {
  if (!targets || targets.length === 0) {
    showInfo('Aucun participant à relancer dans cette sélection.', 'Aucun participant');
    return;
  }
  // Les fenêtres reçoivent les lignes détaillées (nom, adresse, statut).
  reminderTargets.value = targets.map(t => responsesWithDetails.value.find(r => r.id === t.id) ?? t);
  showReminderModal.value = true;
}

function onRemindersSent() {
  showReminderModal.value = false;
  clearSelection();
  // Rafraîchir l'historique affiché
  if (historyTargetResponse.value) {
    historyTargetResponse.value = responsesWithDetails.value.find(r => r.id === historyTargetResponse.value.id) ?? historyTargetResponse.value;
  }
}

// --- Clôture manuelle ---
const { confirmer } = useConfirmation();
const cloture = ref(false);

async function cloturer() {
  const ok = await confirmer({
    titre: 'Clôturer le questionnaire',
    message: 'Plus aucune réponse ne sera acceptée et le bilan sera envoyé à l\'auteur et au référent qualité. Continuer ?',
    libelle: 'Clôturer',
    danger: true,
  });
  if (!ok) return;
  cloture.value = true;
  try {
    await cloturerQuestionnaireService(surveyId, true);
    if (surveyStore.currentSurvey) surveyStore.currentSurvey.status = 'closed';
  } catch (e) {
    // apiCall a affiché l'erreur du serveur.
  } finally {
    cloture.value = false;
  }
}

function openHistoryModal(response: any) {
  historyTargetResponse.value = response;
  showHistoryModal.value = true;
}

function relancerDepuisHistorique(response: any) {
  showHistoryModal.value = false;
  openReminderModal([response]);
}

// --- Store data ---
const analytics = computed(() => responseStore.getSurveyAnalytics(surveyId));

const responses = computed(() =>
  responseStore.responsesBySurvey(surveyId)
);

const participants = computed(() =>
  responseStore.participants.filter(p =>
    responses.value.some(r => r.participantId === p.id)
  )
);

const responsesWithDetails = computed(() => {
  return responses.value.map(response => {
    const participant = participants.value.find(p => p.id === response.participantId);
    const history = response.remindersHistory || [];
    const count = response.remindersCount ?? history.length;
    const lastReminder = response.remindedAt || (history.length > 0 ? history[history.length - 1].sentAt : null);

    return {
      ...response,
      participantName: participant?.name || participant?.email || 'Participant anonyme',
      participantEmail: participant?.email || '',
      statusLabel: getStatusLabel(response),
      statusSeverity: getStatusSeverity(response),
      progress: getProgress(response),
      remindersCount: count,
      remindedAt: lastReminder ? new Date(lastReminder) : null,
      remindersHistory: history,
      remindersStatus: count > 0 ? 'Relancé' : 'Non relancé',
    };
  }).sort((a, b) => b.lastActivity.getTime() - a.lastActivity.getTime());
});

const filteredResponses = ref<any[]>([]);

function onFilter(event: any) {
  filteredResponses.value = event.filteredValue;
}

watch(responsesWithDetails, (newVal) => {
  filteredResponses.value = newVal;
  if (selectedResponses.value.length > 0) {
    const existingIds = new Set(newVal.map(r => r.id));
    selectedResponses.value = selectedResponses.value.filter(r => existingIds.has(r.id));
  }
}, { immediate: true });

/** Statut d'après le serveur (les réponses d'un questionnaire anonyme ne sont pas transmises). */
function estCommence(response: Response): boolean {
  return !!response.metadata?.started || (response.answers && Object.keys(response.answers).length > 0);
}

function getStatusLabel(response: Response): string {
  if (response.completed) return 'Terminé';
  return estCommence(response) ? 'En cours' : 'Non commencé';
}

function getStatusSeverity(response: Response): 'success' | 'warn' | 'secondary' {
  if (response.completed) return 'success';
  return estCommence(response) ? 'warn' : 'secondary';
}

function getProgress(response: Response): number {
  if (response.completed) return 100;
  if (!survey.value || !survey.value.sections) return 0;

  const totalQuestions = survey.value.sections.reduce((sum: number, section: any) =>
    sum + (section.questions?.length || 0), 0
  );

  const answeredQuestions = response.answers ? Object.keys(response.answers).length : 0;

  return totalQuestions > 0 ? Math.round((answeredQuestions / totalQuestions) * 100) : 0;
}

function viewResponse(response: Response) {
  selectedResponse.value = response;
  showResponseDetail.value = true;
}

// La fenêtre a déjà informé du résultat et le store a rechargé la liste.
function handleParticipantsInvited() {
  showInviteModal.value = false;
}

async function exportResponses() {
  try {
    showInfo('Génération du fichier Excel en cours...', 'Export en cours');
    await responseStore.triggerExcelExport(surveyId);
    showSuccess('Le fichier Excel a été téléchargé avec succès.', 'Export terminé');
  } catch (error) {
    showDanger('Impossible d\'exporter les réponses.', 'Erreur d\'export');
  }
}

onMounted(async () => {
  await surveyStore.selectSurvey(surveyId);
  await responseStore.fetchSurveyResponses(surveyId);
});
</script>

<template>
  <div>
    <HeaderComponent
      :titre="`Réponses - ${survey?.title || 'Questionnaire'}`"
      description="Gérez, filtrez et exportez les réponses des participants à ce questionnaire."
      :icon="ChartBarIcon"
      color="blue"
      :show-back="true"
    >
      <template #actions>
        <div class="flex items-center gap-2">
          <ActionButtonVertical
            :to="{ name: 'questionnaire_analytics', params: { id: surveyId } }"
            :icon="ChartBarIcon"
            label="Statistiques"
            severity="help"
          />
          <ActionButtonVertical
            :icon="UserPlusIcon"
            label="Inviter"
            severity="primary"
            @click="showInviteModal = true"
          />
          <ActionButtonVertical
            :icon="BellIcon"
            label="Relancer tous"
            severity="warning"
            :disabled="pendingResponses.length === 0"
            @click="openReminderModal(pendingResponses)"
          />
          <ActionButtonVertical
            :icon="ArrowDownTrayIcon"
            label="Exporter"
            severity="secondary"
            :disabled="responses.length === 0"
            @click="exportResponses"
          />
          <ActionButtonVertical
            v-if="survey && survey.status !== 'draft'"
            :to="{ name: 'questionnaire_diffusion', params: { id: surveyId } }"
            icon="pi pi-share-alt"
            label="Diffusion"
            severity="help"
          />
          <ActionButtonVertical
            v-if="survey?.status === 'published'"
            icon="pi pi-lock"
            label="Clôturer"
            severity="danger"
            :disabled="cloture"
            @click="cloturer"
          />
        </div>
      </template>
    </HeaderComponent>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <Kpi
        label="Total invités"
        :value="analytics.totalInvited"
        :icon="UserGroupIcon"
        color="blue"
        :to="{ name: 'questionnaire_analytics', params: { id: surveyId } }"
      />

      <Kpi
        label="Réponses reçues"
        :value="analytics.totalResponses"
        :icon="ChatBubbleLeftRightIcon"
        color="emerald"
        :to="{ name: 'questionnaire_analytics', params: { id: surveyId } }"
      />

      <Kpi
        label="Taux de réponse"
        :value="`${analytics.completionRate.toFixed(1)}%`"
        :icon="CheckCircleIcon"
        color="purple"
        :to="{ name: 'questionnaire_analytics', params: { id: surveyId } }"
      />

      <Kpi
        label="Temps moyen"
        :value="formatDuration(analytics.averageTimeSpent)"
        :icon="ClockIcon"
        color="amber"
        :to="{ name: 'questionnaire_analytics', params: { id: surveyId } }"
      />
    </div>

    <!-- Responses List -->
    <Card
      v-if="responses.length > 0"
      title="Liste des réponses"
      :subtitle="`${filteredResponses.length} participant(s) affiché(s) sur ${responses.length}`"
      icon="pi pi-table"
      color="blue"
      body-class="p-4 sm:p-6"
    >
      <template #header-actions>
        <IconField>
          <InputIcon>
            <i class="pi pi-search" />
          </InputIcon>
          <InputText
            v-model="filters['global'].value"
            placeholder="Recherche rapide..."
            class="py-1 px-3 text-sm rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800"
          />
        </IconField>
      </template>

      <!-- Bulk Actions Bar -->
      <transition name="slide-down">
        <div
          v-if="selectedResponses.length > 0"
          class="flex flex-wrap items-center justify-between gap-3 p-3 mb-4 bg-primary-50/90 dark:bg-slate-800/90 border border-primary-200 dark:border-primary-800/60 rounded-2xl shadow-xs"
        >
          <div class="flex items-center gap-2.5">
            <span class="inline-flex items-center justify-center min-w-6 h-6 px-1.5 rounded-full bg-primary-600 text-white text-sm font-bold">
              {{ selectedResponses.length }}
            </span>
            <span class="text-sm sm:text-sm font-semibold text-slate-800 dark:text-slate-100">
              {{ selectedResponses.length }} ligne{{ selectedResponses.length > 1 ? 's' : '' }} sélectionnée{{ selectedResponses.length > 1 ? 's' : '' }}
            </span>
            <Tag
              v-if="selectedPendingResponses.length > 0"
              :value="`${selectedPendingResponses.length} à relancer`"
              severity="warn"
              class="text-sm font-sans"
            />
            <span v-else class="text-sm text-slate-500 dark:text-slate-400">
              (toutes terminées)
            </span>
          </div>

          <div class="flex items-center flex-wrap gap-2">
            <Button
              v-if="selectedPendingResponses.length > 0"
              type="button"
              severity="warn"
              size="small"
              icon="pi pi-bell"
              :label="`Relancer (${selectedPendingResponses.length})`"
              class="text-sm !py-1.5 !px-3 shadow-xs"
              @click="openReminderModal(selectedPendingResponses)"
            />
            <Button
              type="button"
              severity="secondary"
              outlined
              size="small"
              icon="pi pi-download"
              :label="`Exporter la sélection (${selectedResponses.length})`"
              class="text-sm !py-1.5 !px-3"
              @click="exportSelectedCsv"
            />
            <Button
              type="button"
              severity="secondary"
              text
              size="small"
              icon="pi pi-times"
              label="Désélectionner"
              class="text-sm !py-1.5 !px-2.5"
              @click="clearSelection"
            />
          </div>
        </div>
      </transition>

      <DataTable
        v-model:filters="filters"
        v-model:selection="selectedResponses"
        :value="responsesWithDetails"
        @filter="onFilter"
        dataKey="id"
        filterDisplay="row"
        :globalFilterFields="['participantName', 'participantEmail']"
        paginator
        :rows="10"
        :rowsPerPageOptions="[10, 25, 50, 100]"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        currentPageReportTemplate="Affichage de {first} à {last} sur {totalRecords} réponses"
        class="p-datatable-responsive p-datatable-sm"
      >
        <template #header>
          <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
            <!-- Quick Selection Actions -->
            <div class="flex items-center gap-1.5 flex-wrap">
              <Button
                type="button"
                size="small"
                severity="secondary"
                outlined
                :icon="isAllSelected ? 'pi pi-check-square' : 'pi pi-square'"
                :label="isAllSelected ? 'Tout décocher' : 'Tout cocher'"
                class="text-sm !py-1 !px-2.5"
                @click="isAllSelected ? clearSelection() : selectAllRows()"
              />
              <Button
                type="button"
                size="small"
                :severity="isAllPendingSelected ? 'warn' : 'secondary'"
                outlined
                icon="pi pi-bell"
                :label="`Cocher ceux à relancer (${filteredPendingCount})`"
                :disabled="filteredPendingCount === 0"
                class="text-sm !py-1 !px-2.5"
                @click="selectAllPending()"
              />
            </div>
          </div>
        </template>

        <template #empty>
          <EmptyState
            title="Aucune réponse correspondante"
            description="Aucun participant ne correspond aux critères de recherche actuels."
            icon="pi pi-search"
            color="gray"
            compact
          />
        </template>

        <!-- Selection Checkbox Column -->
        <Column selectionMode="multiple" headerStyle="width: 3.5rem" style="width: 3.5rem" :exportable="false" />

        <Column field="participantName" header="Participant" sortable style="min-width: 15rem" :showFilterMenu="false">
          <template #body="{ data }">
            <div class="flex items-center">
              <div class="w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                  {{ initiales(data.participantName) }}
                </span>
              </div>
              <div class="ml-3">
                <div class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ data.participantName }}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                  {{ data.participantEmail }}
                </div>
              </div>
            </div>
          </template>
          <template #filter="{ filterModel, filterCallback }">
            <InputText v-model="filterModel.value" type="text" @input="filterCallback()" placeholder="Filtrer par nom"
              class="w-full text-sm border border-gray-300 dark:border-gray-700 rounded p-1 px-2 focus:ring-1 focus:ring-primary-500" />
          </template>
        </Column>

        <Column field="statusLabel" header="Statut" sortable style="min-width: 10rem" :showFilterMenu="false">
          <template #body="{ data }">
            <Tag
              :value="data.statusLabel"
              :severity="getStatusSeverity(data)"
              class="text-sm font-semibold font-sans"
            />
          </template>
          <template #filter="{ filterModel, filterCallback }">
            <Select v-model="filterModel.value" @change="filterCallback()" :options="['Terminé', 'En cours', 'Non commencé']" placeholder="Tous" :showClear="true" class="w-full text-sm border border-gray-300 dark:border-gray-700 rounded focus:ring-1 focus:ring-primary-500" />
          </template>
        </Column>

        <Column field="progress" header="Progression" sortable style="min-width: 12rem">
          <template #body="{ data }">
            <div class="flex items-center w-full">
              <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-2">
                <div class="bg-primary-600 h-2 rounded-full" :style="{ width: `${data.progress}%` }" />
              </div>
              <span class="text-sm text-gray-600 dark:text-gray-400 shrink-0">
                {{ data.progress }}%
              </span>
            </div>
          </template>
        </Column>

        <Column field="remindersStatus" sortField="remindersCount" header="Relances" sortable style="min-width: 12rem" :showFilterMenu="false">
          <template #body="{ data }">
            <div class="flex flex-col gap-0.5">
              <div class="flex items-center gap-1.5">
                <button
                  v-if="data.remindersCount > 0"
                  type="button"
                  class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-sm font-semibold bg-amber-50 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60 hover:bg-amber-100 dark:hover:bg-amber-900/60 transition-colors cursor-pointer"
                  title="Cliquer pour consulter l'historique des relances"
                  @click="openHistoryModal(data)"
                >
                  <i class="pi pi-bell text-sm" />
                  <span>{{ data.remindersCount }} relance{{ data.remindersCount > 1 ? 's' : '' }}</span>
                </button>
                <span v-else class="text-sm text-slate-400 dark:text-slate-500 italic">
                  Aucune relance
                </span>
              </div>
              <span v-if="data.remindedAt" class="text-sm text-slate-500 dark:text-slate-400">
                Dernière : {{ formatRelativeTime(data.remindedAt) }}
              </span>
            </div>
          </template>
          <template #filter="{ filterModel, filterCallback }">
            <Select
              v-if="filterModel"
              v-model="filterModel.value"
              :options="['Relancé', 'Non relancé']"
              placeholder="Tous"
              :showClear="true"
              class="w-full text-sm border border-gray-300 dark:border-gray-700 rounded focus:ring-1 focus:ring-primary-500"
              @change="filterCallback()"
            />
          </template>
        </Column>

        <Column field="lastActivity" header="Dernière activité" sortable style="min-width: 12rem">
          <template #body="{ data }">
            {{ formatRelativeTime(data.lastActivity) }}
          </template>
        </Column>

        <Column header="Actions" style="min-width: 18rem">
          <template #body="{ data }">
            <div class="flex items-center gap-1">
              <Button
                type="button"
                icon="pi pi-eye"
                label="Voir"
                text
                size="small"
                severity="info"
                class="text-sm !py-1 !px-2"
                @click="viewResponse(data)"
              />
              <Button
                type="button"
                icon="pi pi-history"
                label="Historique"
                text
                size="small"
                :severity="data.remindersCount > 0 ? 'warn' : 'secondary'"
                title="Consulter l'historique des relances"
                class="text-sm !py-1 !px-2"
                @click="openHistoryModal(data)"
              />
              <Button
                v-if="!data.completed"
                type="button"
                icon="pi pi-bell"
                label="Relancer"
                text
                size="small"
                severity="warn"
                class="text-sm !py-1 !px-2"
                @click="openReminderModal([data])"
              />
            </div>
          </template>
        </Column>
      </DataTable>
    </Card>

    <!-- Empty State (No responses at all) -->
    <EmptyState
      v-else
      title="Aucune réponse reçue"
      description="Aucune réponse n'a encore été enregistrée pour ce questionnaire. Vous pouvez commencer par inviter des participants."
      icon="pi pi-inbox"
      color="blue"
      action-label="Inviter des participants"
      action-icon="pi pi-user-plus"
      @action="showInviteModal = true"
    />

    <!-- Invite Participants Modal -->
    <InviteParticipantsModal v-if="showInviteModal" :survey-id="surveyId" @close="showInviteModal = false"
      @invited="handleParticipantsInvited" />

    <!-- Response Detail Modal -->
    <ResponseDetailModal v-if="showResponseDetail" :response="selectedResponse" :survey="survey"
      @close="showResponseDetail = false" />

    <!-- Relance -->
    <ReminderSendModal
      :visible="showReminderModal"
      :survey-id="surveyId"
      :survey-title="survey?.title || ''"
      :targets="reminderTargets"
      @close="showReminderModal = false"
      @sent="onRemindersSent"
    />

    <!-- Historique des relances -->
    <ReminderHistoryModal
      :visible="showHistoryModal"
      :response="historyTargetResponse"
      @close="showHistoryModal = false"
      @relancer="relancerDepuisHistorique"
    />
  </div>
</template>


<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.25s ease;
}
.slide-down-enter-from,
.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
