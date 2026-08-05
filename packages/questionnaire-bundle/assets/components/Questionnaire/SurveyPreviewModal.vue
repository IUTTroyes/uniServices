<template>
  <Dialog header="Aperçu du questionnaire"
          :style="{ width: '80vw' }"
          :visible="true"
          @update:visible="$emit('close')"
          :modal="true" :closable="true">
    <div
        class="bg-white dark:bg-gray-800 rounded-xl w-full mx-4 max-h-[90vh] overflow-hidden flex flex-col"
        @click.stop
    >
      <div class="px-6 pt-4 pb-2 border-b border-gray-100 dark:border-gray-700/50 flex items-center justify-between">
        <p class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-2">
          <span class="px-2.5 py-1 bg-purple-100 dark:bg-purple-900/60 text-purple-800 dark:text-purple-200 text-xs font-semibold rounded-full flex items-center gap-1">
            🧪 Mode Test Interactif
          </span>
          Vous pouvez sélectionner des réponses pour tester en direct vos règles conditionnelles.
        </p>
      </div>

      <!-- Preview Content -->
      <div class="flex-1 overflow-y-auto p-6">
        <div class="mx-auto">
          <div v-if="survey">
            <!-- Survey Header -->
            <div class="text-center mb-8">
              <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                {{ survey.title }}
              </h1>
              <p v-if="survey.description" class="text-gray-600 dark:text-gray-400">
                {{ survey.description }}
              </p>
            </div>

            <!-- Progress Bar -->
            <div v-if="survey.opt?.showProgress" class="mb-8">
              <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
                <span>Progression</span>
                <span>{{ Math.round((currentSectionIndex + 1) / survey.sections.length * 100) }}%</span>
              </div>
              <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div
                    class="bg-primary-600 h-2 rounded-full transition-all duration-300"
                    :style="{ width: `${(currentSectionIndex + 1) / survey.sections.length * 100}%` }"
                />
              </div>
            </div>

            <!-- Current Section -->
            <ListSkeleton v-if="isLoadingSection" />
            <template v-else>
              <div v-if="currentSection" class="space-y-6">
                <div>
                  <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                    {{ currentSection.title }}
                  </h2>
                  <div v-if="currentSection.typeSection === 'configurable'" class="mb-2">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                      <Cog6ToothIcon class="w-3 h-3 mr-1" />
                      Section configurable
                    </span>
                  </div>
                  <p v-if="currentSection.description" class="text-gray-600 dark:text-gray-400 mb-6">
                    {{ currentSection.description }}
                  </p>
                </div>

                <!-- Questions -->
                <div class="space-y-6">
                  <div
                      v-for="(question, questionIndex) in visibleQuestions"
                      :key="question.id || question.uuid"
                      class="bg-gray-50 dark:bg-gray-700/60 rounded-xl p-5 border border-gray-200/80 dark:border-gray-600/80 transition-all duration-200"
                  >

                    <div class="mb-3">
                      <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ questionIndex + 1 }}. {{ question.label }}
                        <span v-if="question.required" class="text-red-500">*</span>
                      </h3>
                      <p v-if="question.help" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ question.help }}
                      </p>
                    </div>

                    <!-- Question Preview (Interactive) -->
                    <div class="mt-3">
                      <!-- Single Choice -->
                      <div v-if="question.typeQuestion === 'single_choice'" class="space-y-2">
                        <div
                            v-for="option in question.choices"
                            :key="option.id"
                            class="flex items-center space-x-3 p-3 border border-gray-200 dark:border-gray-600 rounded-lg hover:border-primary-400 cursor-pointer bg-white dark:bg-gray-800 transition-colors"
                            @click="setAnswer(question.id || question.uuid, option.text)"
                        >
                          <input type="radio" :name="`question_${question.id || question.uuid}`" class="text-primary-600"
                          :checked="answers[question.id || question.uuid] === option.text"
                          :value="option.text"
                          @change="setAnswer(question.id || question.uuid, option.text)"
                          />
                          <label class="text-gray-700 dark:text-gray-300 cursor-pointer flex-1">{{ option.text }}</label>
                        </div>
                      </div>

                      <!-- Multiple Choice -->
                      <div v-else-if="question.typeQuestion === 'multiple_choice'" class="space-y-2">
                        <div
                            v-for="option in question.choices"
                            :key="option.id"
                            class="flex items-center space-x-3 p-3 border border-gray-200 dark:border-gray-600 rounded-lg hover:border-primary-400 cursor-pointer bg-white dark:bg-gray-800 transition-colors"
                            @click="toggleMultipleChoice(question.id || question.uuid, option.text)"
                        >
                          <input type="checkbox" class="text-primary-600 rounded"
                                 :checked="(answers[question.id || question.uuid] || []).includes(option.text)"
                                 :value="option.text"
                                 @change="toggleMultipleChoice(question.id || question.uuid, option.text)" />
                          <label class="text-gray-700 dark:text-gray-300 cursor-pointer flex-1">{{ option.text }}</label>
                        </div>
                      </div>

                      <!-- Text Short -->
                      <div v-else-if="question.typeQuestion === 'text_short'">
                        <input
                            type="text"
                            :value="answers[question.id || question.uuid] || ''"
                            @input="setAnswer(question.id || question.uuid, ($event.target as HTMLInputElement).value)"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                            placeholder="Votre réponse..."
                        />
                      </div>

                      <!-- Text Long -->
                      <div v-else-if="question.typeQuestion === 'text_long'">
                        <textarea
                            :value="answers[question.id || question.uuid] || ''"
                            @input="setAnswer(question.id || question.uuid, ($event.target as HTMLTextAreaElement).value)"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white resize-none"
                            rows="4"
                            placeholder="Votre réponse..."
                        />
                      </div>

                      <!-- Scale -->
                      <div v-else-if="question.typeQuestion === 'scale'">
                        <div class="flex items-center justify-between">
                          <span class="text-sm text-gray-600 dark:text-gray-400">
                            {{ question.scale?.minLabel || question.validation?.min || 1 }}
                          </span>
                          <div class="flex space-x-2">
                            <button
                                v-for="n in ((question.scale?.max || question.validation?.max || 10) - (question.scale?.min || question.validation?.min || 1) + 1)"
                                :key="n"
                                :class="[
                                  'w-10 h-10 rounded-full border-2 flex items-center justify-center text-sm font-medium transition-all',
                                  answers[question.id || question.uuid] === ((question.scale?.min || question.validation?.min || 1) + n - 1)
                                    ? 'border-primary-600 bg-primary-50 text-primary-700 dark:bg-primary-900/40 dark:text-primary-300 font-bold shadow-sm'
                                    : 'border-gray-300 dark:border-gray-600 hover:border-primary-400 text-gray-700 dark:text-gray-200'
                                ]"
                                @click="setAnswer(question.id || question.uuid, (question.scale?.min || question.validation?.min || 1) + n - 1)"
                            >
                              {{ (question.scale?.min || question.validation?.min || 1) + n - 1 }}
                            </button>
                          </div>
                          <span class="text-sm text-gray-600 dark:text-gray-400">
                            {{ question.scale?.maxLabel || question.validation?.max || 10 }}
                          </span>
                        </div>
                      </div>

                      <!-- Matrix -->
                      <div v-else-if="question.typeQuestion === 'matrix'">
                        <div class="overflow-x-auto">
                          <table class="w-full border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800">
                            <thead class="bg-gray-100 dark:bg-gray-700">
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
                              <td v-for="n in 5" :key="n" class="p-3 text-center">
                                <input type="radio" :name="`matrix_${row}`" />
                              </td>
                            </tr>
                            </tbody>
                          </table>
                        </div>
                      </div>

                      <!-- Ranking -->
                      <div v-else-if="question.typeQuestion === 'ranking'">
                        <div class="space-y-2">
                          <div
                              v-for="(option, index) in question.choices"
                              :key="option.id"
                              class="flex items-center space-x-3 p-3 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg"
                          >
                            <div class="flex items-center justify-center w-6 h-6 bg-gray-200 dark:bg-gray-600 rounded text-sm font-medium">
                              {{ index + 1 }}
                            </div>
                            <span class="text-gray-700 dark:text-gray-300">{{ option.text }}</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Navigation -->
              <div class="flex items-center justify-between mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                <Button
                    severity="secondary"
                    v-if="survey.opt?.allowBack && currentSectionIndex > 0"
                    @click="prevSection()"
                >
                  <ChevronLeftIcon class="w-4 h-4" />
                  Précédent
                </Button>
                <div v-else></div>

                <div class="flex items-center space-x-2">
                  <Button
                      v-if="currentSectionIndex < survey.sections.length - 1"
                      @click="nextSection()"
                  >
                    Suivant
                    <ChevronRightIcon class="w-4 h-4" />
                  </Button>
                  <Button
                      v-else
                  >
                    Terminer (Mode Aperçu)
                    <CheckIcon class="w-4 h-4" />
                  </Button>
                </div>
              </div>
            </template>
          </div>
        </div>
      </div>
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import {
  ChevronLeftIcon,
  ChevronRightIcon,
  CheckIcon,
  Cog6ToothIcon
} from '@heroicons/vue/24/outline';
import type { Section, Survey } from '@/types/survey';
import { getPreviewQuestionnaire, getPreviewQuestionnaireSection } from '@/requests/questionnaire_services/questionnaireService.js';
import { ListSkeleton } from '@components';

