<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-2xl mx-auto px-4">
      <!-- Survey Header -->
      <div v-if="survey" class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
          {{ survey.title }}
        </h1>
        <p v-if="survey.description" class="text-lg text-gray-600 dark:text-gray-400 mb-6">
          {{ survey.description }}
        </p>

        <!-- Estimated Response Time -->
        <div v-if="survey.estimatedTime" class="flex items-center justify-center space-x-2 text-sm text-gray-500 dark:text-gray-400 mb-6">
          <ClockIcon class="w-4 h-4 text-gray-400" />
          <span>Temps de réponse estimé : {{ formatEstimatedTime(survey.estimatedTime) }}</span>
        </div>

        <!-- Progress Bar -->
        <div v-if="survey.settings.showProgress && !isCompleted" class="mb-8">
          <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
            <span>Progression</span>
            <span>{{ progress }}%</span>
          </div>
          <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
            <div
              class="bg-gradient-to-r from-primary-500 to-primary-600 h-3 rounded-full transition-all duration-500"
              :style="{ width: `${progress}%` }"
            />
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="text-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mx-auto mb-4"></div>
        <p class="text-gray-600 dark:text-gray-400">Chargement du questionnaire...</p>
      </div>

      <!-- Survey Not Found -->
      <div v-else-if="!survey" class="text-center py-12">
        <ExclamationTriangleIcon class="w-16 h-16 text-yellow-500 mx-auto mb-4" />
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
          Questionnaire introuvable
        </h2>
        <p class="text-gray-600 dark:text-gray-400">
          Le lien que vous avez suivi n'est pas valide ou le questionnaire n'est plus disponible.
        </p>
      </div>

      <!-- Survey Completed -->
      <div v-else-if="isCompleted" class="text-center py-12">
        <CheckCircleIcon class="w-16 h-16 text-green-500 mx-auto mb-4" />
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
          Merci pour votre participation !
        </h2>
        <div v-if="survey.settings.thankYouMessage" class="mb-6">
          <p class="text-gray-600 dark:text-gray-400 whitespace-pre-line">
            {{ survey.settings.thankYouMessage }}
          </p>
        </div>
        <div class="flex items-center justify-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
          <div class="flex items-center space-x-1">
            <ClockIcon class="w-4 h-4" />
            <span>Temps passé: {{ formatDuration(timeSpent) }}</span>
          </div>
          <div class="flex items-center space-x-1">
            <CheckIcon class="w-4 h-4" />
            <span>Réponses enregistrées</span>
          </div>
        </div>
      </div>

      <!-- Survey Form -->
      <div v-else-if="currentSection">
        <form @submit.prevent="handleNext" class="space-y-8">
          <!-- Section Header -->
          <div class="text-center mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-2">
              {{ currentSection.title }}
            </h2>
            <p v-if="currentSection.description" class="text-gray-600 dark:text-gray-400">
              {{ currentSection.description }}
            </p>
          </div>

          <!-- Questions -->
          <div class="space-y-6">
            <div
              v-for="(question, questionIndex) in visibleQuestions"
              :key="question.id"
              class="p-5 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-2xs space-y-3"
            >
              <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">
                  {{ getSectionQuestionNumber(questionIndex) }}. {{ question.title }}
                  <span v-if="question.required" class="text-red-500 ml-1">*</span>
                </h3>
                <p v-if="question.description" class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                  {{ question.description }}
                </p>
              </div>

              <!-- Question Input -->
              <div class="space-y-2">
                <!-- Single Choice -->
                <div v-if="question.type === 'single_choice'" class="space-y-2">
                  <label
                    v-for="option in question.options"
                    :key="option.id"
                    :class="['q-choice', answers[question.id] === option.text && 'q-choice-active']"
                  >
                    <input
                      type="radio"
                      :name="`question_${question.id}`"
                      :value="option.text"
                      :checked="answers[question.id] === option.text"
                      class="q-check mt-0.5"
                      @change="setAnswer(question.id, option.text)"
                    />
                    <span class="flex-1 text-xs font-medium text-slate-800 dark:text-slate-200">
                      {{ option.text }}
                    </span>
                  </label>
                </div>

                <!-- Multiple Choice -->
                <div v-else-if="question.type === 'multiple_choice'" class="space-y-2">
                  <label
                    v-for="option in question.options"
                    :key="option.id"
                    :class="['q-choice', (answers[question.id] as string[] || []).includes(option.text) && 'q-choice-active']"
                  >
                    <input
                      type="checkbox"
                      :checked="(answers[question.id] as string[] || []).includes(option.text)"
                      class="q-check mt-0.5"
                      @change="toggleMultipleChoice(question.id, option.text)"
                    />
                    <span class="flex-1 text-xs font-medium text-slate-800 dark:text-slate-200">
                      {{ option.text }}
                    </span>
                  </label>
                </div>

                <!-- Text Short -->
                <div v-else-if="question.type === 'text_short'">
                  <input
                    type="text"
                    :value="answers[question.id] || ''"
                    @input="setAnswer(question.id, ($event.target as HTMLInputElement).value)"
                    class="q-input"
                    placeholder="Votre réponse..."
                    :maxlength="question.validation?.maxLength"
                  />
                  <div
                    v-if="question.validation?.maxLength"
                    class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 text-right"
                  >
                    {{ (answers[question.id] as string || '').length }} / {{ question.validation.maxLength }}
                  </div>
                </div>

                <!-- Text Long -->
                <div v-else-if="question.type === 'text_long'">
                  <textarea
                    :value="answers[question.id] || ''"
                    @input="setAnswer(question.id, ($event.target as HTMLTextAreaElement).value)"
                    class="q-input"
                    rows="4"
                    placeholder="Votre réponse..."
                    :maxlength="question.validation?.maxLength"
                  />
                  <div
                    v-if="question.validation?.maxLength"
                    class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 text-right"
                  >
                    {{ (answers[question.id] as string || '').length }} / {{ question.validation.maxLength }}
                  </div>
                </div>

                <!-- Scale -->
                <div v-else-if="question.type === 'scale'" class="space-y-2">
                  <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                    <span>{{ question.validation?.min || 1 }}</span>
                    <span>{{ question.validation?.max || 10 }}</span>
                  </div>
                  <div class="flex flex-wrap gap-2 justify-center">
                    <button
                      v-for="n in ((question.validation?.max || 10) - (question.validation?.min || 1) + 1)"
                      :key="n"
                      type="button"
                      @click="setAnswer(question.id, (question.validation?.min || 1) + n - 1)"
                      :class="[
                        'w-9 h-9 rounded-xl border text-xs font-bold transition-all cursor-pointer flex items-center justify-center',
                        answers[question.id] === (question.validation?.min || 1) + n - 1
                          ? 'bg-primary-600 text-white border-primary-600 shadow-sm'
                          : 'bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:border-primary-400'
                      ]"
                    >
                      {{ (question.validation?.min || 1) + n - 1 }}
                    </button>
                  </div>
                </div>

                <!-- Matrix (simplified) -->
                <div v-else-if="question.type === 'matrix'" class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700/80">
                  <table class="w-full text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-900/60 text-slate-700 dark:text-slate-300 font-semibold">
                      <tr>
                        <th class="p-2.5 text-left font-semibold"></th>
                        <th
                          v-for="col in ['Pas du tout', 'Peu', 'Moyennement', 'Beaucoup', 'Énormément']"
                          :key="col"
                          class="p-2.5 text-center font-semibold"
                        >
                          {{ col }}
                        </th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700/80 bg-white dark:bg-slate-800">
                      <tr
                        v-for="row in ['Critère 1', 'Critère 2']"
                        :key="row"
                      >
                        <td class="p-2.5 font-medium text-slate-800 dark:text-slate-200">{{ row }}</td>
                        <td v-for="(col) in ['Pas du tout', 'Peu', 'Moyennement', 'Beaucoup', 'Énormément']" :key="col" class="p-2.5 text-center">
                          <input
                            type="radio"
                            :name="`matrix_${question.id}_${row}`"
                            :value="col"
                            @change="setMatrixAnswer(question.id, row, col)"
                            class="q-check !w-3.5 !h-3.5 mx-auto"
                          />
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- Ranking -->
                <div v-else-if="question.type === 'ranking'">
                  <p class="text-xs text-slate-500 dark:text-slate-400 mb-2">
                    Glissez les éléments pour les classer par ordre de préférence
                  </p>
                  <draggable
                    v-model="rankingItems[question.id]"
                    @end="updateRanking(question.id)"
                    class="space-y-2"
                  >
                    <div
                      v-for="(item, index) in rankingItems[question.id] || []"
                      :key="item"
                      class="flex items-center gap-3 p-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80 rounded-xl shadow-2xs cursor-move hover:shadow-md transition-all"
                    >
                      <div class="flex items-center justify-center w-6 h-6 bg-primary-100 dark:bg-primary-950/60 text-primary-700 dark:text-primary-300 rounded-lg text-xs font-bold">
                        {{ index + 1 }}
                      </div>
                      <span class="text-xs font-medium text-slate-800 dark:text-slate-200">{{ item }}</span>
                      <div class="ml-auto text-slate-400">
                        <Bars3Icon class="w-4 h-4" />
                      </div>
                    </div>
                  </draggable>
                </div>
              </div>

              <!-- Validation Error -->
              <div v-if="errors[question.id]" class="q-error">
                {{ errors[question.id] }}
              </div>
            </div>
          </div>

          <!-- Navigation -->
          <div class="flex items-center justify-between pt-8 mt-8 border-t border-gray-200 dark:border-gray-700">
            <Button
              v-if="survey.settings.allowBack && currentSectionIndex > 0"
              type="button"
              @click="goToPrevious"
              severity="secondary"
              outlined
              class="flex items-center gap-2"
            >
              <ChevronLeftIcon class="w-4 h-4" />
              <span>Précédent</span>
            </Button>
            <div v-else></div>

            <Button
              type="submit"
              :severity="isLastSection ? 'success' : 'primary'"
              class="flex items-center gap-2"
            >
              <span>{{ isLastSection ? 'Terminer' : 'Suivant' }}</span>
              <ChevronRightIcon v-if="!isLastSection" class="w-4 h-4" />
              <CheckIcon v-else class="w-4 h-4" />
            </Button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';
