<template>
  <div class="p-6">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          Réponses - {{ survey?.title }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
          Gérez et analysez les réponses de votre questionnaire
        </p>
      </div>

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
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
      <div class="card">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">Total invités</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ analytics.totalInvited }}
            </p>
          </div>
          <UserGroupIcon class="w-8 h-8 text-blue-500" />
        </div>
      </div>

      <div class="card">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">Réponses reçues</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ analytics.totalResponses }}
            </p>
          </div>
          <ChatBubbleLeftRightIcon class="w-8 h-8 text-green-500" />
        </div>
      </div>

      <div class="card">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">Taux de réponse</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ analytics.completionRate.toFixed(1) }}%
            </p>
          </div>
          <CheckCircleIcon class="w-8 h-8 text-purple-500" />
        </div>
      </div>

      <div class="card">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">Temps moyen</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ formatDuration(analytics.averageTimeSpent) }}
            </p>
          </div>
          <ClockIcon class="w-8 h-8 text-yellow-500" />
        </div>
      </div>
    </div>

    <!-- Filters and Search -->
    <div class="card mb-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <div class="relative">
            <MagnifyingGlassIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
            <input v-model="searchQuery" type="text" placeholder="Rechercher par email ou nom..."
              class="pl-10 input-field w-64" />
          </div>

          <select v-model="statusFilter" class="input-field w-40">
            <option value="all">Tous les statuts</option>
            <option value="completed">Terminé</option>
            <option value="in_progress">En cours</option>
            <option value="not_started">Non commencé</option>
          </select>

          <select v-model="groupFilter" class="input-field w-40">
            <option value="all">Tous les groupes</option>
            <option v-for="group in availableGroups" :key="group" :value="group">
              {{ group }}
            </option>
          </select>
        </div>

        <div class="flex items-center space-x-2">
          <button @click="viewMode = 'table'" :class="[
            'p-2 rounded-lg',
            viewMode === 'table'
              ? 'bg-primary-100 text-primary-600 dark:bg-primary-900 dark:text-primary-400'
              : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'
          ]">
            <TableCellsIcon class="w-5 h-5" />
          </button>
          <button @click="viewMode = 'cards'" :class="[
            'p-2 rounded-lg',
            viewMode === 'cards'
              ? 'bg-primary-100 text-primary-600 dark:bg-primary-900 dark:text-primary-400'
              : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'
          ]">
            <Squares2X2Icon class="w-5 h-5" />
          </button>
        </div>
      </div>
    </div>

    <!-- Bulk actions bar -->
    <Transition name="slide-down">
      <div v-if="selectedIds.size > 0"
        class="mb-4 flex items-center justify-between px-4 py-3 bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-700 rounded-xl">
        <div class="flex items-center space-x-3">
          <BellIcon class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
          <span class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
            {{ selectedIds.size }} participant{{ selectedIds.size > 1 ? 's' : '' }} sélectionné{{ selectedIds.size > 1 ? 's' : '' }}
          </span>
        </div>
        <div class="flex items-center space-x-2">
          <Button
            :label="`Relancer (${selectedPendingResponses.length})`"
            icon="pi pi-bell"
            severity="warn"
            size="small"
            :disabled="selectedPendingResponses.length === 0"
            @click="openReminderModal(selectedPendingResponses)"
          />
          <Button
            label="Annuler"
            severity="secondary"
            size="small"
            text
            @click="clearSelection"
          />
        </div>
      </div>
    </Transition>

    <!-- Responses List -->
    <div class="card">
      <!-- Table View -->
      <div v-if="viewMode === 'table'" class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-4 py-3 w-10">
                <input type="checkbox" :checked="isAllSelected" :indeterminate="isIndeterminate"
                  @change="toggleSelectAll"
                  class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500 cursor-pointer" />
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Participant
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Statut
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Progression
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Dernière activité
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="response in filteredResponses" :key="response.id"
              :class="['hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors', selectedIds.has(response.id) ? 'bg-yellow-50 dark:bg-yellow-900/10' : '']">
              <td class="px-4 py-4">
                <input type="checkbox" :checked="selectedIds.has(response.id)"
                  @change="toggleSelect(response.id)"
                  class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500 cursor-pointer" />
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                      {{ getParticipantInitials(response.participantId) }}
                    </span>
                  </div>
                  <div class="ml-3">
                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                      {{ getParticipantName(response.participantId) }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                      {{ getParticipantEmail(response.participantId) }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="[
                  'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                  getStatusColor(response)
                ]">
                  {{ getStatusLabel(response) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-2">
                    <div class="bg-primary-600 h-2 rounded-full" :style="{ width: `${getProgress(response)}%` }" />
                  </div>
                  <span class="text-sm text-gray-600 dark:text-gray-400">
                    {{ getProgress(response) }}%
                  </span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                {{ formatRelativeTime(response.lastActivity) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button @click="viewResponse(response)"
                  class="text-primary-600 dark:text-primary-400 hover:text-primary-900 dark:hover:text-primary-300 mr-3">
                  Voir
                </button>
                <button v-if="!response.completed" @click="openReminderModal([response])"
                  class="text-yellow-600 dark:text-yellow-400 hover:text-yellow-900 dark:hover:text-yellow-300">
                  Relancer
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Cards View -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="response in filteredResponses" :key="response.id"
          :class="['border rounded-lg p-4 transition-colors cursor-pointer',
            selectedIds.has(response.id)
              ? 'border-yellow-400 bg-yellow-50 dark:bg-yellow-900/10 dark:border-yellow-600'
              : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
          ]"
          @click.stop>
          <div class="flex items-center justify-between mb-3">
            <div class="flex items-center space-x-3">
              <input type="checkbox" :checked="selectedIds.has(response.id)"
                @change="toggleSelect(response.id)"
                class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500 cursor-pointer flex-shrink-0" />
              <div class="w-10 h-10 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                  {{ getParticipantInitials(response.participantId) }}
                </span>
              </div>
              <div>
                <div class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ getParticipantName(response.participantId) }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                  {{ getParticipantEmail(response.participantId) }}
                </div>
              </div>
            </div>
            <span :class="[
              'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
              getStatusColor(response)
            ]">
              {{ getStatusLabel(response) }}
            </span>
          </div>

          <div class="mb-3">
            <div class="flex items-center justify-between text-sm mb-1">
              <span class="text-gray-600 dark:text-gray-400">Progression</span>
              <span class="text-gray-900 dark:text-white">{{ getProgress(response) }}%</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
              <div class="bg-primary-600 h-2 rounded-full" :style="{ width: `${getProgress(response)}%` }" />
            </div>
          </div>

          <div class="flex items-center justify-between">
            <span class="text-xs text-gray-500 dark:text-gray-400">
              {{ formatRelativeTime(response.lastActivity) }}
            </span>
            <div class="flex items-center space-x-2">
              <button @click="viewResponse(response)" class="btn-secondary text-xs">
                Voir
              </button>
              <button v-if="!response.completed" @click="openReminderModal([response])"
                class="text-yellow-600 dark:text-yellow-400 hover:text-yellow-900 dark:hover:text-yellow-300 text-xs">
                Relancer
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="filteredResponses.length === 0" class="text-center py-12">
        <ChatBubbleLeftRightIcon class="w-16 h-16 text-gray-400 mx-auto mb-4" />
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
          Aucune réponse trouvée
        </h3>
        <p class="text-gray-600 dark:text-gray-400 mb-4">
          {{ searchQuery || statusFilter !== 'all' || groupFilter !== 'all'
            ? 'Aucune réponse ne correspond à vos critères de recherche.'
            : 'Aucune réponse n\'a encore été reçue pour ce questionnaire.'
          }}
        </p>
        <button @click="showInviteModal = true" class="btn-primary">
          Inviter des participants
        </button>
      </div>
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
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
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

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
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

const route = useRoute();
const surveyStore = useSurveyStore();
const responseStore = useResponseStore();
const uiStore = useUIStore();

const surveyId = route.params.id as string;
const survey = computed(() => surveyStore.currentSurvey);
const searchQuery = ref('');
const statusFilter = ref('all');
const groupFilter = ref('all');
const viewMode = ref<'table' | 'cards'>('table');
const showInviteModal = ref(false);
const showResponseDetail = ref(false);
const selectedResponse = ref<Response | null>(null);

// --- Selection ---
const selectedIds = ref<Set<string>>(new Set());

function toggleSelect(id: string) {
  const next = new Set(selectedIds.value);
  if (next.has(id)) next.delete(id);
  else next.add(id);
  selectedIds.value = next;
}

function toggleSelectAll() {
  if (isAllSelected.value) {
    selectedIds.value = new Set();
  } else {
    selectedIds.value = new Set(filteredResponses.value.map(r => r.id));
  }
}

function clearSelection() {
  selectedIds.value = new Set();
}

const isAllSelected = computed(() =>
  filteredResponses.value.length > 0 &&
  filteredResponses.value.every(r => selectedIds.value.has(r.id))
);

const isIndeterminate = computed(() =>
  selectedIds.value.size > 0 && !isAllSelected.value
);

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

const selectedPendingResponses = computed(() =>
  responses.value.filter(r => selectedIds.value.has(r.id) && !r.completed)
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
  clearSelection();

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

const filteredResponses = computed(() => {
  let filtered = responses.value;

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(response => {
      const participant = participants.value.find(p => p.id === response.participantId);
      return (
        participant?.email?.toLowerCase().includes(query) ||
        participant?.name?.toLowerCase().includes(query)
      );
    });
  }

  if (statusFilter.value !== 'all') {
    filtered = filtered.filter(response => {
      switch (statusFilter.value) {
        case 'completed':
          return response.completed;
        case 'in_progress':
          return !response.completed && Object.keys(response.answers).length > 0;
        case 'not_started':
          return Object.keys(response.answers).length === 0;
        default:
          return true;
      }
    });
  }

  if (groupFilter.value !== 'all') {
    filtered = filtered.filter(response => {
      const participant = participants.value.find(p => p.id === response.participantId);
      return participant?.group === groupFilter.value;
    });
  }

  return filtered.sort((a, b) => b.lastActivity.getTime() - a.lastActivity.getTime());
});

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
