<script setup lang="ts">
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
  EnvelopeIcon,
  UsersIcon,
} from '@heroicons/vue/24/outline';
import { useSurveyStore } from '@/stores/survey';
import { useResponseStore } from '@/stores/responses';
import { useUIStore } from '@/stores/ui';
import InviteParticipantsModal from '@/components/Questionnaire/InviteParticipantsModal.vue';
import ResponseDetailModal from '@/components/Questionnaire/ResponseDetailModal.vue';
import ActionButtonVertical from '@components/components/ActionButtonVertical.vue';
import Button from 'primevue/button';
import type { Response } from '@/types/survey';
import { formatRelativeTime, formatDuration } from '@/utils/date';
import { HeaderComponent, Kpi, Card } from '@components';

const route = useRoute();
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
});
const showInviteModal = ref(false);
const showResponseDetail = ref(false);
const selectedResponse = ref<Response | null>(null);

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
  reminderTargets.value = targets;
  reminderSubject.value = DEFAULT_SUBJECT.value;
  reminderMessage.value = DEFAULT_MESSAGE.value;
  showReminderModal.value = true;
}

async function confirmSendReminders() {
  isSendingReminders.value = true;
  // Simulate async send
  await new Promise(resolve => setTimeout(resolve, 800));
  isSendingReminders.value = false;
  showReminderModal.value = false;

  const names = reminderTargets.value
    .map(r => getParticipantName(r.participantId))
    .join(', ');

  uiStore.addNotification(
    'success',
    'Rappels envoyés',
    `${reminderTargets.value.length} rappel${reminderTargets.value.length > 1 ? 's envoyés' : ' envoyé'} à : ${names.length > 60 ? names.slice(0, 60) + '…' : names}.`
  );
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
    return {
      ...response,
      participantName: participant?.name || participant?.email || 'Participant anonyme',
      participantEmail: participant?.email || '',
      participantGroup: participant?.group || '',
      statusLabel: getStatusLabel(response),
      progress: getProgress(response),
    };
  }).sort((a, b) => b.lastActivity.getTime() - a.lastActivity.getTime());
});

const filteredResponses = ref<any[]>([]);

function onFilter(event: any) {
  filteredResponses.value = event.filteredValue;
}