import {
  ExclamationTriangleIcon,
  CheckCircleIcon,
  ClockIcon,
  CheckIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  Bars3Icon
} from '@heroicons/vue/24/outline';
import { VueDraggableNext as draggable } from 'vue-draggable-next';
import { useSurveyStore } from '@/stores/survey';
import { useResponseStore } from '@/stores/responses';
import type { Survey, Question } from '@/types/survey';
import { formatDuration } from '@/utils/date';
import { 
  getInvitationByToken, 
  getInvitationSection, 
  saveInvitationAnswers, 
  submitInvitation 
} from '@/requests/questionnaire_services/questionnaireService';

const route = useRoute();
const surveyStore = useSurveyStore();
const responseStore = useResponseStore();

const token = route.params.token as string;
const loading = ref(true);
const currentSectionIndex = ref(0);
const answers = ref<Record<string, any>>({});
const errors = ref<Record<string, string>>({});
const rankingItems = ref<Record<string, string[]>>({});
const startTime = ref<Date | null>(null);
const isCompleted = ref(false);

const survey = ref<Survey | null>(null);

const currentSection = computed(() =>
  survey.value?.sections?.[currentSectionIndex.value] || null
);

const isLastSection = computed(() =>
  survey.value && survey.value.sections ? currentSectionIndex.value === survey.value.sections.length - 1 : false
);

