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
        <router-link
          :to="`/analytics/${surveyId}`"
          class="btn-secondary"
        >
          <ChartBarIcon class="w-4 h-4" />
          Voir les statistiques
        </router-link>
        <button @click="showInviteModal = true" class="btn-primary">
          <UserPlusIcon class="w-4 h-4" />
          Inviter des participants
        </button>
        <button @click="exportResponses" class="btn-secondary">
          <ArrowDownTrayIcon class="w-4 h-4" />
          Exporter
        </button>
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
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Rechercher par email ou nom..."
              class="pl-10 input-field w-64"
            />
          </div>

          <select v-model="statusFilter" class="input-field w-40">
            <option value="all">Tous les statuts</option>
            <option value="completed">Terminé</option>
            <option value="in_progress">En cours</option>
            <option value="not_started">Non commencé</option>
          </select>

          <select v-model="groupFilter" class="input-field w-40">
            <option value="all">Tous les groupes</option>
            <option
              v-for="group in availableGroups"
              :key="group"
              :value="group"
            >
              {{ group }}
            </option>
          </select>
        </div>

        <div class="flex items-center space-x-2">
          <button
            @click="viewMode = 'table'"
            :class="[
              'p-2 rounded-lg',
              viewMode === 'table'
                ? 'bg-primary-100 text-primary-600 dark:bg-primary-900 dark:text-primary-400'
                : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'
            ]"
          >
            <TableCellsIcon class="w-5 h-5" />
          </button>
          <button
            @click="viewMode = 'cards'"
            :class="[
              'p-2 rounded-lg',
              viewMode === 'cards'
                ? 'bg-primary-100 text-primary-600 dark:bg-primary-900 dark:text-primary-400'
                : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'
            ]"
          >
            <Squares2X2Icon class="w-5 h-5" />
          </button>
        </div>
      </div>
    </div>

    <!-- Responses List -->
    <div class="card">
      <!-- Table View -->
      <div v-if="viewMode === 'table'" class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Participant
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Statut
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Progression
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Dernière activité
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr
              v-for="response in filteredResponses"
              :key="response.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-700"
            >
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
                <span
                  :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    getStatusColor(response)
                  ]"
                >
                  {{ getStatusLabel(response) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-2">
                    <div
                      class="bg-primary-600 h-2 rounded-full"
                      :style="{ width: `${getProgress(response)}%` }"
                    />
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
                <button
                  @click="viewResponse(response)"
                  class="text-primary-600 dark:text-primary-400 hover:text-primary-900 dark:hover:text-primary-300 mr-3"
                >
                  Voir
                </button>
                <button
                  v-if="!response.completed"
                  @click="sendReminder(response)"
                  class="text-yellow-600 dark:text-yellow-400 hover:text-yellow-900 dark:hover:text-yellow-300"
                >
                  Relancer
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Cards View -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="response in filteredResponses"
          :key="response.id"
          class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 hover:border-gray-300 dark:hover:border-gray-500 transition-colors"
        >
          <div class="flex items-center justify-between mb-3">
            <div class="flex items-center space-x-3">
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
            <span
              :class="[
                'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                getStatusColor(response)
              ]"
            >
              {{ getStatusLabel(response) }}
            </span>
          </div>

          <div class="mb-3">
            <div class="flex items-center justify-between text-sm mb-1">
              <span class="text-gray-600 dark:text-gray-400">Progression</span>
              <span class="text-gray-900 dark:text-white">{{ getProgress(response) }}%</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
              <div
                class="bg-primary-600 h-2 rounded-full"
                :style="{ width: `${getProgress(response)}%` }"
              />
            </div>
          </div>

          <div class="flex items-center justify-between">
            <span class="text-xs text-gray-500 dark:text-gray-400">
              {{ formatRelativeTime(response.lastActivity) }}
            </span>
            <div class="flex items-center space-x-2">
              <button
                @click="viewResponse(response)"
                class="btn-secondary text-xs"
              >
                Voir
              </button>
              <button
                v-if="!response.completed"
                @click="sendReminder(response)"
                class="text-yellow-600 dark:text-yellow-400 hover:text-yellow-900 dark:hover:text-yellow-300 text-xs"
              >
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
    <InviteParticipantsModal
      v-if="showInviteModal"
      :survey-id="surveyId"
      @close="showInviteModal = false"
      @invited="handleParticipantsInvited"
    />

    <!-- Response Detail Modal -->
    <ResponseDetailModal
      v-if="showResponseDetail"
      :response="selectedResponse"
      :survey="survey"
      @close="showResponseDetail = false"
    />
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
  Squares2X2Icon
} from '@heroicons/vue/24/outline';
import { useSurveyStore } from '@/stores/survey';
import { useResponseStore } from '@/stores/responses';
import { useUIStore } from '@/stores/ui';
import InviteParticipantsModal from '@/components/Questionnaire/InviteParticipantsModal.vue';
import ResponseDetailModal from '@/components/Questionnaire/ResponseDetailModal.vue';
import type { Response } from '@/types/survey';
import { formatRelative } from 'date-fns';
import { fr } from 'date-fns/locale';

const route = useRoute();
const surveyStore = useSurveyStore();
const responseStore = useResponseStore();
const uiStore = useUIStore();

const surveyId = route.params.id as string;
const searchQuery = ref('');
const statusFilter = ref('all');
const groupFilter = ref('all');
const viewMode = ref<'table' | 'cards'>('table');
const showInviteModal = ref(false);
const showResponseDetail = ref(false);
const selectedResponse = ref<Response | null>(null);

const survey = computed(() =>
  surveyStore.surveys.find(s => s.id === surveyId)
);

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

  // Search filter
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

  // Status filter
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

  // Group filter
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
  if (Object.keys(response.answers).length > 0) {
    return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
  }
  return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
}

function getProgress(response: Response): number {
  if (!survey.value) return 0;

  const totalQuestions = survey.value.sections.reduce((sum, section) =>
    sum + section.questions.length, 0
  );

  const answeredQuestions = Object.keys(response.answers).length;

  return totalQuestions > 0 ? Math.round((answeredQuestions / totalQuestions) * 100) : 0;
}

function formatRelativeTime(date: Date): string {
  return formatRelative(date, new Date(), { locale: fr });
}

function formatDuration(milliseconds: number): string {
  const minutes = Math.round(milliseconds / (1000 * 60));
  if (minutes < 60) return `${minutes}min`;
  const hours = Math.floor(minutes / 60);
  const remainingMinutes = minutes % 60;
  return `${hours}h${remainingMinutes > 0 ? ` ${remainingMinutes}min` : ''}`;
}

function viewResponse(response: Response) {
  selectedResponse.value = response;
  showResponseDetail.value = true;
}

function sendReminder(response: Response) {
  const participant = participants.value.find(p => p.id === response.participantId);
  uiStore.addNotification(
    'success',
    'Rappel envoyé',
    `Un rappel a été envoyé à ${participant?.email || 'ce participant'}.`
  );
}

function handleParticipantsInvited() {
  showInviteModal.value = false;
  uiStore.addNotification(
    'success',
    'Invitations envoyées',
    'Les invitations ont été envoyées avec succès.'
  );
}

function exportResponses() {
  // Mock export functionality
  uiStore.addNotification(
    'success',
    'Export en cours',
    'Vos données sont en cours d\'exportation.'
  );
}

onMounted(() => {
  if (!survey.value) {
    surveyStore.selectSurvey(surveyId);
  }

  // Generate demo data if no responses exist
  if (responses.value.length === 0) {
    responseStore.generateDemoData(surveyId, 15);
  }
});
</script>
