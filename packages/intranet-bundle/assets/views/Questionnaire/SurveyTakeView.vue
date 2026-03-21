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
          <div class="space-y-8">
            <div
              v-for="(question, questionIndex) in visibleQuestions"
              :key="question.id"
              class="card"
            >
              <div class="mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                  {{ getSectionQuestionNumber(questionIndex) }}. {{ question.title }}
                  <span v-if="question.required" class="text-red-500 ml-1">*</span>
                </h3>
                <p v-if="question.description" class="text-sm text-gray-600 dark:text-gray-400">
                  {{ question.description }}
                </p>
              </div>

              <!-- Question Input -->
              <div class="space-y-3">
                <!-- Single Choice -->
                <div v-if="question.type === 'single_choice'" class="space-y-3">
                  <div
                    v-for="option in question.options"
                    :key="option.id"
                    class="flex items-center space-x-3 p-3 border border-gray-200 dark:border-gray-600 rounded-lg hover:border-primary-300 dark:hover:border-primary-600 transition-colors cursor-pointer"
                    @click="setAnswer(question.id, option.text)"
                  >
                    <input
                      type="radio"
                      :name="`question_${question.id}`"
                      :value="option.text"
                      :checked="answers[question.id] === option.text"
                      class="text-primary-600 focus:ring-primary-500"
                      @change="setAnswer(question.id, option.text)"
                    />
                    <label class="flex-1 cursor-pointer text-gray-700 dark:text-gray-300">
                      {{ option.text }}
                    </label>
                  </div>
                </div>

                <!-- Multiple Choice -->
                <div v-else-if="question.type === 'multiple_choice'" class="space-y-3">
                  <div
                    v-for="option in question.options"
                    :key="option.id"
                    class="flex items-center space-x-3 p-3 border border-gray-200 dark:border-gray-600 rounded-lg hover:border-primary-300 dark:hover:border-primary-600 transition-colors cursor-pointer"
                    @click="toggleMultipleChoice(question.id, option.text)"
                  >
                    <input
                      type="checkbox"
                      :checked="(answers[question.id] as string[] || []).includes(option.text)"
                      class="text-primary-600 focus:ring-primary-500 rounded"
                      @change="toggleMultipleChoice(question.id, option.text)"
                    />
                    <label class="flex-1 cursor-pointer text-gray-700 dark:text-gray-300">
                      {{ option.text }}
                    </label>
                  </div>
                </div>

                <!-- Text Short -->
                <div v-else-if="question.type === 'text_short'">
                  <input
                    type="text"
                    :value="answers[question.id] || ''"
                    @input="setAnswer(question.id, ($event.target as HTMLInputElement).value)"
                    class="w-full input-field"
                    placeholder="Votre réponse..."
                    :maxlength="question.validation?.maxLength"
                  />
                  <div
                    v-if="question.validation?.maxLength"
                    class="text-xs text-gray-500 dark:text-gray-400 mt-1 text-right"
                  >
                    {{ (answers[question.id] as string || '').length }} / {{ question.validation.maxLength }}
                  </div>
                </div>

                <!-- Text Long -->
                <div v-else-if="question.type === 'text_long'">
                  <textarea
                    :value="answers[question.id] || ''"
                    @input="setAnswer(question.id, ($event.target as HTMLTextAreaElement).value)"
                    class="w-full input-field resize-none"
                    rows="4"
                    placeholder="Votre réponse..."
                    :maxlength="question.validation?.maxLength"
                  />
                  <div
                    v-if="question.validation?.maxLength"
                    class="text-xs text-gray-500 dark:text-gray-400 mt-1 text-right"
                  >
                    {{ (answers[question.id] as string || '').length }} / {{ question.validation.maxLength }}
                  </div>
                </div>

                <!-- Scale -->
                <div v-else-if="question.type === 'scale'">
                  <div class="flex items-center justify-between px-4">
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                      {{ question.validation?.min || 1 }}
                    </span>
                    <div class="flex space-x-2">
                      <button
                        v-for="n in ((question.validation?.max || 10) - (question.validation?.min || 1) + 1)"
                        :key="n"
                        type="button"
                        @click="setAnswer(question.id, (question.validation?.min || 1) + n - 1)"
                        :class="[
                          'w-10 h-10 rounded-full border-2 flex items-center justify-center text-sm font-medium transition-all',
                          answers[question.id] === (question.validation?.min || 1) + n - 1
                            ? 'border-primary-500 bg-primary-500 text-white'
                            : 'border-gray-300 dark:border-gray-600 hover:border-primary-400 text-gray-700 dark:text-gray-300'
                        ]"
                      >
                        {{ (question.validation?.min || 1) + n - 1 }}
                      </button>
                    </div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                      {{ question.validation?.max || 10 }}
                    </span>
                  </div>
                </div>

                <!-- Matrix (simplified) -->
                <div v-else-if="question.type === 'matrix'">
                  <div class="overflow-x-auto">
                    <table class="w-full border border-gray-300 dark:border-gray-600 rounded-lg">
                      <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                          <th class="p-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300"></th>
                          <th
                            v-for="col in ['Pas du tout', 'Peu', 'Moyennement', 'Beaucoup', 'Énormément']"
                            :key="col"
                            class="p-3 text-center text-sm font-medium text-gray-700 dark:text-gray-300"
                          >
                            {{ col }}
                          </th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr
                          v-for="row in ['Critère 1', 'Critère 2']"
                          :key="row"
                          class="border-t border-gray-300 dark:border-gray-600"
                        >
                          <td class="p-3 text-sm text-gray-700 dark:text-gray-300 font-medium">{{ row }}</td>
                          <td v-for="(col, colIndex) in ['Pas du tout', 'Peu', 'Moyennement', 'Beaucoup', 'Énormément']" :key="col" class="p-3 text-center">
                            <input
                              type="radio"
                              :name="`matrix_${question.id}_${row}`"
                              :value="col"
                              @change="setMatrixAnswer(question.id, row, col)"
                              class="text-primary-600 focus:ring-primary-500"
                            />
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <!-- Ranking -->
                <div v-else-if="question.type === 'ranking'">
                  <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
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
                      class="flex items-center space-x-3 p-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg cursor-move hover:shadow-md transition-all"
                    >
                      <div class="flex items-center justify-center w-6 h-6 bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 rounded-full text-sm font-medium">
                        {{ index + 1 }}
                      </div>
                      <span class="text-gray-700 dark:text-gray-300">{{ item }}</span>
                      <div class="ml-auto text-gray-400">
                        <Bars3Icon class="w-5 h-5" />
                      </div>
                    </div>
                  </draggable>
                </div>
              </div>

              <!-- Validation Error -->
              <div v-if="errors[question.id]" class="mt-2 text-sm text-red-600 dark:text-red-400">
                {{ errors[question.id] }}
              </div>
            </div>
          </div>

          <!-- Navigation -->
          <div class="flex items-center justify-between pt-8 mt-8 border-t border-gray-200 dark:border-gray-700">
            <button
              v-if="survey.settings.allowBack && currentSectionIndex > 0"
              type="button"
              @click="goToPrevious"
              class="btn-secondary"
            >
              <ChevronLeftIcon class="w-4 h-4" />
              Précédent
            </button>
            <div v-else></div>

            <button
              type="submit"
              class="btn-primary"
            >
              {{ isLastSection ? 'Terminer' : 'Suivant' }}
              <ChevronRightIcon v-if="!isLastSection" class="w-4 h-4" />
              <CheckIcon v-else class="w-4 h-4" />
            </button>
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
  survey.value?.sections[currentSectionIndex.value] || null
);