const progress = computed(() => {
  if (!survey.value || !survey.value.sections || survey.value.sections.length === 0) return 0;
  return Math.round(((currentSectionIndex.value + 1) / survey.value.sections.length) * 100);
});

function normalizeString(v: any): string {
  return String(v ?? '').trim().toLowerCase();
}

function evaluateConditionValue(operator: string, value: any, dependentAnswer: any): boolean {
  const normVal = normalizeString(value);
  const normAns = normalizeString(dependentAnswer);

  switch (operator) {
    case 'equals':
      if (Array.isArray(dependentAnswer)) {
        return dependentAnswer.some(item => normalizeString(item) === normVal);
      }
      return normAns === normVal || String(dependentAnswer ?? '') === String(value ?? '');
    case 'not_equals':
      if (Array.isArray(dependentAnswer)) {
        return !dependentAnswer.some(item => normalizeString(item) === normVal);
      }
      return normAns !== normVal && String(dependentAnswer ?? '') !== String(value ?? '');
    case 'contains':
      if (Array.isArray(dependentAnswer)) {
        return dependentAnswer.some(item => normalizeString(item).includes(normVal));
      }
      return normAns.includes(normVal) || String(dependentAnswer || '').includes(String(value));
    case 'not_contains':
      if (Array.isArray(dependentAnswer)) {
        return !dependentAnswer.some(item => normalizeString(item).includes(normVal));
      }
      return !normAns.includes(normVal) && !String(dependentAnswer || '').includes(String(value));
    case 'greater_than':
      return Number(dependentAnswer) > Number(value);
    case 'less_than':
      return Number(dependentAnswer) < Number(value);
    case 'greater_equal':
      return Number(dependentAnswer) >= Number(value);
    case 'less_equal':
      return Number(dependentAnswer) <= Number(value);
    case 'starts_with':
      return normAns.startsWith(normVal);
    case 'ends_with':
      return normAns.endsWith(normVal);
    case 'is_empty':
      return dependentAnswer === undefined || dependentAnswer === null || dependentAnswer === '' || (Array.isArray(dependentAnswer) && dependentAnswer.length === 0);
    case 'is_not_empty':
      return dependentAnswer !== undefined && dependentAnswer !== null && dependentAnswer !== '' && (!Array.isArray(dependentAnswer) || dependentAnswer.length > 0);
    default:
      return normAns === normVal;
  }
}

