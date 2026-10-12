<template>
  <div class="p-5 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-2xs transition-all mb-4">
    <div class="flex items-start gap-3.5">
      <!-- Drag Handle -->
      <div class="question-handle drag-handle mt-2 p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-grab active:cursor-grabbing rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700/60 transition-colors">
        <Bars3Icon class="w-5 h-5" />
      </div>

      <!-- Question Content -->
      <div class="flex-1 min-w-0">
        <!-- Question Header -->
        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
          <div class="flex flex-wrap items-center gap-2">
            <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-700/70 text-slate-700 dark:text-slate-300 text-xs font-bold font-mono">
              Q{{ question.sortOrder }}
            </span>
            <span
              :class="[
                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold',
                getQuestionTypeColor(question.typeQuestion)
              ]"
            >
              {{ getQuestionTypeLabel(question.typeQuestion) }}
            </span>

            <label class="inline-flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400 cursor-pointer select-none ml-1">
              <input
                type="checkbox"
                :checked="question.required"
                @change="updateQuestion({ required: !question.required })"
                class="q-check !w-3.5 !h-3.5"
              />
              <span class="font-medium">Obligatoire</span>
            </label>

            <!-- Badges for Conditional Logic -->
            <span v-if="hasConditionalRules" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-200 border border-amber-300 dark:border-amber-700">
              ⚡ Déclenche {{ conditionalRules.length }} règle(s)
            </span>
            <span v-if="incomingRules.length > 0" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-200 border border-blue-300 dark:border-blue-700">
              🔗 Conditionnée par {{ incomingRules.length }} question(s)
            </span>
            <span v-if="defaultVisibilityState" :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold border', defaultVisibilityState.color]">
              {{ defaultVisibilityState.label }}
            </span>
          </div>

          <div class="flex items-center gap-1">
            <ButtonDuplicate tooltip="Dupliquer la question" @confirm-duplicate="duplicateQuestion" />
            <ButtonDelete tooltip="Supprimer la question" @confirm-delete="$emit('delete', question.uuid)" />
          </div>
        </div>

        <!-- Question Title -->
        <FormField label="Libellé de la question" :required="question.required" class="mb-3">
          <input
            :value="question.label"
            @change="updateQuestion({ label: ($event.target as HTMLInputElement).value })"
            class="q-input text-base font-semibold"
            placeholder="Tapez votre question ici..."
          />
        </FormField>

        <!-- Question Description / Help -->
        <FormField label="Texte d'aide / Instructions" hint="Précisions affichées sous la question pour orienter le répondant" class="mb-4">
          <textarea
            :value="question.help || ''"
            @change="updateQuestion({ help: ($event.target as HTMLTextAreaElement).value })"
            class="q-input text-xs"
            placeholder="Description ou instructions complémentaires (optionnel)"
            rows="2"
          />
        </FormField>

        <!-- Question-specific Options -->
        <div class="space-y-4">
          <!-- Single Choice / Multiple Choice Options -->
          <div v-if="['single_choice', 'multiple_choice', 'ranking'].includes(question.typeQuestion)" class="p-3.5 rounded-xl bg-slate-50/70 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-700/80 space-y-3">
            <div class="flex items-center justify-between">
              <h4 class="q-label !mb-0">Options de réponse</h4>
              <Button
                size="small"
                severity="secondary"
                outlined
                icon="pi pi-plus"
                label="Ajouter une option"
                @click="addOption"
              />
            </div>
            <draggable
              v-model="questionOptions"
              handle=".option-handle"
              class="space-y-2"
            >
              <div
                v-for="(option, optionIndex) in questionOptions"
                :key="option.id"
                class="flex items-center gap-2 p-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80 rounded-xl shadow-2xs"
              >
                <div class="option-handle drag-handle p-1 text-slate-400 hover:text-slate-600 cursor-grab">
                  <Bars3Icon class="w-4 h-4" />
                </div>
                <div class="shrink-0">
                  <div
                    :class="[
                      'w-4 h-4 border-2 border-slate-300 dark:border-slate-600',
                      question.typeQuestion === 'single_choice' ? 'rounded-full' : 'rounded'
                    ]"
                  />
                </div>
                <input
                  :value="option.text"
                  @input="updateOption(optionIndex, { text: ($event.target as HTMLInputElement).value })"
                  class="flex-1 bg-transparent border-0 text-xs text-slate-900 dark:text-white focus:outline-none px-2 py-1"
                  placeholder="Texte de l'option"
                />
                <Button
                  v-if="questionOptions.length > 2"
                  severity="danger"
                  text
                  rounded
                  size="small"
                  icon="pi pi-times"
                  aria-label="Supprimer"
                  @click="removeOption(optionIndex)"
                />
              </div>
            </draggable>
          </div>

          <!-- Scale Options -->
          <div v-if="question.typeQuestion === 'scale'" class="p-3.5 rounded-xl bg-slate-50/70 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-700/80 space-y-3">
            <h4 class="q-label !mb-0">Paramètres de l'échelle</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="q-label">Valeur minimale</label>
                <input
                  type="number"
                  :value="question.opt?.min || 1"
                  @input="updateValidation({ min: parseInt(($event.target as HTMLInputElement).value) })"
                  class="q-input q-input-sm"
                  min="0"
                />
              </div>
              <div>
                <label class="q-label">Valeur maximale</label>
                <input
                  type="number"
                  :value="question.opt?.max || 10"
                  @input="updateValidation({ max: parseInt(($event.target as HTMLInputElement).value) })"
                  class="q-input q-input-sm"
                  min="1"
                />
              </div>
            </div>
          </div>

          <!-- Text Options -->
          <div v-if="['text_short', 'text_long'].includes(question.typeQuestion)" class="p-3.5 rounded-xl bg-slate-50/70 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-700/80 space-y-3">
            <h4 class="q-label !mb-0">Validation de la saisie</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="q-label">Longueur minimale (caractères)</label>
                <input
                  type="number"
                  :value="question.opt?.minLength || ''"
                  @input="updateValidation({ minLength: parseInt(($event.target as HTMLInputElement).value) || undefined })"
                  class="q-input q-input-sm"
                  min="0"
                  placeholder="0"
                />
              </div>
              <div>
                <label class="q-label">Longueur maximale (caractères)</label>
                <input
                  type="number"
                  :value="question.opt?.maxLength || ''"
                  @input="updateValidation({ maxLength: parseInt(($event.target as HTMLInputElement).value) || undefined })"
                  class="q-input q-input-sm"
                  min="1"
                  placeholder="Illimité"
                />
              </div>
            </div>
          </div>

          <!-- Matrix Options -->
          <div v-if="question.typeQuestion === 'matrix'" class="p-3.5 rounded-xl bg-slate-50/70 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-700/80 space-y-4">
            <div>
              <div class="flex items-center justify-between mb-2">
                <h4 class="q-label !mb-0">Lignes (éléments à évaluer)</h4>
                <Button size="small" severity="secondary" outlined icon="pi pi-plus" label="Ajouter une ligne" @click="addMatrixRow" />
              </div>
              <div class="space-y-2">
                <div
                  v-for="(row, rowIndex) in matrixRows"
                  :key="rowIndex"
                  class="flex items-center gap-2"
                >
                  <input
                    :value="row"
                    @input="updateMatrixRow(rowIndex, ($event.target as HTMLInputElement).value)"
                    class="flex-1 q-input q-input-sm"
                    placeholder="Ligne"
                  />
                  <Button
                    v-if="matrixRows.length > 1"
                    severity="danger"
                    text
                    rounded
                    size="small"
                    icon="pi pi-times"
                    aria-label="Supprimer"
                    @click="removeMatrixRow(rowIndex)"
                  />
                </div>
              </div>
            </div>

            <div>
              <div class="flex items-center justify-between mb-2">
                <h4 class="q-label !mb-0">Colonnes (échelle d'évaluation)</h4>
                <Button size="small" severity="secondary" outlined icon="pi pi-plus" label="Ajouter une colonne" @click="addMatrixColumn" />
              </div>
              <div class="space-y-2">
                <div
                  v-for="(col, colIndex) in matrixColumns"
                  :key="colIndex"
                  class="flex items-center gap-2"
                >
                  <input
                    :value="col"
                    @input="updateMatrixColumn(colIndex, ($event.target as HTMLInputElement).value)"
                    class="flex-1 q-input q-input-sm"
                    placeholder="Colonne"
                  />
                  <Button
                    v-if="matrixColumns.length > 1"
                    severity="danger"
                    text
                    rounded
                    size="small"
                    icon="pi pi-times"
                    aria-label="Supprimer"
                    @click="removeMatrixColumn(colIndex)"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Conditional Logic -->
        <div class="mt-4 pt-3 border-t border-slate-200 dark:border-slate-700/80">
          <div class="flex items-center justify-between mb-2">
            <h4 class="q-label !mb-0">Logique conditionnelle</h4>
            <Button
              size="small"
              :severity="hasConditionalRules ? 'warn' : 'secondary'"
              :outlined="!hasConditionalRules"
              icon="pi pi-bolt"
              :label="hasConditionalRules ? 'Gérer les règles' : 'Ajouter une règle'"
              @click="showConditionalModal = true"
            />
          </div>

          <!-- Outgoing rules (this question triggers rules) -->
          <div v-if="hasConditionalRules" class="space-y-2 mb-2">
            <h5 class="text-[11px] font-semibold uppercase tracking-wider text-amber-700 dark:text-amber-400">
              ⚡ Règles déclenchées par cette question ({{ conditionalRules.length }})
            </h5>
            <div
              v-for="(rule, index) in conditionalRules"
              :key="index"
              class="p-2.5 bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/60 rounded-xl text-xs text-amber-900 dark:text-amber-200"
            >
              {{ getConditionalRuleDescription(rule) }}
            </div>
          </div>

          <!-- Incoming rules (this question is controlled by another question) -->
          <div v-if="incomingRules.length > 0" class="space-y-2">
            <h5 class="text-[11px] font-semibold uppercase tracking-wider text-blue-700 dark:text-blue-400">
              🔗 Conditionnée par d'autres questions ({{ incomingRules.length }})
            </h5>
            <div
              v-for="(inc, index) in incomingRules"
              :key="index"
              class="p-2.5 bg-blue-50/70 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-900/60 rounded-xl text-xs text-blue-900 dark:text-blue-200"
            >
              Si <span class="font-semibold">"{{ inc.sourceQuestion.label }}"</span> {{ getOperatorLabel(inc.rule.operator) }} <span class="font-semibold">"{{ inc.rule.value }}"</span>, alors {{ inc.rule.action === 'show' ? 'afficher' : 'masquer' }} cette question.
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Conditional Logic Modal -->
    <ConditionalLogicModal
      v-if="showConditionalModal"
      :question="question"
      :all-questions="allQuestions || []"
      :all-sections="allSections || []"
      @close="showConditionalModal = false"
      @update="updateConditionalRules"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import {
  Bars3Icon
} from '@heroicons/vue/24/outline';
import Button from 'primevue/button';
import { VueDraggableNext as draggable } from 'vue-draggable-next';
import type { Question, QuestionOption } from '@types';
import { v4 as uuidv4 } from 'uuid';
import { FormField } from '../Form';
import ConditionalLogicModal from './ConditionalLogicModal.vue';
import ButtonDelete from "@components/components/Buttons/ButtonDelete.vue";
import ButtonDuplicate from "@components/components/Buttons/ButtonDuplicate.vue";

interface Props {
  question: Question;
  sectionId: string;
  index: number;
  allQuestions?: Question[];
  allSections?: any[];
}

interface Emits {
  update: [questionId: string, updates: Partial<Question>];
  delete: [questionId: string];
  duplicate: [questionId: string];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const showConditionalModal = ref(false);
const matrixRows = ref<string[]>(['Ligne 1', 'Ligne 2']);
const matrixColumns = ref<string[]>(['Pas du tout', 'Peu', 'Moyennement', 'Beaucoup', 'Énormément']);

const questionOptions = computed({
  get: () => props.question.choices || [],
  set: (value: QuestionOption[]) => {
    updateQuestion({ choices: value });
  }
});

const conditionalRules = computed(() => props.question.conditionalRules || []);
const hasConditionalRules = computed(() => conditionalRules.value.length > 0);

const incomingRules = computed(() => {
  if (!props.allQuestions) return [];
  const currentId = props.question.uuid || props.question.id;
  const result: { sourceQuestion: Question; rule: any }[] = [];

  props.allQuestions.forEach(q => {
    if (q.conditionalRules && (q.uuid || q.id) !== currentId) {
      q.conditionalRules.forEach(r => {
        if (r.targetQuestionIds?.includes(currentId as string)) {
          result.push({ sourceQuestion: q, rule: r });
        }
      });
    }
  });

  return result;
});

const defaultVisibilityState = computed(() => {
  if (incomingRules.value.length === 0) return null;
  const hasShowRule = incomingRules.value.some(inc => inc.rule.action === 'show');
  const hasHideRule = incomingRules.value.some(inc => inc.rule.action === 'hide');

  if (hasShowRule) {
    return {
      type: 'show',
      label: '👁️ Masquée au démarrage',
      color: 'bg-purple-100 text-purple-800 dark:bg-purple-900/60 dark:text-purple-200 border-purple-300 dark:border-purple-700'
    };
  }
  if (hasHideRule) {
    return {
      type: 'hide',
      label: '👁️ Visible au démarrage',
      color: 'bg-teal-100 text-teal-800 dark:bg-teal-900/60 dark:text-teal-200 border-teal-300 dark:border-teal-700'
    };
  }
  return null;
});

function updateQuestion(updates: Partial<Question>) {
  emit('update', props.question.uuid, updates);
}

function updateValidation(opt: Partial<Question['opt']>) {
  const currentValidation = props.question.opt || {};
  updateQuestion({
    opt: { ...currentValidation, ...opt }
  });
}

function getQuestionTypeLabel(type: string): string {
  const labels = {
    single_choice: 'Choix unique',
    multiple_choice: 'Choix multiples',
    text_short: 'Texte court',
    text_long: 'Texte long',
    scale: 'Échelle',
    matrix: 'Grille',
    ranking: 'Classement'
  };
  return labels[type as keyof typeof labels] || type;
}

function getQuestionTypeColor(type: string): string {
  const colors = {
    single_choice: 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300',
    multiple_choice: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300',
    text_short: 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300',
    text_long: 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300',
    scale: 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300',
    matrix: 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300',
    ranking: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-300'
  };
  return colors[type as keyof typeof colors] || 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200';
}

// Option management
function addOption() {
  const reponses = [...questionOptions.value];
  reponses.push({
    id: uuidv4(),
    text: `Option ${reponses.length + 1}`,
    value: `option${reponses.length + 1}`
  });
  updateQuestion({ choices: reponses });
}

function updateOption(index: number, updates: Partial<QuestionOption>) {
  const reponses = [...questionOptions.value];
  reponses[index] = { ...reponses[index], ...updates };
  updateQuestion({ choices: reponses });
}

function removeOption(index: number) {
  const reponses = [...questionOptions.value];
  reponses.splice(index, 1);
  updateQuestion({ choices: reponses });
}

// Matrix management
function addMatrixRow() {
  matrixRows.value.push(`Ligne ${matrixRows.value.length + 1}`);
}

function removeMatrixRow(index: number) {
  matrixRows.value.splice(index, 1);
}

function updateMatrixRow(index: number, value: string) {
  matrixRows.value[index] = value;
}

function addMatrixColumn() {
  matrixColumns.value.push(`Colonne ${matrixColumns.value.length + 1}`);
}

function removeMatrixColumn(index: number) {
  matrixColumns.value.splice(index, 1);
}

function updateMatrixColumn(index: number, value: string) {
  matrixColumns.value[index] = value;
}

// Other actions
function duplicateQuestion() {
  emit('duplicate', props.question);
}

function updateConditionalRules(rules: any[]) {
  updateQuestion({ conditionalRules: rules });
  showConditionalModal.value = false;
}

function getOperatorLabel(op: string): string {
  const operatorLabels: Record<string, string> = {
    equals: 'est égal à',
    not_equals: 'n\'est pas égal à',
    contains: 'contient',
    not_contains: 'ne contient pas',
    greater_than: 'est supérieur à',
    less_than: 'est inférieur à',
    greater_equal: 'est supérieur ou égal à',
    less_equal: 'est inférieur ou égal à',
    starts_with: 'commence par',
    ends_with: 'se termine par',
    is_empty: 'est vide',
    is_not_empty: 'n\'est pas vide'
  };
  return operatorLabels[op] || op;
}

function getTargetQuestionsNames(targetIds: string[] = []): string {
  if (!targetIds || targetIds.length === 0) return 'les questions cibles';

  const names = targetIds
    .map(id => {
      const q = props.allQuestions?.find(item => String(item.uuid || item.id) === String(id));
      return q ? `"${q.label}"` : null;
    })
    .filter(Boolean);

  if (names.length === 0) return `${targetIds.length} question(s) cible(s)`;
  if (names.length === 1) return `la question ${names[0]}`;
  return `les questions (${names.join(', ')})`;
}

function getConditionalRuleDescription(rule: any): string {
  const sourceQuestion = props.allQuestions?.find(q =>
    (q.uuid && q.uuid === rule.dependsOn) ||
    (q.id && (q.id === rule.dependsOn || String(q.id) === String(rule.dependsOn)))
  ) || props.question;

  const operatorLabel = getOperatorLabel(rule.operator);
  const isNoValueOp = ['is_empty', 'is_not_empty'].includes(rule.operator);
  const baseDescription = isNoValueOp
    ? `Si "${sourceQuestion.label}" ${operatorLabel}`
    : `Si "${sourceQuestion.label}" ${operatorLabel} "${rule.value}"`;

  switch (rule.type) {
    case 'show_hide': {
      const actionLabel = rule.action === 'show' ? 'afficher' : 'masquer';
      const targetsText = getTargetQuestionsNames(rule.targetQuestionIds);
      return `${baseDescription}, alors ${actionLabel} ${targetsText}`;
    }
    case 'jump_section':
      return `${baseDescription}, alors aller à une autre section`;
    case 'end_survey':
      return `${baseDescription}, alors terminer le questionnaire`;
    case 'set_required': {
      const requiredAction = rule.action === 'require' ? 'rendre obligatoire' : 'rendre optionnelle';
      const targetsText = getTargetQuestionsNames(rule.targetQuestionIds);
      return `${baseDescription}, alors ${requiredAction} ${targetsText}`;
    }
    default:
      return baseDescription;
  }
}
</script>