watch(responsesWithDetails, (newVal) => {
  filteredResponses.value = newVal;
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

function exportResponses() {
  uiStore.addNotification(
    'success',
    'Export en cours',
    'Vos données sont en cours d\'exportation.'
  );
}

onMounted(async () => {
  await surveyStore.selectSurvey(surveyId);
  await responseStore.fetchSurveyResponses(surveyId);
});
</script>

<template>
  <div>
    <HeaderComponent
    :titre="`Réponses - ${survey?.title}`"
    description="`Gérez et analysez les réponses de votre questionnaire`"
    :icon="ChartBarIcon"
    >
    <template v-slot:actions>
      <div class="flex items-center space-x-3">
        <ActionButtonVertical :to="{ name: 'questionnaire_analytics', params: { id: surveyId } }" :icon="ChartBarIcon"
          label="Voir les statistiques" severity="help" />
        <ActionButtonVertical :icon="UserPlusIcon" label="Inviter des participants" severity="primary"
          @click="showInviteModal = true" />
        <ActionButtonVertical :icon="BellIcon" label="Relancer tous" severity="warning"
          @click="openReminderModal(pendingResponses)" />
        <ActionButtonVertical :icon="ArrowDownTrayIcon" label="Exporter" severity="secondary"
          @click="exportResponses" />
      </div>
    </template>
    </HeaderComponent>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
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
        color="green"
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
        color="yellow"
        :to="{ name: 'questionnaire_analytics', params: { id: surveyId } }"
      />
    </div>

    <!-- Responses List -->
    <Card v-if="responses.length > 0">
      <DataTable
        v-model:filters="filters"
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
          <div class="flex justify-between items-center gap-4 mb-2">
            <div class="flex items-baseline space-x-2">
              <h3 class="text-base font-semibold text-gray-800 dark:text-white">Liste des réponses </h3>
              <span class="text-xs text-gray-500 dark:text-gray-400 font-normal">
                &nbsp;({{ filteredResponses.length }} réponse(s) trouvée(s))
              </span>
            </div>
            <IconField>
              <InputIcon>
                <i class="pi pi-search" />
              </InputIcon>
              <InputText v-model="filters['global'].value" placeholder="Rechercher..."
                class="py-1.5 px-3 text-sm rounded-lg shadow-sm border border-gray-300 dark:border-gray-700 focus:outline-none focus:ring-1 focus:ring-primary-500" />
            </IconField>
          </div>
        </template>

        <template #empty>
          <div class="text-center py-12">
            <ChatBubbleLeftRightIcon class="w-16 h-16 text-gray-400 mx-auto mb-4" />
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
              Aucune réponse correspondante
            </h3>
            <p class="text-gray-500 dark:text-gray-400">
              Aucune réponse ne correspond à vos filtres de recherche.
            </p>
          </div>
        </template>

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
            <span :class="[
              'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
              getStatusColor(data)
            ]">
              {{ data.statusLabel }}
            </span>
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

        <Column field="lastActivity" header="Dernière activité" sortable style="min-width: 12rem">
          <template #body="{ data }">
            {{ formatRelativeTime(data.lastActivity) }}
          </template>
        </Column>

        <Column header="Actions" style="min-width: 10rem">
          <template #body="{ data }">
            <button @click="viewResponse(data)"
              class="text-primary-600 dark:text-primary-400 hover:text-primary-900 dark:hover:text-primary-300 mr-3 cursor-pointer">
              Voir
            </button>
            <button v-if="!data.completed" @click="openReminderModal([data])"
              class="text-yellow-600 dark:text-yellow-400 hover:text-yellow-900 dark:hover:text-yellow-300 cursor-pointer">
              Relancer
            </button>
          </template>
        </Column>
      </DataTable>
    </Card>

    <!-- Empty State (No responses at all) -->
    <div v-else class="card text-center py-12">
      <ChatBubbleLeftRightIcon class="w-16 h-16 text-gray-400 mx-auto mb-4" />
      <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
        Aucune réponse reçue
      </h3>
      <p class="text-gray-600 dark:text-gray-400 mb-4">
        Aucune réponse n'a encore été reçue pour ce questionnaire.
      </p>
      <button @click="showInviteModal = true" class="btn-primary cursor-pointer">
        Inviter des participants
      </button>
    </div>

    <!-- Invite Participants Modal -->
    <InviteParticipantsModal v-if="showInviteModal" :survey-id="surveyId" @close="showInviteModal = false"
      @invited="handleParticipantsInvited" />

    <!-- Response Detail Modal -->
    <ResponseDetailModal v-if="showResponseDetail" :response="selectedResponse" :survey="survey"
      @close="showResponseDetail = false" />

    <!-- Reminder Modal -->
    <Transition name="fade">
      <div v-if="showReminderModal"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
        @click.self="showReminderModal = false">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
          <!-- Header -->
          <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900/40 rounded-xl flex items-center justify-center">
                <BellIcon class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
              </div>
              <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Envoyer un rappel</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  {{ reminderTargets.length }} participant{{ reminderTargets.length > 1 ? 's' : '' }} ciblé{{ reminderTargets.length > 1 ? 's' : '' }}
                </p>
              </div>
            </div>
            <button @click="showReminderModal = false"
              class="p-2 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>

          <div class="p-6 space-y-6">
            <!-- Recipients list -->
            <div>
              <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center">
                <UsersIcon class="w-4 h-4 mr-2" />
                Destinataires
              </h3>
              <div class="max-h-36 overflow-y-auto space-y-1.5 pr-1">
                <div v-for="response in reminderTargets" :key="response.id"
                  class="flex items-center justify-between bg-gray-50 dark:bg-gray-700/50 rounded-lg px-3 py-2">
                  <div class="flex items-center space-x-2">
                    <div class="w-7 h-7 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center text-xs font-medium text-gray-700 dark:text-gray-300">
                      {{ getParticipantInitials(response.participantId) }}
                    </div>
                    <div>
                      <p class="text-sm font-medium text-gray-900 dark:text-white">{{ getParticipantName(response.participantId) }}</p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">{{ getParticipantEmail(response.participantId) }}</p>
                    </div>
                  </div>
                  <span :class="['text-xs px-2 py-0.5 rounded-full font-medium', getStatusColor(response)]">
                    {{ getStatusLabel(response) }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Message preview / editor -->
            <div>
              <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center">
                  <EnvelopeIcon class="w-4 h-4 mr-2" />
                  Message de rappel
                </h3>
                <button @click="resetReminderMessage"
                  class="text-xs text-primary-600 dark:text-primary-400 hover:underline">
                  Réinitialiser
                </button>
              </div>

              <!-- Subject -->
              <div class="mb-3">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Objet</label>
                <input v-model="reminderSubject" type="text"
                  class="w-full input-field text-sm"
                  placeholder="Objet de l'email..." />
              </div>

              <!-- Body -->
              <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Corps du message</label>
                <textarea v-model="reminderMessage" rows="8"
                  class="w-full input-field text-sm resize-none font-mono leading-relaxed"
                  placeholder="Corps du message..." />
              </div>

              <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">
                Les variables <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">{{ '{prenom}' }}</code>,
                <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">{{ '{lien}' }}</code> et
                <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">{{ '{questionnaire}' }}</code>
                seront remplacées automatiquement.
              </p>
            </div>
          </div>

          <!-- Footer -->
          <div class="flex items-center justify-end space-x-3 p-6 border-t border-gray-200 dark:border-gray-700">
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
      </div>
    </Transition>
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