function getAnswerForQuestion(questionId: any): any {
  if (questionId === undefined || questionId === null) return undefined;
  if (answers.value[questionId] !== undefined) return answers.value[questionId];

  const allSurveyQuestions = (survey.value?.sections || []).flatMap(s => s.questions || []);
  const qObj = allSurveyQuestions.find((q: any) =>
    String(q.id) === String(questionId) ||
    String(q.uuid) === String(questionId) ||
    String(q.questionId) === String(questionId)
  );

  if (qObj) {
    if (qObj.id !== undefined && answers.value[qObj.id] !== undefined) return answers.value[qObj.id];
    if (qObj.uuid && answers.value[qObj.uuid] !== undefined) return answers.value[qObj.uuid];
    if (qObj.questionId !== undefined && answers.value[qObj.questionId] !== undefined) return answers.value[qObj.questionId];
  }
  return undefined;
}

function evaluateRule(rule: any): boolean {
  const conditions = rule.conditions && rule.conditions.length > 0
    ? rule.conditions
    : [{ dependsOn: rule.dependsOn, operator: rule.operator, value: rule.value }];

  const op = rule.logicalOperator || 'AND';

  if (op === 'OR') {
    return conditions.some((c: any) => {
      const dependentAnswer = getAnswerForQuestion(c.dependsOn ?? c.dependsOnQuestionId);
      return evaluateConditionValue(c.operator, c.value, dependentAnswer);
    });
  } else {
    return conditions.every((c: any) => {
      const dependentAnswer = getAnswerForQuestion(c.dependsOn ?? c.dependsOnQuestionId);
      return evaluateConditionValue(c.operator, c.value, dependentAnswer);
    });
  }
}

