<template>
  <Dialog
    :style="{ width: '92vw', maxWidth: '900px' }"
    :visible="true"
    :modal="true"
    :closable="true"
    :draggable="false"
    @update:visible="$emit('close')"
  >
    <template #header>
      <DialogHeader
        :icon="EyeIcon"
        title="Aperçu du questionnaire"
        subtitle="Test interactif des questions et des règles conditionnelles"
      />
    </template>

    <div class="q-form">
      <div class="q-callout q-callout-info">
        <SparklesIcon class="w-4 h-4 shrink-0 mt-0.5" />
        <div class="flex items-center justify-between gap-2 w-full">
          <p class="font-semibold text-xs">
            Mode Test Interactif — vous pouvez cocher et saisir des réponses pour tester en direct le comportement du questionnaire.
          </p>
        </div>
      </div>

      <!-- Preview Content -->
      <div v-if="survey" class="space-y-5">
        <!-- Survey Header -->
        <div class="text-center py-2 border-b border-slate-200 dark:border-slate-700/80">
          <h1 class="text-xl font-bold text-slate-900 dark:text-white">
            {{ survey.title }}
          </h1>
          <p v-if="survey.description" class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-2xl mx-auto">
            {{ survey.description }}
          </p>
        </div>

        <!-- Progress Bar -->
        <div v-if="survey.opt?.showProgress" class="space-y-1.5">
          <div class="flex items-center justify-between text-xs font-semibold text-slate-600 dark:text-slate-400">
            <span>Progression</span>
            <span>{{ Math.round((currentSectionIndex + 1) / survey.sections.length * 100) }}%</span>
          </div>
          <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2 overflow-hidden">
            <div
              class="bg-primary-600 h-2 rounded-full transition-all duration-300"
              :style="{ width: `${(currentSectionIndex + 1) / survey.sections.length * 100}%` }"
            />
          </div>
        </div>

        <!-- Current Section -->
        <ListSkeleton v-if="isLoadingSection" />
        <template v-else>
          <div v-if="currentSection" class="space-y-4">
            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-700/80">
              <div class="flex items-center gap-2">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">
                  {{ currentSection.title }}
                </h2>
                <span
                  v-if="currentSection.typeSection === 'configurable'"
                  class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 inline-flex items-center gap-1"
                >
                  <Cog6ToothIcon class="w-3 h-3" />
                  Section configurable
                </span>
              </div>
              <p v-if="currentSection.description" class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                {{ currentSection.description }}
              </p>
            </div>

            <!-- Questions -->
            <div class="space-y-4">
              <div
                v-for="(question, questionIndex) in visibleQuestions"
                :key="question.id || question.uuid"
                class="p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80 shadow-2xs space-y-3"
              >
                <div>
                  <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                    {{ questionIndex + 1 }}. {{ question.label }}
                    <span v-if="question.required" class="text-red-500">*</span>
                  </h3>
                  <p v-if="question.help" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    {{ question.help }}
                  </p>
                </div>

                <!-- Question Preview (Interactive) -->
                <div>
                  <!-- Single Choice -->
                  <div v-if="question.typeQuestion === 'single_choice'" class="space-y-2">
                    <label
                      v-for="option in question.choices"
                      :key="option.id"
                      :class="['q-choice', answers[question.id || question.uuid] === option.text && 'q-choice-active']"
                    >
                      <input
                        type="radio"
                        :name="`question_${question.id || question.uuid}`"
                        class="q-check mt-0.5"
                        :checked="answers[question.id || question.uuid] === option.text"
                        :value="option.text"
                        @change="setAnswer(question.id || question.uuid, option.text)"
                      />
                      <span class="text-xs font-medium text-slate-800 dark:text-slate-200 flex-1">{{ option.text }}</span>
                    </label>
                  </div>

                  <!-- Multiple Choice -->
                  <div v-else-if="question.typeQuestion === 'multiple_choice'" class="space-y-2">
                    <label
                      v-for="option in question.choices"
                      :key="option.id"
                      :class="['q-choice', (answers[question.id || question.uuid] || []).includes(option.text) && 'q-choice-active']"
                    >
                      <input
                        type="checkbox"
                        class="q-check mt-0.5"
                        :checked="(answers[question.id || question.uuid] || []).includes(option.text)"
                        :value="option.text"
                        @change="toggleMultipleChoice(question.id || question.uuid, option.text)"
                      />
                      <span class="text-xs font-medium text-slate-800 dark:text-slate-200 flex-1">{{ option.text }}</span>
                    </label>
                  </div>

                  <!-- Text Short -->
                  <div v-else-if="question.typeQuestion === 'text_short'">
                    <input
                      type="text"
                      :value="answers[question.id || question.uuid] || ''"
                      @input="setAnswer(question.id || question.uuid, ($event.target as HTMLInputElement).value)"
                      class="q-input"
                      placeholder="Votre réponse..."
                    />
                  </div>

                  <!-- Text Long -->
                  <div v-else-if="question.typeQuestion === 'text_long'">
                    <textarea
                      :value="answers[question.id || question.uuid] || ''"
                      @input="setAnswer(question.id || question.uuid, ($event.target as HTMLTextAreaElement).value)"
                      class="q-input"
                      rows="4"
                      placeholder="Votre réponse..."
                    />
                  </div>

                  <!-- Scale -->
                  <div v-else-if="question.typeQuestion === 'scale'" class="space-y-2">
                    <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                      <span>{{ question.scale?.minLabel || question.validation?.min || 1 }}</span>
                      <span>{{ question.scale?.maxLabel || question.validation?.max || 10 }}</span>
                    </div>
                    <div class="flex flex-wrap gap-2 justify-center">
                      <button
                        v-for="n in ((question.scale?.max || question.validation?.max || 10) - (question.scale?.min || question.validation?.min || 1) + 1)"
                        :key="n"
                        type="button"
                        :class="[
                          'w-9 h-9 rounded-xl border text-xs font-bold transition-all cursor-pointer flex items-center justify-center',
                          answers[question.id || question.uuid] === ((question.scale?.min || question.validation?.min || 1) + n - 1)
                            ? 'bg-primary-600 text-white border-primary-600 shadow-sm'
                            : 'bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:border-primary-400'
                        ]"
                        @click="setAnswer(question.id || question.uuid, (question.scale?.min || question.validation?.min || 1) + n - 1)"
                      >
                        {{ (question.scale?.min || question.validation?.min || 1) + n - 1 }}
                      </button>
                    </div>
                  </div>

                  <!-- Matrix -->
                  <div v-else-if="question.typeQuestion === 'matrix'" class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700/80">
                    <table class="w-full text-xs">
                      <thead class="bg-slate-50 dark:bg-slate-900/60 text-slate-700 dark:text-slate-300 font-semibold">
                        <tr>
                          <th class="p-2.5 text-left"></th>
                          <th v-for="col in ['Pas du tout', 'Peu', 'Moyennement', 'Beaucoup', 'Énormément']" :key="col" class="p-2.5 text-center">
                            {{ col }}
                          </th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-slate-200 dark:divide-slate-700/80 bg-white dark:bg-slate-800">
                        <tr v-for="row in ['Critère 1', 'Critère 2']" :key="row">
                          <td class="p-2.5 font-medium text-slate-800 dark:text-slate-200">{{ row }}</td>
                          <td v-for="n in 5" :key="n" class="p-2.5 text-center">
                            <input type="radio" :name="`matrix_${row}`" class="q-check !w-3.5 !h-3.5 mx-auto" />
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  <!-- Ranking -->
                  <div v-else-if="question.typeQuestion === 'ranking'" class="space-y-2">
                    <div
                      v-for="(option, index) in question.choices"
                      :key="option.id"
                      class="flex items-center gap-3 p-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80 rounded-xl shadow-2xs"
                    >
                      <div class="flex items-center justify-center w-6 h-6 bg-slate-100 dark:bg-slate-700 rounded-lg text-xs font-bold text-slate-700 dark:text-slate-200">
                        {{ index + 1 }}
                      </div>
                      <span class="text-xs font-medium text-slate-800 dark:text-slate-200">{{ option.text }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Navigation -->
            <div class="q-actions !justify-between">
              <Button
                v-if="survey.opt?.allowBack && currentSectionIndex > 0"
                severity="secondary"
                outlined
                icon="pi pi-chevron-left"
                label="Précédent"
                @click="prevSection()"
              />
              <div v-else></div>

              <div class="flex items-center gap-2">
                <Button
                  v-if="currentSectionIndex < survey.sections.length - 1"
                  icon="pi pi-chevron-right"
                  iconPos="right"
                  label="Suivant"
                  @click="nextSection()"
                />
                <Button
                  v-else
                  icon="pi pi-check"
                  label="Terminer (Mode Aperçu)"
                  @click="$emit('close')"
                />
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import {
  Cog6ToothIcon,
  EyeIcon,
  SparklesIcon
} from '@heroicons/vue/24/outline';
import type { Section, Survey } from '@/types/survey';
import { getPreviewQuestionnaire, getPreviewQuestionnaireSection } from '@/requests/questionnaire_services/questionnaireService.js';
import { ListSkeleton } from '@components';
import { DialogHeader } from '../Form';

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

  const allQuestions = getAllQuestions();
  allQuestions.forEach((sq: any) => {
    const sqId = sq.id || sq.uuid || sq.questionId;
    if (String(sqId) === String(questionId) && answers.value[sqId] !== undefined) {
      val = answers.value[sqId];
    }
  });

  return val;
}

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

    const applicableRules: any[] = [];

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

    allQuestions.forEach((sq: any) => {
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
