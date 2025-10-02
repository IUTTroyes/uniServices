<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click="$emit('close')">
    <div
      class="bg-white dark:bg-gray-800 rounded-xl w-full max-w-4xl mx-4 max-h-[90vh] overflow-hidden flex flex-col"
      @click.stop
    >
      <!-- Header -->
      <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
        <div>
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">
            Détail de la réponse
          </h2>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            {{ getParticipantName() }} • {{ formatDate(response?.lastActivity) }}
          </p>
        </div>
        <button
          @click="$emit('close')"
          class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
        >
          <XMarkIcon class="w-5 h-5" />
        </button>
      </div>

      <!-- Content -->
      <div class="flex-1 overflow-y-auto p-6">
        <div v-if="response && survey" class="space-y-6">
          <!-- Response Info -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="card">
              <div class="text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">Statut</p>
                <span
                  :class="[
                    'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium mt-1',
                    response.completed
                      ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                      : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
                  ]"
                >
                  {{ response.completed ? 'Terminé' : 'En cours' }}
                </span>
              </div>
            </div>

            <div class="card">
              <div class="text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">Progression</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                  {{ getProgress() }}%
                </p>
              </div>
            </div>

            <div class="card">
              <div class="text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">Temps passé</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                  {{ getTimeSpent() }}
                </p>
              </div>
            </div>
          </div>

          <!-- Responses by Section -->
          <div v-for="section in survey.sections" :key="section.id" class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2">
              {{ section.title }}
            </h3>

            <div class="space-y-4">
              <div
                v-for="(question, questionIndex) in section.questions"
                :key="question.id"
                class="card"
              >
                <div class="mb-3">
                  <h4 class="font-medium text-gray-900 dark:text-white">
                    {{ questionIndex + 1 }}. {{ question.title }}
                    <span v-if="question.required" class="text-red-500 ml-1">*</span>
                  </h4>
                  <p v-if="question.description" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {{ question.description }}
                  </p>
                </div>

                <!-- Answer Display -->
                <div class="mt-3">
                  <div v-if="!response.answers[question.id]" class="text-gray-500 dark:text-gray-400 italic">
                    Pas de réponse
                  </div>

                  <!-- Single/Multiple Choice -->
                  <div v-else-if="['single_choice', 'multiple_choice'].includes(question.type)">
                    <div v-if="Array.isArray(response.answers[question.id])">
                      <div class="flex flex-wrap gap-2">
                        <span
                          v-for="answer in response.answers[question.id]"
                          :key="answer"
                          class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200"
                        >
                          {{ answer }}
                        </span>
                      </div>
                    </div>
                    <div v-else>
                      <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200">
                        {{ response.answers[question.id] }}
                      </span>
                    </div>
                  </div>

                  <!-- Text -->
                  <div v-else-if="['text_short', 'text_long'].includes(question.type)">
                    <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                      <p class="text-gray-900 dark:text-white whitespace-pre-wrap">
                        {{ response.answers[question.id] }}
                      </p>
                    </div>
                  </div>

                  <!-- Scale -->
                  <div v-else-if="question.type === 'scale'">
                    <div class="flex items-center space-x-2">
                      <span class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                        {{ response.answers[question.id] }}
                      </span>
                      <span class="text-gray-500 dark:text-gray-400">
                        / {{ question.validation?.max || 10 }}
                      </span>
                    </div>
                  </div>

                  <!-- Matrix -->
                  <div v-else-if="question.type === 'matrix'">
                    <div class="space-y-2">
                      <div
                        v-for="(value, key) in response.answers[question.id]"
                        :key="key"
                        class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded"
                      >
                        <span class="font-medium text-gray-900 dark:text-white">{{ key }}</span>
                        <span class="text-primary-600 dark:text-primary-400">{{ value }}</span>
                      </div>
                    </div>
                  </div>

                  <!-- Ranking -->
                  <div v-else-if="question.type === 'ranking'">
                    <div class="space-y-2">
                      <div
                        v-for="(item, index) in response.answers[question.id]"
                        :key="index"
                        class="flex items-center space-x-3 p-2 bg-gray-50 dark:bg-gray-700 rounded"
                      >
                        <div class="flex items-center justify-center w-6 h-6 bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 rounded-full text-sm font-medium">
                          {{ index + 1 }}
                        </div>
                        <span class="text-gray-900 dark:text-white">{{ item }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="flex items-center justify-end space-x-3 p-6 border-t border-gray-200 dark:border-gray-700">
        <button @click="$emit('close')" class="btn-secondary">
          Fermer
        </button>
        <button v-if="!response?.completed" @click="sendReminder" class="btn-primary">
          Envoyer un rappel
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { XMarkIcon } from '@heroicons/vue/24/outline';
import type { Response, Survey, Participant } from '@/types/survey';
import { useResponseStore } from '@/stores/responses';
import { useUIStore } from '@/stores/ui';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';

interface Props {
  response: Response | null;
  survey: Survey | null;
}

interface Emits {
  close: [];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const responseStore = useResponseStore();
const uiStore = useUIStore();

const participant = computed(() => {
  if (!props.response?.participantId) return null;
  return responseStore.participants.find(p => p.id === props.response?.participantId);
});

function getParticipantName(): string {
  return participant.value?.name || participant.value?.email || 'Participant anonyme';
}

function getProgress(): number {
  if (!props.response || !props.survey) return 0;

  const totalQuestions = props.survey.sections.reduce((sum, section) =>
    sum + section.questions.length, 0
  );

  const answeredQuestions = Object.keys(props.response.answers).length;

  return totalQuestions > 0 ? Math.round((answeredQuestions / totalQuestions) * 100) : 0;
}

function getTimeSpent(): string {
  if (!props.response) return '0min';

  const startTime = props.response.startedAt.getTime();
  const endTime = props.response.submittedAt?.getTime() || props.response.lastActivity.getTime();
  const duration = endTime - startTime;

  const minutes = Math.round(duration / (1000 * 60));
  if (minutes < 60) return `${minutes}min`;

  const hours = Math.floor(minutes / 60);
  const remainingMinutes = minutes % 60;
  return `${hours}h${remainingMinutes > 0 ? ` ${remainingMinutes}min` : ''}`;
}

function formatDate(date?: Date): string {
  if (!date) return '';
  return format(date, 'dd/MM/yyyy à HH:mm', { locale: fr });
}

function sendReminder() {
  uiStore.addNotification(
    'success',
    'Rappel envoyé',
    `Un rappel a été envoyé à ${getParticipantName()}.`
  );
}
</script>