const visibleQuestions = computed(() => {
  if (!currentSection.value || !currentSection.value.questions) return [];

  // Gather all questions across all sections in the survey
  const allSurveyQuestions = (survey.value?.sections || []).flatMap(s => s.questions || []);

  return currentSection.value.questions.filter((question: any) => {
    const qId = question.id || question.questionId || question.uuid;

    // Find all rules across the survey targeting this question
    const applicableRules: any[] = [];

    // 1. DTO Visibility
    if (question.visibility && (question.visibility.dependsOnQuestionId || (question.visibility.conditions && question.visibility.conditions.length > 0))) {
      applicableRules.push({
        dependsOn: question.visibility.dependsOnQuestionId,
        operator: question.visibility.operator,
        value: question.visibility.value,
        logicalOperator: question.visibility.logicalOperator || 'AND',
        conditions: question.visibility.conditions || [],
        action: question.visibility.action || 'show',
        type: 'show_hide'
      });
    }

    // 2. Questions with conditionalRules
    allSurveyQuestions.forEach((sq: any) => {
      if (sq.conditionalRules && Array.isArray(sq.conditionalRules)) {
        sq.conditionalRules.forEach((r: any) => {
          const isTargeted = (r.targetQuestionIds && Array.isArray(r.targetQuestionIds) && r.targetQuestionIds.length > 0)
            ? r.targetQuestionIds.some((tid: any) => String(tid) === String(qId) || (question.uuid && String(tid) === String(question.uuid)))
            : (String(r.dependsOn) !== String(qId) && (question.uuid ? String(r.dependsOn) !== String(question.uuid) : true) && (String(sq.id) === String(qId) || (question.uuid && String(sq.uuid) === String(question.uuid))));

          if (isTargeted) {
            applicableRules.push({
              dependsOn: r.dependsOn,
              operator: r.operator,
              value: r.value,
              logicalOperator: r.logicalOperator || 'AND',
              conditions: r.conditions || [],
              action: r.action || 'show',
              type: r.type || 'show_hide'
            });
          }
        });
      }
    });

    if (applicableRules.length === 0) return true;

    // Check applicable rules
    return applicableRules.some(rule => {
      const conditionMet = evaluateRule(rule);
      const { action, type } = rule;

      // Apply the action based on rule type
      if (type === 'show_hide' || !type) {
        if (action === 'show') {
          return conditionMet; // Show if condition is met
        } else if (action === 'hide') {
          return !conditionMet; // Hide if condition is met (so show if NOT met)
        }
      }

      return true;
    });
  });
});

// Computed property to check if survey should end early
const shouldEndSurvey = computed(() => {
  if (!currentSection.value) return false;

  // Check all questions in current section for end_survey rules
  return currentSection.value.questions.some(question => {
    if (!question.conditionalRules) return false;

    return question.conditionalRules.some(rule => {
      if (rule.type !== 'end_survey') return false;
      return evaluateRule(rule);
    });
  });
});

// Computed property to get target section for jump rules
const jumpTargetSection = computed(() => {
  if (!currentSection.value) return null;

  for (const question of currentSection.value.questions) {
    if (!question.conditionalRules) continue;

    for (const rule of question.conditionalRules) {
      if (rule.type !== 'jump_section') continue;

      const conditionMet = evaluateRule(rule);

      if (conditionMet && rule.targetSectionId) {
        const targetIndex = survey.value?.sections?.findIndex(s => s.id === rule.targetSectionId);
        return targetIndex !== undefined && targetIndex !== -1 ? targetIndex : null;
      }
    }
  }

  return null;
});