const isLastSection = computed(() =>
  survey.value ? currentSectionIndex.value === survey.value.sections.length - 1 : false
);

const progress = computed(() => {
  if (!survey.value) return 0;
  return Math.round(((currentSectionIndex.value + 1) / survey.value.sections.length) * 100);
});

const visibleQuestions = computed(() => {
  if (!currentSection.value) return [];

  return currentSection.value.questions.filter(question => {
    if (!question.conditionalRules || question.conditionalRules.length === 0) return true;

    // Check all conditional rules - question is visible if ANY rule passes
    return question.conditionalRules.some(rule => {
      const dependentAnswer = answers.value[rule.dependsOn];
      const { operator, value, action, type } = rule;

      let conditionMet = false;

      switch (operator) {
        case 'equals':
          conditionMet = dependentAnswer === value;
          break;
        case 'not_equals':
          conditionMet = dependentAnswer !== value;
          break;
        case 'contains':
          conditionMet = Array.isArray(dependentAnswer) && dependentAnswer.includes(value);
          break;
        case 'not_contains':
          conditionMet = Array.isArray(dependentAnswer) && !dependentAnswer.includes(value);
          break;
        case 'greater_than':
          conditionMet = Number(dependentAnswer) > Number(value);
          break;
        case 'less_than':
          conditionMet = Number(dependentAnswer) < Number(value);
          break;
        case 'greater_equal':
          conditionMet = Number(dependentAnswer) >= Number(value);
          break;
        case 'less_equal':
          conditionMet = Number(dependentAnswer) <= Number(value);
          break;
        case 'starts_with':
          conditionMet = String(dependentAnswer).startsWith(String(value));
          break;
        case 'ends_with':
          conditionMet = String(dependentAnswer).endsWith(String(value));
          break;
        case 'is_empty':
          conditionMet = !dependentAnswer || dependentAnswer === '';
          break;
        case 'is_not_empty':
          conditionMet = dependentAnswer && dependentAnswer !== '';
          break;
        default:
          conditionMet = dependentAnswer === value;
      }

      // Apply the action based on rule type
      if (type === 'show_hide') {
        if (action === 'show') {
          return conditionMet; // Show if condition is met
        } else if (action === 'hide') {
          return !conditionMet; // Hide if condition is met (so show if NOT met)
        }
      }

      // For other rule types, default behavior is to show the question
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

      const dependentAnswer = answers.value[rule.dependsOn];
      const { operator, value } = rule;

      switch (operator) {
        case 'equals':
          return dependentAnswer === value;
        case 'not_equals':
          return dependentAnswer !== value;
        case 'contains':
          return Array.isArray(dependentAnswer) && dependentAnswer.includes(value);
        default:
          return false;
      }
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

      const dependentAnswer = answers.value[rule.dependsOn];
      const { operator, value } = rule;

      let conditionMet = false;
      switch (operator) {
        case 'equals':
          conditionMet = dependentAnswer === value;
          break;
        case 'not_equals':
          conditionMet = dependentAnswer !== value;
          break;
        case 'contains':
          conditionMet = Array.isArray(dependentAnswer) && dependentAnswer.includes(value);
          break;
      }

      if (conditionMet && rule.targetSectionId) {
        const targetIndex = survey.value?.sections.findIndex(s => s.id === rule.targetSectionId);
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
  if (!survey.value) return questionIndex + 1;

  let totalQuestions = 0;
  for (let i = 0; i < currentSectionIndex.value; i++) {
    totalQuestions += survey.value.sections[i].questions.length;
  }

  return totalQuestions + questionIndex + 1;
}

function setAnswer(questionId: string, value: any) {
  answers.value[questionId] = value;
  delete errors.value[questionId];

  // Auto-save if enabled
  if (survey.value?.settings.autoSave) {
    saveProgress();
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
    saveProgress();
  }
}

function setMatrixAnswer(questionId: string, row: string, value: string) {
  if (!answers.value[questionId]) {
    answers.value[questionId] = {};
  }
  answers.value[questionId][row] = value;

  if (survey.value?.settings.autoSave) {
    saveProgress();
  }
}

function updateRanking(questionId: string) {
  answers.value[questionId] = [...(rankingItems.value[questionId] || [])];

  if (survey.value?.settings.autoSave) {
    saveProgress();
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
          const dependentAnswer = answers.value[rule.dependsOn];
          let conditionMet = false;

          switch (rule.operator) {
            case 'equals':
              conditionMet = dependentAnswer === rule.value;
              break;
            case 'not_equals':
              conditionMet = dependentAnswer !== rule.value;
              break;
            case 'contains':
              conditionMet = Array.isArray(dependentAnswer) && dependentAnswer.includes(rule.value);
              break;
          }

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

function handleNext() {
  if (!validateCurrentSection()) {
    return;
  }

  // Check for early survey termination
  if (shouldEndSurvey.value) {
    completeSurvey();
    return;
  }

  // Check for section jump
  const jumpTarget = jumpTargetSection.value;
  if (jumpTarget !== null) {
    currentSectionIndex.value = jumpTarget;
    saveProgress();
    return;
  }

  if (isLastSection.value) {
    completeSurvey();
  } else {
    currentSectionIndex.value++;
    saveProgress();
  }
}

function goToPrevious() {
  if (currentSectionIndex.value > 0) {
    currentSectionIndex.value--;
  }
}

function saveProgress() {
  // In a real app, this would save to the backend
  if (survey.value) {
    const response = responseStore.createResponse(survey.value.id);
    responseStore.updateResponse(response.id, { answers: answers.value });
  }
}

function completeSurvey() {
  if (!survey.value) return;

  const response = responseStore.createResponse(survey.value.id);
  responseStore.updateResponse(response.id, {
    answers: answers.value,
    completed: true
  });
  responseStore.submitResponse(response.id);

  isCompleted.value = true;
}

function formatDuration(milliseconds: number): string {
  const minutes = Math.round(milliseconds / (1000 * 60));
  if (minutes < 60) return `${minutes}min`;
  const hours = Math.floor(minutes / 60);
  const remainingMinutes = minutes % 60;
  return `${hours}h${remainingMinutes > 0 ? ` ${remainingMinutes}min` : ''}`;
}

function initializeRankingItems() {
  if (!survey.value) return;

  survey.value.sections.forEach(section => {
    section.questions
      .filter(q => q.type === 'ranking')
      .forEach(question => {
        if (question.options) {
          rankingItems.value[question.id] = question.options.map(o => o.text);
        }
      });
  });
}

onMounted(async () => {
  // Simulate loading survey data
  await new Promise(resolve => setTimeout(resolve, 1000));

  // In a real app, you would fetch the survey based on the token
  // For demo purposes, we'll use the first published survey
  const publishedSurveys = surveyStore.publishedSurveys;
  if (publishedSurveys.length > 0) {
    survey.value = publishedSurveys[0];
    initializeRankingItems();
  }

  startTime.value = new Date();
  loading.value = false;
});

// Auto-save on page unload
onUnmounted(() => {
  if (survey.value?.settings.autoSave && Object.keys(answers.value).length > 0) {
    saveProgress();
  }
});
</script>
