<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
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
  EnvelopeIcon,
  UsersIcon,
} from '@heroicons/vue/24/outline';
import { useSurveyStore } from '@/stores/survey';
import { useResponseStore } from '@/stores/responses';
import { useUIStore } from '@/stores/ui';
import InviteParticipantsModal from '@/components/Questionnaire/InviteParticipantsModal.vue';
import ResponseDetailModal from '@/components/Questionnaire/ResponseDetailModal.vue';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Tag from 'primevue/tag';
import type { Response } from '@/types/survey';
import { formatRelativeTime, formatDuration, formatDateTime } from '@/utils/date';
import { DialogHeader, FormField, FormSection } from '@/components/Form';
import { HeaderComponent, Kpi, Card, EmptyState, ActionButtonVertical } from '@components';

const route = useRoute();
const router = useRouter();
const surveyStore = useSurveyStore();
const responseStore = useResponseStore();
const uiStore = useUIStore();

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
  participantGroup: { value: null, matchMode: FilterMatchMode.EQUALS },
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
    uiStore.addNotification(
      'info',
      'Sélection mise à jour',
      `${pendings.length} participant${pendings.length > 1 ? 's à relancer sélectionnés' : ' à relancer sélectionné'}.`
    );
  } else {
    uiStore.addNotification(
      'info',
      'Aucun relançable',
      'Tous les participants affichés ont déjà répondu au questionnaire.'
    );
  }
}

function clearSelection() {
  selectedResponses.value = [];
}