const timeSpent = computed(() => {
  if (!startTime.value) return 0;
  return Date.now() - startTime.value.getTime();
});

function getSectionQuestionNumber(questionIndex: number): number {
  if (!survey.value || !survey.value.sections) return questionIndex + 1;

  let totalQuestions = 0;
  for (let i = 0; i < currentSectionIndex.value; i++) {
    const section = survey.value.sections[i];
    if (section && section.questions) {
      totalQuestions += section.questions.length;
    }
  }

  return totalQuestions + questionIndex + 1;
}

function setAnswer(questionId: string, value: any) {
  answers.value[questionId] = value;
  delete errors.value[questionId];

  // Auto-save if enabled
  if (survey.value?.settings.autoSave) {
    saveCurrentSectionProgress();
  }
}

function toggleMultipleChoice(questionId: string, option: string) {
  const currentAnswers = (answers.value[questionId] as string[]) || [];
  const index = currentAnswers.indexOf(option);

  if (index > -1) {
    currentAnswers.splice(index, 1);
  } else {
    currentAnswers.push(option);
  }

  answers.value[questionId] = [...currentAnswers];
  delete errors.value[questionId];

  if (survey.value?.settings.autoSave) {
    saveCurrentSectionProgress();
  }
}

function setMatrixAnswer(questionId: string, row: string, value: string) {
  if (!answers.value[questionId]) {
    answers.value[questionId] = {};
  }
  answers.value[questionId][row] = value;

  if (survey.value?.settings.autoSave) {
    saveCurrentSectionProgress();
  }
}

function updateRanking(questionId: string) {
  answers.value[questionId] = [...(rankingItems.value[questionId] || [])];

  if (survey.value?.settings.autoSave) {
    saveCurrentSectionProgress();
  }
}

function validateCurrentSection(): boolean {
  errors.value = {};
  let isValid = true;

  visibleQuestions.value.forEach(question => {
    // Check if question should be required based on conditional rules
    let isRequired = question.required;

    if (question.conditionalRules) {
      question.conditionalRules.forEach(rule => {
        if (rule.type === 'set_required') {
          const conditionMet = evaluateRule(rule);
          if (conditionMet) {
            isRequired = rule.action === 'require';
          }
        }
      });
    }

    if (isRequired) {
      const answer = answers.value[question.id];

      if (answer === undefined || answer === null || answer === '' ||
          (Array.isArray(answer) && answer.length === 0)) {
        errors.value[question.id] = 'Cette question est obligatoire';
        isValid = false;
      }
    }

    // Additional validation based on question type
    if (question.validation && answers.value[question.id]) {
      const answer = answers.value[question.id];

      if (question.type === 'text_short' || question.type === 'text_long') {
        if (question.validation.minLength && answer.length < question.validation.minLength) {
          errors.value[question.id] = `Minimum ${question.validation.minLength} caractères requis`;
          isValid = false;
        }
        if (question.validation.maxLength && answer.length > question.validation.maxLength) {
          errors.value[question.id] = `Maximum ${question.validation.maxLength} caractères autorisés`;
          isValid = false;
        }
      }
    }
  });

  return isValid;
}

async function loadSectionQuestions(index: number) {
  if (!survey.value || !survey.value.sections || !survey.value.sections[index]) return;
  const sec = survey.value.sections[index];
  
  loading.value = true;
  try {
    const secDetails = await getInvitationSection(token, sec.id);
    const questionsList = Array.isArray(secDetails.questions) ? secDetails.questions : (secDetails.questions?.member || secDetails.questions || []);
    sec.questions = questionsList.map((q: any) => ({
      id: q.questionId,
      uuid: q.uuid,
      type: q.typeQuestion?.value || q.typeQuestion || 'text_short',
      title: q.label,
      description: '',
      required: q.required,
      options: q.choices ? q.choices.map((c: any) => ({ id: c.id, text: c.text || c.label, value: c.value })) : [],
      validation: q.scale ? { min: q.scale.min, max: q.scale.max } : {},
      visibility: q.visibility,
      conditionalRules: q.conditionalRules || []
    }));
    
    questionsList.forEach((q: any) => {
      if (q.answer !== null && q.answer !== undefined) {
        answers.value[q.questionId] = q.answer;
      }
    });

    initializeRankingItems();
  } catch (error) {
    console.error('Failed to load section questions:', error);
  } finally {
    loading.value = false;
  }
}

