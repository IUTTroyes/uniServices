<template>
  <Dialog
    :visible="true"
    modal
    :style="{ width: '800px', maxWidth: '95vw' }"
    :closable="false"
    class="p-dialog-clean"
    @update:visible="$emit('close')"
  >
    <template #header>
      <DialogHeader
        :icon="DocumentTextIcon"
        tone="blue"
        title="Détail de la réponse"
        :subtitle="`${getParticipantName()} • ${formatDate(response?.lastActivity)}`"
        @close="$emit('close')"
      />
    </template>

    <div v-if="response && survey" class="space-y-6 pt-1">
      <!-- Response Info KPI Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-900/30 flex items-center gap-3">
          <div
            :class="[
              'w-10 h-10 rounded-xl flex items-center justify-center shrink-0 text-white shadow-xs',
              response.completed ? 'bg-emerald-500' : 'bg-amber-500'
            ]"
          >
            <component :is="response.completed ? CheckCircleIcon : ClockIcon" class="w-5 h-5" />
          </div>
          <div class="min-w-0">
            <span class="text-xs font-medium text-slate-500 dark:text-slate-400 block">Statut</span>
            <span class="text-sm font-semibold text-slate-900 dark:text-slate-100">
              {{ response.completed ? 'Terminé' : 'En cours' }}
            </span>
          </div>
        </div>

        <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-900/30 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 bg-primary-600 text-white shadow-xs">
            <ChartBarIcon class="w-5 h-5" />
          </div>
          <div class="min-w-0">
            <span class="text-xs font-medium text-slate-500 dark:text-slate-400 block">Progression</span>
            <span class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ getProgress() }}%</span>
          </div>
        </div>

        <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-900/30 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 bg-purple-600 text-white shadow-xs">
            <ClockIcon class="w-5 h-5" />
          </div>
          <div class="min-w-0">
            <span class="text-xs font-medium text-slate-500 dark:text-slate-400 block">Temps passé</span>
            <span class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ getTimeSpent() }}</span>
          </div>
        </div>
      </div>

      <!-- Responses by Section -->
      <div v-for="section in survey.sections" :key="section.id" class="space-y-4">
        <div class="flex items-center gap-2 pb-2 border-b border-slate-200 dark:border-slate-700/80">
          <div class="w-2 h-2 rounded-full bg-primary-500"></div>
          <h3 class="text-sm font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
            {{ section.title }}
          </h3>
        </div>

        <div class="space-y-3">
          <div
            v-for="(question, questionIndex) in section.questions"
            :key="question.id"
            class="p-4 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-white dark:bg-slate-900/40 space-y-3"
          >
            <div>
              <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                <span class="text-xs font-medium text-slate-400 dark:text-slate-500">{{ questionIndex + 1 }}.</span>
                <span>{{ question.title }}</span>
                <span v-if="question.required" class="text-red-500 font-bold">*</span>
              </h4>
              <p v-if="question.description" class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                {{ question.description }}
              </p>
            </div>

            <!-- Answer Display -->
            <div class="pt-1">
              <div v-if="!response.answers[question.id]" class="text-xs text-slate-400 dark:text-slate-500 italic flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                Pas de réponse
              </div>

              <!-- Single/Multiple Choice -->
              <div v-else-if="['single_choice', 'multiple_choice'].includes(question.type)">
                <div v-if="Array.isArray(response.answers[question.id])" class="flex flex-wrap gap-2">
                  <span
                    v-for="answer in response.answers[question.id]"
                    :key="answer"
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold bg-primary-50 text-primary-700 dark:bg-primary-950/40 dark:text-primary-300 border border-primary-200 dark:border-primary-800"
                  >
                    <CheckCircleIcon class="w-3.5 h-3.5 text-primary-600 dark:text-primary-400" />
                    {{ answer }}
                  </span>
                </div>
                <div v-else>
                  <span
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold bg-primary-50 text-primary-700 dark:bg-primary-950/40 dark:text-primary-300 border border-primary-200 dark:border-primary-800"
                  >
                    <CheckCircleIcon class="w-3.5 h-3.5 text-primary-600 dark:text-primary-400" />
                    {{ response.answers[question.id] }}
                  </span>
                </div>
              </div>

              <!-- Text -->
              <div v-else-if="['text_short', 'text_long'].includes(question.type)">
                <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-sm text-slate-800 dark:text-slate-200 whitespace-pre-wrap">
                  {{ response.answers[question.id] }}
                </div>
              </div>

              <!-- Scale -->
              <div v-else-if="question.type === 'scale'">
                <div class="inline-flex items-baseline gap-2 px-3 py-1.5 rounded-xl bg-primary-50 dark:bg-primary-950/40 border border-primary-200 dark:border-primary-800">
                  <span class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                    {{ response.answers[question.id] }}
                  </span>
                  <span class="text-xs font-medium text-slate-500 dark:text-slate-400">
                    / {{ question.validation?.max || 10 }}
                  </span>
                </div>
              </div>

              <!-- Matrix -->
              <div v-else-if="question.type === 'matrix'">
                <div class="space-y-1.5">
                  <div
                    v-for="(value, key) in response.answers[question.id]"
                    :key="key"
                    class="flex items-center justify-between p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-xs"
                  >
                    <span class="font-medium text-slate-800 dark:text-slate-200">{{ key }}</span>
                    <span class="font-semibold text-primary-600 dark:text-primary-400">{{ value }}</span>
                  </div>
                </div>
              </div>

              <!-- Ranking -->
              <div v-else-if="question.type === 'ranking'">
                <div class="space-y-1.5">
                  <div
                    v-for="(item, index) in response.answers[question.id]"
                    :key="index"
                    class="flex items-center gap-2.5 p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-xs"
                  >
                    <span class="flex items-center justify-center w-5 h-5 rounded-full bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300 font-bold text-2xs">
                      {{ index + 1 }}
                    </span>
                    <span class="font-medium text-slate-800 dark:text-slate-200">{{ item }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex items-center justify-end gap-3 pt-3">
        <Button
          label="Fermer"
          severity="secondary"
          text
          @click="$emit('close')"
        />
        <Button
          v-if="!response?.completed"
          label="Envoyer un rappel"
          severity="primary"
          @click="sendReminder"
        />
      </div>
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import {
  DocumentTextIcon,
  CheckCircleIcon,
  ClockIcon,
  ChartBarIcon
} from '@heroicons/vue/24/outline';
import DialogHeader from '@/components/Form/DialogHeader.vue';
import type { Survey, Response } from '@types';
import { useResponseStore } from '@/stores/responses';
import { useUIStore } from '@/stores/ui';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';

interface Props {
  response: Response | null;
  survey: Survey | null;
}

interface Emits {
  (e: 'close'): void;
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
  if (props.response.completed) return 100;

  const totalQuestions = props.survey.sections.reduce((sum, section) =>
    sum + section.questions.length, 0
  );

  const answeredQuestions = Object.keys(props.response.answers).length;

  return totalQuestions > 0 ? Math.round((answeredQuestions / totalQuestions) * 100) : 0;
}

function getTimeSpent(): string {
  if (!props.response) return '0min';

  const startTime = new Date(props.response.startedAt).getTime();
  const endTime = props.response.submittedAt
    ? new Date(props.response.submittedAt).getTime()
    : new Date(props.response.lastActivity).getTime();
  const duration = endTime - startTime;

  const minutes = Math.round(duration / (1000 * 60));
  if (minutes < 60) return `${minutes}min`;

  const hours = Math.floor(minutes / 60);
  const remainingMinutes = minutes % 60;
  return `${hours}h${remainingMinutes > 0 ? ` ${remainingMinutes}min` : ''}`;
}

function formatDate(date?: Date | string): string {
  if (!date) return '';
  const parsed = typeof date === 'string' ? new Date(date) : date;
  return format(parsed, 'dd/MM/yyyy à HH:mm', { locale: fr });
}

function sendReminder() {
  uiStore.addNotification(
    'success',
    'Rappel envoyé',
    `Un rappel a été envoyé à ${getParticipantName()}.`
  );
}
</script>