function exportSelectedCsv() {
  if (selectedResponses.value.length === 0) return;
  const headers = ['Nom', 'Email', 'Groupe', 'Statut', 'Progression (%)', 'Relances (nb)', 'Dernière relance', 'Dernière activité'];
  const rows = selectedResponses.value.map(r => [
    r.participantName || '',
    r.participantEmail || '',
    r.participantGroup || '',
    r.statusLabel || '',
    r.progress !== undefined ? String(r.progress) : '',
    r.remindersCount !== undefined ? String(r.remindersCount) : '0',
    r.remindedAt ? new Date(r.remindedAt).toLocaleString('fr-FR') : '',
    r.lastActivity ? new Date(r.lastActivity).toLocaleString('fr-FR') : ''
  ]);

  const csvContent = [headers, ...rows]
    .map(row => row.map(val => `"${String(val).replace(/"/g, '""')}"`).join(';'))
    .join('\r\n');

  const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.setAttribute('href', url);
  link.setAttribute('download', `reponses_selection_${surveyId}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);

  uiStore.addNotification(
    'success',
    'Export CSV généré',
    `${selectedResponses.value.length} ligne${selectedResponses.value.length > 1 ? 's exportées' : ' exportée'}.`
  );
}

// --- Reminder modal ---
const showReminderModal = ref(false);
const reminderTargets = ref<Response[]>([]);
const isSendingReminders = ref(false);

const DEFAULT_SUBJECT = computed(() =>
  `Rappel : questionnaire "${survey.value?.title || ''}" en attente`
);

const DEFAULT_MESSAGE = computed(() =>
  `Bonjour {prenom},

Nous vous rappelons que le questionnaire "{questionnaire}" est toujours en attente de votre réponse.

Merci de bien vouloir y répondre dès que possible en cliquant sur le lien ci-dessous :
{lien}

Cordialement,
L'équipe IUT Troyes`
);

const reminderSubject = ref('');
const reminderMessage = ref('');

function resetReminderMessage() {
  reminderSubject.value = DEFAULT_SUBJECT.value;
  reminderMessage.value = DEFAULT_MESSAGE.value;
}

const pendingResponses = computed(() =>
  responses.value.filter(r => !r.completed)
);

function openReminderModal(targets: Response[]) {
  if (!targets || targets.length === 0) {
    uiStore.addNotification('info', 'Aucun participant', 'Aucun participant à relancer dans cette sélection.');
    return;
  }
  reminderTargets.value = targets;
  reminderSubject.value = DEFAULT_SUBJECT.value;
  reminderMessage.value = DEFAULT_MESSAGE.value;
  showReminderModal.value = true;
}

async function confirmSendReminders() {
  isSendingReminders.value = true;

  const targetIds = reminderTargets.value.map(r => r.id);
  await responseStore.sendReminders(
    surveyId,
    targetIds,
    reminderSubject.value,
    reminderMessage.value
  );

  isSendingReminders.value = false;
  showReminderModal.value = false;

  // Refresh active history modal participant if open
  if (historyTargetResponse.value) {
    const updated = responsesWithDetails.value.find(r => r.id === historyTargetResponse.value.id);
    if (updated) {
      historyTargetResponse.value = updated;
    }
  }

  const names = reminderTargets.value
    .map(r => getParticipantName(r.participantId))
    .join(', ');

  uiStore.addNotification(
    'success',
    'Rappels envoyés',
    `${reminderTargets.value.length} rappel${reminderTargets.value.length > 1 ? 's envoyés' : ' envoyé'} à : ${names.length > 60 ? names.slice(0, 60) + '…' : names}.`
  );

  clearSelection();
}

function openHistoryModal(response: any) {
  historyTargetResponse.value = response;
  showHistoryModal.value = true;
}

const sortedReminderHistory = computed(() => {
  if (!historyTargetResponse.value?.remindersHistory) return [];
  return [...historyTargetResponse.value.remindersHistory].sort((a, b) => {
    const da = new Date(a.sentAt).getTime();
    const db = new Date(b.sentAt).getTime();
    return db - da;
  });
});

function triggerDirectReminderFromHistory() {
  if (!historyTargetResponse.value) return;
  const target = historyTargetResponse.value;
  showHistoryModal.value = false;
  openReminderModal([target]);
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

const availableGroups = computed(() => {
  const groups = new Set<string>();
  participants.value.forEach(p => {
    if (p.group) groups.add(p.group);
  });
  return Array.from(groups).sort();
});

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
      participantGroup: participant?.group || '',
      statusLabel: getStatusLabel(response),
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

function getParticipantName(participantId?: string): string {
  const participant = participants.value.find(p => p.id === participantId);
  return participant?.name || participant?.email || 'Participant anonyme';
}

function getParticipantEmail(participantId?: string): string {
  const participant = participants.value.find(p => p.id === participantId);
  return participant?.email || '';
}

function getParticipantInitials(participantId?: string): string {
  const name = getParticipantName(participantId);
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
}

function getStatusLabel(response: Response): string {
  if (response.completed) return 'Terminé';
  if (Object.keys(response.answers).length > 0) return 'En cours';
  return 'Non commencé';
}

function getStatusSeverity(response: Response): 'success' | 'warn' | 'secondary' {
  if (response.completed) return 'success';
  if (response.answers && Object.keys(response.answers).length > 0) return 'warn';
  return 'secondary';
}

function getStatusColor(response: Response): string {
  if (response.completed) {
    return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
  }
  if (response.answers && Object.keys(response.answers).length > 0) {
    return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
  }
  return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
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

function getSurveyUrl(response: Response): string {
  const href = router.resolve({ name: 'questionnaire_take-survey', params: { token: response.id } }).href;
  return new URL(href, window.location.origin).toString();
}

async function copySurveyUrl(response: Response) {
  const url = getSurveyUrl(response);
  try {
    if (navigator.clipboard?.writeText) {
      await navigator.clipboard.writeText(url);
    } else {
      const ta = document.createElement('textarea');
      ta.value = url;
      document.body.appendChild(ta);
      ta.select();
      document.execCommand('copy');
      document.body.removeChild(ta);
    }
    uiStore.addNotification('success', 'Lien copié', 'L\'URL du questionnaire a été copiée dans le presse-papiers.');
  } catch (e) {
    uiStore.addNotification('danger', 'Erreur', `Impossible de copier le lien : ${url}`);
  }
}

function viewResponse(response: Response) {
  selectedResponse.value = response;
  showResponseDetail.value = true;
}

async function handleParticipantsInvited() {
  showInviteModal.value = false;
  uiStore.addNotification(
    'success',
    'Invitations envoyées',
    'Les invitations ont été envoyées avec succès.'
  );
  await responseStore.fetchSurveyResponses(surveyId);
}

async function exportResponses() {
  try {
    uiStore.addNotification(
      'info',
      'Export en cours',
      'Génération du fichier Excel en cours...'
    );
    await responseStore.triggerExcelExport(surveyId);
    uiStore.addNotification(
      'success',
      'Export terminé',
      'Le fichier Excel a été téléchargé avec succès.'
    );
  } catch (error) {
    uiStore.addNotification(
      'danger',
      'Erreur d\'export',
      'Impossible d\'exporter les réponses.'
    );
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
            class="py-1 px-3 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800"
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
            <span class="inline-flex items-center justify-center min-w-6 h-6 px-1.5 rounded-full bg-primary-600 text-white text-xs font-bold">
              {{ selectedResponses.length }}
            </span>
            <span class="text-xs sm:text-sm font-semibold text-slate-800 dark:text-slate-100">
              {{ selectedResponses.length }} ligne{{ selectedResponses.length > 1 ? 's' : '' }} sélectionnée{{ selectedResponses.length > 1 ? 's' : '' }}
            </span>
            <Tag
              v-if="selectedPendingResponses.length > 0"
              :value="`${selectedPendingResponses.length} à relancer`"
              severity="warn"
              class="text-xs font-sans"
            />
            <span v-else class="text-xs text-slate-500 dark:text-slate-400">
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
              class="text-xs !py-1.5 !px-3 shadow-xs"
              @click="openReminderModal(selectedPendingResponses)"
            />
            <Button
              type="button"
              severity="secondary"
              outlined
              size="small"
              icon="pi pi-download"
              :label="`Exporter la sélection (${selectedResponses.length})`"
              class="text-xs !py-1.5 !px-3"
              @click="exportSelectedCsv"
            />
            <Button
              type="button"
              severity="secondary"
              text
              size="small"
              icon="pi pi-times"
              label="Désélectionner"
              class="text-xs !py-1.5 !px-2.5"
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
                class="text-xs !py-1 !px-2.5"
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
                class="text-xs !py-1 !px-2.5"
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
                  {{ getParticipantInitials(data.participantId) }}
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
              class="w-full text-xs border border-gray-300 dark:border-gray-700 rounded p-1 px-2 focus:ring-1 focus:ring-primary-500" />
          </template>
        </Column>

        <Column field="participantGroup" header="Groupe" sortable style="min-width: 10rem" :showFilterMenu="false">
          <template #body="{ data }">
            <span class="text-sm text-gray-650 dark:text-gray-400">
              {{ data.participantGroup || '-' }}
            </span>
          </template>
          <template #filter="{ filterModel, filterCallback }">
            <Select v-model="filterModel.value" @change="filterCallback()" :options="availableGroups" placeholder="Tous" :showClear="true" class="w-full text-xs border border-gray-300 dark:border-gray-700 rounded focus:ring-1 focus:ring-primary-500" />
          </template>
        </Column>

        <Column field="statusLabel" header="Statut" sortable style="min-width: 10rem" :showFilterMenu="false">
          <template #body="{ data }">
            <Tag
              :value="data.statusLabel"
              :severity="getStatusSeverity(data)"
              class="text-xs font-semibold font-sans"
            />
          </template>
          <template #filter="{ filterModel, filterCallback }">
            <Select v-model="filterModel.value" @change="filterCallback()" :options="['Terminé', 'En cours', 'Non commencé']" placeholder="Tous" :showClear="true" class="w-full text-xs border border-gray-300 dark:border-gray-700 rounded focus:ring-1 focus:ring-primary-500" />
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
                  class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60 hover:bg-amber-100 dark:hover:bg-amber-900/60 transition-colors cursor-pointer"
                  title="Cliquer pour consulter l'historique des relances"
                  @click="openHistoryModal(data)"
                >
                  <i class="pi pi-bell text-[10px]" />
                  <span>{{ data.remindersCount }} relance{{ data.remindersCount > 1 ? 's' : '' }}</span>
                </button>
                <span v-else class="text-xs text-slate-400 dark:text-slate-500 italic">
                  Aucune relance
                </span>
              </div>
              <span v-if="data.remindedAt" class="text-[11px] text-slate-500 dark:text-slate-400">
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
              class="w-full text-xs border border-gray-300 dark:border-gray-700 rounded focus:ring-1 focus:ring-primary-500"
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
                class="text-xs !py-1 !px-2"
                @click="viewResponse(data)"
              />
              <Button
                type="button"
                icon="pi pi-link"
                label="Lien"
                text
                size="small"
                severity="secondary"
                title="Copier l'URL du questionnaire de ce participant"
                class="text-xs !py-1 !px-2"
                @click="copySurveyUrl(data)"
              />
              <Button
                type="button"
                icon="pi pi-history"
                label="Historique"
                text
                size="small"
                :severity="data.remindersCount > 0 ? 'warn' : 'secondary'"
                title="Consulter l'historique des relances"
                class="text-xs !py-1 !px-2"
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
                class="text-xs !py-1 !px-2"
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

    <!-- Reminder Modal -->
    <!-- Reminder Modal -->
    <Dialog
      :style="{ width: '92vw', maxWidth: '680px' }"
      :visible="showReminderModal"
      :modal="true"
      :closable="true"
      :draggable="false"
      @update:visible="showReminderModal = $event"
    >
      <template #header>
        <DialogHeader
          :icon="BellIcon"
          tone="amber"
          title="Envoyer un rappel"
          :subtitle="`${reminderTargets.length} participant${reminderTargets.length > 1 ? 's' : ''} ciblé${reminderTargets.length > 1 ? 's' : ''}`"
        />
      </template>

      <div class="q-form">
        <!-- Recipients list -->
        <FormSection :icon="UsersIcon" tone="slate" title="Destinataires">
          <div class="max-h-36 overflow-y-auto space-y-1.5 pr-1">
            <div
              v-for="response in reminderTargets"
              :key="response.id"
              class="flex items-center justify-between bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3 py-2"
            >
              <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center text-xs font-bold text-slate-700 dark:text-slate-300">
                  {{ getParticipantInitials(response.participantId) }}
                </div>
                <div>
                  <p class="text-xs font-medium text-slate-900 dark:text-white">{{ getParticipantName(response.participantId) }}</p>
                  <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ getParticipantEmail(response.participantId) }}</p>
                </div>
              </div>
              <Tag
                :value="getStatusLabel(response)"
                :severity="getStatusSeverity(response)"
                class="text-xs font-sans"
              />
            </div>
          </div>
        </FormSection>

        <!-- Message preview / editor -->
        <FormSection :icon="EnvelopeIcon" tone="amber" title="Message de rappel">
          <template #actions>
            <button
              type="button"
              class="text-xs font-semibold text-primary-600 dark:text-primary-400 hover:underline border-0 bg-transparent cursor-pointer"
              @click="resetReminderMessage"
            >
              Réinitialiser
            </button>
          </template>

          <FormField label="Objet du message" for="reminder-subject">
            <input
              id="reminder-subject"
              v-model="reminderSubject"
              type="text"
              class="q-input"
              placeholder="Objet de l'email..."
            />
          </FormField>

          <FormField label="Corps du message" for="reminder-message">
            <textarea
              id="reminder-message"
              v-model="reminderMessage"
              rows="7"
              class="q-input font-mono text-xs leading-relaxed"
              placeholder="Corps du message..."
            />
          </FormField>

          <p class="text-[11px] text-slate-500 dark:text-slate-400">
            Les variables <code class="bg-slate-100 dark:bg-slate-700 px-1 py-0.5 rounded font-mono">{{ '{prenom}' }}</code>,
            <code class="bg-slate-100 dark:bg-slate-700 px-1 py-0.5 rounded font-mono">{{ '{lien}' }}</code> et
            <code class="bg-slate-100 dark:bg-slate-700 px-1 py-0.5 rounded font-mono">{{ '{questionnaire}' }}</code>
            seront remplacées automatiquement.
          </p>
        </FormSection>

        <!-- Actions -->
        <div class="q-actions">
          <Button
            label="Annuler"
            severity="secondary"
            outlined
            @click="showReminderModal = false"
          />
          <Button
            :label="`Envoyer ${reminderTargets.length > 1 ? 'les ' + reminderTargets.length + ' rappels' : 'le rappel'}`"
            severity="warn"
            :loading="isSendingReminders"
            :icon="isSendingReminders ? undefined : 'pi pi-bell'"
            @click="confirmSendReminders"
          />
        </div>
      </div>
    </Dialog>

    <!-- Reminder History Modal -->
    <Dialog
      :style="{ width: '92vw', maxWidth: '720px' }"
      :visible="showHistoryModal"
      :modal="true"
      :closable="true"
      :draggable="false"
      @update:visible="showHistoryModal = $event"
    >
      <template #header>
        <DialogHeader
          :icon="ClockIcon"
          tone="amber"
          title="Historique des relances"
          :subtitle="`${historyTargetResponse?.participantName || 'Participant'} (${historyTargetResponse?.participantEmail || ''})`"
          @close="showHistoryModal = false"
        />
      </template>

      <div v-if="historyTargetResponse" class="space-y-5 pt-1">
        <!-- Participant Summary Banner -->
        <div class="p-3.5 rounded-2xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/70 dark:bg-slate-900/40 flex flex-wrap items-center justify-between gap-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-amber-100 dark:bg-amber-950/70 text-amber-800 dark:text-amber-300 rounded-full flex items-center justify-center font-bold text-sm border border-amber-200 dark:border-amber-800/60 shadow-xs">
              {{ getParticipantInitials(historyTargetResponse.participantId) }}
            </div>
            <div>
              <p class="text-sm font-semibold text-slate-900 dark:text-white">
                {{ historyTargetResponse.participantName }}
              </p>
              <p class="text-xs text-slate-500 dark:text-slate-400">
                {{ historyTargetResponse.participantEmail }} • Groupe : {{ historyTargetResponse.participantGroup || 'Non assigné' }}
              </p>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <Tag
              :value="historyTargetResponse.statusLabel"
              :severity="getStatusSeverity(historyTargetResponse)"
              class="text-xs font-sans"
            />
            <Tag
              :value="`${historyTargetResponse.remindersCount || 0} relance${(historyTargetResponse.remindersCount || 0) > 1 ? 's' : ''}`"
              :severity="(historyTargetResponse.remindersCount || 0) > 0 ? 'warn' : 'secondary'"
              class="text-xs font-sans"
            />
          </div>
        </div>

        <!-- History List / Timeline -->
        <div v-if="historyTargetResponse.remindersHistory && historyTargetResponse.remindersHistory.length > 0" class="space-y-3">
          <div class="flex items-center justify-between pb-1">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
              <i class="pi pi-history text-xs" />
              <span>Détail chronologique des envois ({{ historyTargetResponse.remindersHistory.length }})</span>
            </h4>
            <span class="text-[11px] text-slate-400 dark:text-slate-500">
              Classé du plus récent au plus ancien
            </span>
          </div>

          <div class="relative pl-6 space-y-4 before:content-[''] before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200 dark:before:bg-slate-700">
            <div
              v-for="(item, idx) in sortedReminderHistory"
              :key="item.id || idx"
              class="relative group"
            >
              <!-- Timeline node dot -->
              <div class="absolute -left-6 top-2 w-5 h-5 rounded-full bg-amber-500 dark:bg-amber-400 border-2 border-white dark:border-slate-800 flex items-center justify-center text-white shadow-xs">
                <i class="pi pi-bell text-[9px]" />
              </div>

              <!-- Card Content -->
              <div class="p-3.5 bg-white dark:bg-slate-800/90 border border-slate-200 dark:border-slate-700/80 rounded-xl shadow-xs transition hover:border-amber-300 dark:hover:border-amber-700">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-1.5">
                  <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-slate-900 dark:text-white">
                      Relance #{{ historyTargetResponse.remindersHistory.length - idx }}
                    </span>
                    <Tag
                      :value="item.type === 'bulk' ? 'Relance groupée' : (item.type === 'automatic' ? 'Automatique' : 'Relance individuelle')"
                      :severity="item.type === 'bulk' ? 'info' : (item.type === 'automatic' ? 'help' : 'warn')"
                      class="text-[10px] font-sans !py-0.5 !px-2"
                    />
                  </div>
                  <div class="text-right">
                    <span class="text-xs font-medium text-slate-700 dark:text-slate-300">
                      {{ formatDateTime(item.sentAt) }}
                    </span>
                    <span class="text-[11px] text-slate-400 dark:text-slate-500 block">
                      ({{ formatRelativeTime(item.sentAt) }})
                    </span>
                  </div>
                </div>

                <div v-if="item.subject" class="text-xs font-medium text-slate-800 dark:text-slate-200 mb-1">
                  <span class="text-slate-400 dark:text-slate-500 font-normal">Objet :</span> {{ item.subject }}
                </div>

                <div v-if="item.message" class="mt-2 p-2.5 bg-slate-50 dark:bg-slate-900/50 rounded-lg border border-slate-100 dark:border-slate-800 text-xs text-slate-600 dark:text-slate-300 whitespace-pre-line font-mono text-[11px] leading-relaxed">
                  {{ item.message }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty History State -->
        <div v-else class="py-8 text-center bg-slate-50/50 dark:bg-slate-900/30 border border-dashed border-slate-200 dark:border-slate-700/80 rounded-2xl">
          <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 mb-3">
            <i class="pi pi-bell-slash text-xl" />
          </div>
          <h4 class="text-sm font-semibold text-slate-800 dark:text-slate-200">
            Aucune relance effectuée
          </h4>
          <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto mt-1 mb-4">
            Ce participant n'a encore reçu aucun rappel ou email de relance pour ce questionnaire.
          </p>
          <Button
            v-if="!historyTargetResponse.completed"
            type="button"
            icon="pi pi-bell"
            label="Envoyer une première relance"
            severity="warn"
            size="small"
            class="text-xs shadow-xs"
            @click="triggerDirectReminderFromHistory"
          />
        </div>

        <!-- Modal Actions Footer -->
        <div class="flex items-center justify-between pt-3 border-t border-slate-200 dark:border-slate-700/80">
          <Button
            type="button"
            label="Fermer"
            severity="secondary"
            outlined
            size="small"
            class="text-xs !py-1.5 !px-3"
            @click="showHistoryModal = false"
          />
          <Button
            v-if="!historyTargetResponse.completed"
            type="button"
            icon="pi pi-bell"
            label="Nouvelle relance"
            severity="warn"
            size="small"
            class="text-xs !py-1.5 !px-3 shadow-xs"
            @click="triggerDirectReminderFromHistory"
          />
        </div>
      </div>
    </Dialog>
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