async function handleNext() {
  if (!validateCurrentSection()) {
    return;
  }

  await saveCurrentSectionProgress();

  // Check for early survey termination
  if (shouldEndSurvey.value) {
    await completeSurvey();
    return;
  }

  // Check for section jump
  const jumpTarget = jumpTargetSection.value;
  if (jumpTarget !== null) {
    currentSectionIndex.value = jumpTarget;
    await loadSectionQuestions(jumpTarget);
    return;
  }

  if (isLastSection.value) {
    await completeSurvey();
  } else {
    currentSectionIndex.value++;
    await loadSectionQuestions(currentSectionIndex.value);
  }
}

async function goToPrevious() {
  if (currentSectionIndex.value > 0) {
    currentSectionIndex.value--;
    await loadSectionQuestions(currentSectionIndex.value);
  }
}

async function saveCurrentSectionProgress() {
  const sec = currentSection.value;
  if (!sec || !survey.value) return;

  const answersList = (visibleQuestions.value || []).map(q => ({
    questionId: q.id,
    value: answers.value[q.id] !== undefined ? answers.value[q.id] : null
  }));

  try {
    await saveInvitationAnswers(token, {
      publishedSectionInstanceId: sec.id,
      answers: answersList
    });
  } catch (e) {
    console.error('Failed to save answers:', e);
  }
}

async function completeSurvey() {
  if (!survey.value) return;

  try {
    await submitInvitation(token);
    isCompleted.value = true;
  } catch (e) {
    console.error('Failed to submit survey:', e);
  }
}

function initializeRankingItems() {
  if (!survey.value || !survey.value.sections) return;

  survey.value.sections.forEach(section => {
    if (section.questions) {
      section.questions
        .filter(q => q.type === 'ranking')
        .forEach(question => {
          if (question.options) {
            rankingItems.value[question.id] = question.options.map(o => o.text);
          }
        });
    }
  });
}

function formatEstimatedTime(seconds: number): string {
  if (seconds < 60) {
    return "moins d'une minute";
  }
  const minutes = Math.floor(seconds / 60);
  const remainingSeconds = seconds % 60;
  if (remainingSeconds === 0) {
    return `${minutes} min`;
  }
  return `${minutes} min ${remainingSeconds} s`;
}

onMounted(async () => {
  loading.value = true;
  try {
    const inv = await getInvitationByToken(token);
    console.log(token)
    console.log(inv)
    if (inv) {
      if (inv.invitationStatus === 'submitted') {
        isCompleted.value = true;
        loading.value = false;
        return;
      }
      
      survey.value = {
        id: token,
        title: inv.questionnaireTitle,
        description: '',
        estimatedTime: 300,
        settings: {
          showProgress: true,
          allowBack: true,
          autoSave: true,
          thankYouMessage: 'Merci pour votre participation !'
        },
        sections: (inv.sections || []).map((s: any) => ({
          id: s.publishedSectionInstanceId,
          title: s.title,
          description: '',
          questions: []
        }))
      };

      currentSectionIndex.value = 0;
      await loadSectionQuestions(0);
    }
  } catch (error) {
    console.error('Failed to load survey invitation:', error);
    survey.value = null;
  } finally {
    loading.value = false;
    startTime.value = new Date();
  }
});

onUnmounted(async () => {
  if (survey.value?.settings.autoSave && Object.keys(answers.value).length > 0) {
    await saveCurrentSectionProgress();
  }
});
</script>