interface Props {
  uuid: string;
  sections?: Section[];
}

interface Emits {
  close: [];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const survey = ref<Survey>();
const currentSection = ref<Section>();
const isLoadingSection = ref<boolean>(true);
const currentSectionIndex = ref(0);

const answers = ref<Record<string | number, any>>({});

function getAllQuestions(): any[] {
  const allQ: any[] = [];
  if (props.sections && props.sections.length > 0) {
    props.sections.forEach((s: any) => { if (s.questions) allQ.push(...s.questions); });
  } else if (survey.value?.sections) {
    survey.value.sections.forEach((s: any) => { if (s.questions) allQ.push(...s.questions); });
  }
  return allQ;
}

function setAnswer(questionId: string | number, value: any) {
  const allQ = getAllQuestions();
  const qObj = allQ.find(q => String(q.uuid) === String(questionId) || String(q.id) === String(questionId) || String(q.questionId) === String(questionId));

  const newAnswers = { ...answers.value, [questionId]: value };
  if (qObj) {
    if (qObj.uuid) newAnswers[qObj.uuid] = value;
    if (qObj.id !== undefined) newAnswers[qObj.id] = value;
    if (qObj.questionId !== undefined) newAnswers[qObj.questionId] = value;
  }
  answers.value = newAnswers;
}

function toggleMultipleChoice(questionId: string | number, optionText: string) {
  const current = (answers.value[questionId] as string[]) || [];
  let updated: string[];
  if (current.includes(optionText)) {
    updated = current.filter(item => item !== optionText);
  } else {
    updated = [...current, optionText];
  }
  setAnswer(questionId, updated);
}

function getAnswerForQuestion(questionId: any): any {
  let val = answers.value[questionId];
  if (val !== undefined) return val;

  // Search by UUID or ID match
  const allQuestions = getAllQuestions();
  allQuestions.forEach((sq: any) => {
    const sqId = sq.id || sq.uuid || sq.questionId;
    if (String(sqId) === String(questionId) && answers.value[sqId] !== undefined) {
      val = answers.value[sqId];
    }
  });

  return val;
}

function evaluateConditionValue(operator: string, value: any, dependentAnswer: any): boolean {
  switch (operator) {
    case 'equals':
      return String(dependentAnswer ?? '') === String(value ?? '');
    case 'not_equals':
      return String(dependentAnswer ?? '') !== String(value ?? '');
    case 'contains':
      if (Array.isArray(dependentAnswer)) {
        return dependentAnswer.includes(value);
      }
      return String(dependentAnswer || '').includes(String(value));
    case 'not_contains':
      if (Array.isArray(dependentAnswer)) {
        return !dependentAnswer.includes(value);
      }
      return !String(dependentAnswer || '').includes(String(value));
    case 'greater_than':
      return Number(dependentAnswer) > Number(value);
    case 'less_than':
      return Number(dependentAnswer) < Number(value);
    case 'greater_equal':
      return Number(dependentAnswer) >= Number(value);
    case 'less_equal':
      return Number(dependentAnswer) <= Number(value);
    case 'starts_with':
      return String(dependentAnswer || '').startsWith(String(value));
    case 'ends_with':
      return String(dependentAnswer || '').endsWith(String(value));
    case 'is_empty':
      return dependentAnswer === undefined || dependentAnswer === null || dependentAnswer === '';
    case 'is_not_empty':
      return dependentAnswer !== undefined && dependentAnswer !== null && dependentAnswer !== '';
    default:
      return String(dependentAnswer ?? '') === String(value ?? '');
  }
}

function evaluateRule(rule: any): boolean {
  const conditions = rule.conditions && rule.conditions.length > 0
    ? rule.conditions
    : [{ dependsOn: rule.dependsOn, operator: rule.operator, value: rule.value }];

  const op = rule.logicalOperator || 'AND';

  if (op === 'OR') {
    return conditions.some((c: any) => {
      const dependentAnswer = getAnswerForQuestion(c.dependsOn || c.dependsOnQuestionId);
      return evaluateConditionValue(c.operator, c.value, dependentAnswer);
    });
  } else {
    return conditions.every((c: any) => {
      const dependentAnswer = getAnswerForQuestion(c.dependsOn || c.dependsOnQuestionId);
      return evaluateConditionValue(c.operator, c.value, dependentAnswer);
    });
  }
}

const visibleQuestions = computed(() => {
  if (!currentSection.value || !currentSection.value.questions) return [];

  const allQuestions = getAllQuestions();

  return currentSection.value.questions.filter((question: any) => {
    const qId = question.id || question.uuid || question.questionId;

    // Collect all applicable rules for this question
    const applicableRules: any[] = [];

    // 1. Check if question has a DTO visibility property from backend preview
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

    // 2. Check conditionalRules across all questions in the survey
    allQuestions.forEach((sq: any) => {
      if (sq.conditionalRules && Array.isArray(sq.conditionalRules)) {
        sq.conditionalRules.forEach((r: any) => {
          const isTargeted = (r.targetQuestionIds && Array.isArray(r.targetQuestionIds) && r.targetQuestionIds.length > 0)
            ? r.targetQuestionIds.some((tid: any) => String(tid) === String(qId) || (question.uuid && String(tid) === String(question.uuid)))
            : (String(r.dependsOn) !== String(qId) && (question.uuid ? String(r.dependsOn) !== String(question.uuid) : true));

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

    return applicableRules.some(rule => {
      const conditionMet = evaluateRule(rule);
      const { action } = rule;

      if (action === 'show') {
        return conditionMet;
      } else if (action === 'hide') {
        return !conditionMet;
      }
      return true;
    });
  });
});

onMounted(async () => {
  survey.value = await getPreviewQuestionnaire(props.uuid);
  currentSection.value = await getPreviewQuestionnaireSection(props.uuid, survey.value.sections[currentSectionIndex.value].key);
  isLoadingSection.value = false;
});

const nextSection = async () => {
  if (survey.value && currentSectionIndex.value < survey.value.sections.length - 1) {
    isLoadingSection.value = true;
    currentSectionIndex.value++;
    currentSection.value = await getPreviewQuestionnaireSection(props.uuid, survey.value.sections[currentSectionIndex.value].key);
    isLoadingSection.value = false;
  }
};

const prevSection = async () => {
  if (currentSectionIndex.value > 0 && survey.value) {
    isLoadingSection.value = true;
    currentSectionIndex.value--;
    currentSection.value = await getPreviewQuestionnaireSection(props.uuid, survey.value.sections[currentSectionIndex.value].key);
    isLoadingSection.value = false;
  }
};
</script>

