<template>
  <div class="card">
    <div class="flex items-start space-x-4">
      <!-- Drag Handle -->
      <div class="question-handle drag-handle mt-3">
        <Bars3Icon class="w-5 h-5" />
      </div>

      <!-- Question Content -->
      <div class="flex-1">
        <!-- Question Header -->
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center space-x-3">
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
              Q{{ question.sortOrder }}
            </span>
            <span
              :class="[
                'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                getQuestionTypeColor(question.typeQuestion)
              ]"
            >
              {{ getQuestionTypeLabel(question.typeQuestion) }}
            </span>
            <div class="flex items-center space-x-2">
              <input
                type="checkbox"
                :checked="question.required"
                @change="updateQuestion({ required: !question.required })"
                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
              />
              <span class="text-sm text-gray-600 dark:text-gray-400">Obligatoire</span>
            </div>
          </div>
          <Menu as="div" class="relative">
            <MenuButton class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700">
              <EllipsisVerticalIcon class="w-4 h-4" />
            </MenuButton>
            <MenuItems class="question-menu">
              <MenuItem>
                <button @click="duplicateQuestion" class="menu-item w-full">
                  <DocumentDuplicateIcon class="w-4 h-4" />
                  Dupliquer
                </button>
              </MenuItem>
              <MenuItem>
                <button @click="$emit('delete', question.uuid)" class="menu-item w-full text-red-600 dark:text-red-400">
                  <TrashIcon class="w-4 h-4" />
                  Supprimer
                </button>
              </MenuItem>
            </MenuItems>
          </Menu>
        </div>

        <!-- Question Title -->
        <div class="mb-4">
          <input
            :value="question.label"
            @change="updateQuestion({ label: ($event.target as HTMLInputElement).value })"
            class="text-lg font-medium w-full bg-transparent border-none focus:outline-none focus:ring-2 focus:ring-primary-500 rounded px-3 py-2 border border-gray-200 dark:border-gray-600"
            placeholder="Tapez votre question ici..."
          />
        </div>

        <!-- Question Description -->
        <div class="mb-4">
          <textarea
            :value="question.help || ''"
            @change="updateQuestion({ help: ($event.target as HTMLTextAreaElement).value })"
            class="text-sm text-gray-600 dark:text-gray-400 w-full bg-transparent border border-gray-200 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded px-3 py-2 resize-none"
            placeholder="Description ou instructions (optionnel)"
            rows="2"
          />
        </div>

        <!-- Question-specific Options -->
        <div class="space-y-4">
          <!-- Single Choice / Multiple Choice Options -->
          <div v-if="['single_choice', 'multiple_choice', 'ranking'].includes(question.typeQuestion)">
            <div class="flex items-center justify-between mb-3">
              <h4 class="text-sm font-medium text-gray-900 dark:text-white">Options</h4>
              <Button @click="addOption"
                      severity="success"
                      class="text-sm">
                <PlusIcon class="w-4 h-4" />
                Ajouter
              </Button>
            </div>
            <draggable
              v-model="questionOptions"
              handle=".option-handle"
              class="space-y-2"
            >
              <div
                v-for="(option, optionIndex) in questionOptions"
                :key="option.id"
                class="flex items-center space-x-3 p-3 border border-gray-200 dark:border-gray-600 rounded-lg"
              >
                <div class="option-handle drag-handle">
                  <Bars3Icon class="w-4 h-4" />
                </div>
                <div class="flex-shrink-0">
                  <div
                    :class="[
                      'w-4 h-4 border-2 border-gray-300',
                      question.typeQuestion === 'single_choice' ? 'rounded-full' : 'rounded'
                    ]"
                  />
                </div>
                <input
                  :value="option.text"
                  @input="updateOption(optionIndex, { text: ($event.target as HTMLInputElement).value })"
                  class="flex-1 bg-transparent border-none focus:outline-none focus:ring-1 focus:ring-primary-500 rounded px-2 py-1"
                  placeholder="Texte de l'option"
                />
                <button
                  v-if="questionOptions.length > 2"
                  @click="removeOption(optionIndex)"
                  class="p-1 text-red-500 hover:text-red-700 rounded"
                >
                  <XMarkIcon class="w-4 h-4" />
                </button>
              </div>
            </draggable>
          </div>

          <!-- Scale Options -->
          <div v-if="question.typeQuestion === 'scale'">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Paramètres de l'échelle</h4>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Valeur minimale</label>
                <input
                  type="number"
                  :value="question.validation?.min || 1"
                  @input="updateValidation({ min: parseInt(($event.target as HTMLInputElement).value) })"
                  class="input-field"
                  min="0"
                />
              </div>
              <div>
                <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Valeur maximale</label>
                <input
                  type="number"
                  :value="question.validation?.max || 10"
                  @input="updateValidation({ max: parseInt(($event.target as HTMLInputElement).value) })"
                  class="input-field"
                  min="1"
                />
              </div>
            </div>
            <div class="mt-3">
              <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Libellés des extrémités</label>
              <div class="grid grid-cols-2 gap-4">
                <input
                  type="text"
                  placeholder="Libellé minimum"
                  class="input-field"
                />
                <input
                  type="text"
                  placeholder="Libellé maximum"
                  class="input-field"
                />
              </div>
            </div>
          </div>

          <!-- Text Options -->
          <div v-if="['text_short', 'text_long'].includes(question.typeQuestion)">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Validation du texte</h4>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Longueur minimale</label>
                <input
                  type="number"
                  :value="question.validation?.minLength || ''"
                  @input="updateValidation({ minLength: parseInt(($event.target as HTMLInputElement).value) || undefined })"
                  class="input-field"
                  min="0"
                  placeholder="0"
                />
              </div>
              <div>
                <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Longueur maximale</label>
                <input
                  type="number"
                  :value="question.validation?.maxLength || ''"
                  @input="updateValidation({ maxLength: parseInt(($event.target as HTMLInputElement).value) || undefined })"
                  class="input-field"
                  min="1"
                  placeholder="Illimité"
                />
              </div>
            </div>
          </div>

          <!-- Matrix Options -->
          <div v-if="question.typeQuestion === 'matrix'">
            <div class="space-y-4">
              <div>
                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Lignes (éléments à évaluer)</h4>
                <div class="space-y-2">
                  <div
                    v-for="(row, rowIndex) in matrixRows"
                    :key="rowIndex"
                    class="flex items-center space-x-3"
                  >
                    <input
                      :value="row"
                      @input="updateMatrixRow(rowIndex, ($event.target as HTMLInputElement).value)"
                      class="flex-1 input-field"
                      placeholder="Ligne"
                    />
                    <button
                      v-if="matrixRows.length > 1"
                      @click="removeMatrixRow(rowIndex)"
                      class="p-1 text-red-500 hover:text-red-700"
                    >
                      <XMarkIcon class="w-4 h-4" />
                    </button>
                  </div>
                  <Button @click="addMatrixRow"
                          severity="success"
                          class="text-sm">
                    <PlusIcon class="w-4 h-4" />
                    Ajouter une ligne
                  </Button>
                </div>
              </div>

              <div>
                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Colonnes (échelle d'évaluation)</h4>
                <div class="space-y-2">
                  <div
                    v-for="(col, colIndex) in matrixColumns"
                    :key="colIndex"
                    class="flex items-center space-x-3"
                  >
                    <input
                      :value="col"
                      @input="updateMatrixColumn(colIndex, ($event.target as HTMLInputElement).value)"
                      class="flex-1 input-field"
                      placeholder="Colonne"
                    />
                    <button
                      v-if="matrixColumns.length > 1"
                      @click="removeMatrixColumn(colIndex)"
                      class="p-1 text-red-500 hover:text-red-700"
                    >
                      <XMarkIcon class="w-4 h-4" />
                    </button>
                  </div>
                  <Button @click="addMatrixColumn"
                          severity="success"
                          class="text-sm">
                    <PlusIcon class="w-4 h-4" />
                    Ajouter une colonne
                  </Button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Conditional Logic -->
        <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white">Logique conditionnelle</h4>
            <Button
              @click="showConditionalModal = true"
              :severity="hasConditionalRules ? 'warn' : 'secondary'"
              :class="[
                'text-sm'
              ]"
            >
              {{ hasConditionalRules ? 'Modifier' : 'Ajouter' }}
            </Button>
          </div>
          <div v-if="hasConditionalRules" class="mt-3 space-y-2">
            <div
              v-for="(rule, index) in conditionalRules"
              :key="index"
              class="p-3 bg-blue-50 dark:bg-blue-900 rounded-lg"
            >
              <p class="text-sm text-blue-800 dark:text-blue-200">
                {{ getConditionalRuleDescription(rule) }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Conditional Logic Modal -->
    <ConditionalLogicModal
      v-if="showConditionalModal"
      :question="question"
      :all-questions="allQuestions"
      :all-sections="allSections"
      @close="showConditionalModal = false"
      @update="updateConditionalRules"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import {
  Bars3Icon,
  EllipsisVerticalIcon,
  DocumentDuplicateIcon,
  TrashIcon,
  PlusIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline';
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue';
import { VueDraggableNext as draggable } from 'vue-draggable-next';
import type { Question, QuestionOption } from '@types';
import { v4 as uuidv4 } from 'uuid';
import ConditionalLogicModal from './ConditionalLogicModal.vue';

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

function updateQuestion(updates: Partial<Question>) {
  emit('update', props.question.uuid, updates);
}

function updateValidation(validation: Partial<Question['validation']>) {
  const currentValidation = props.question.validation || {};
  updateQuestion({
    validation: { ...currentValidation, ...validation }
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
    single_choice: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    multiple_choice: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    text_short: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
    text_long: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
    scale: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    matrix: 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-200',
    ranking: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200'
  };
  return colors[type as keyof typeof colors] || 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
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

  // This would be handled by the parent component
  emit('duplicate', props.question);
}

function updateConditionalRules(rules: any[]) {
  updateQuestion({ conditionalRules: rules });
  showConditionalModal.value = false;
}

function getConditionalRuleDescription(rule: any): string {
  const sourceQuestion = props.allQuestions?.find(q => q.id === rule.dependsOn);
  if (!sourceQuestion) return 'Règle invalide';

  const operatorLabels: Record<string, string> = {
    equals: 'est égal à',
    not_equals: 'n\'est pas égal à',
    contains: 'contient',
    not_contains: 'ne contient pas',
    greater_than: 'est supérieur à',
    less_than: 'est inférieur à'
  };

  const operatorLabel = operatorLabels[rule.operator] || rule.operator;
  const baseDescription = `Si "${sourceQuestion.label}" ${operatorLabel} "${rule.value}"`;

  switch (rule.type) {
    case 'show_hide':
      const actionLabel = rule.action === 'show' ? 'afficher' : 'masquer';
      return `${baseDescription}, alors ${actionLabel} cette question`;
    case 'jump_section':
      return `${baseDescription}, alors aller à une autre section`;
    case 'end_survey':
      return `${baseDescription}, alors terminer le questionnaire`;
    case 'set_required':
      const requiredAction = rule.action === 'require' ? 'rendre obligatoire' : 'rendre optionnelle';
      return `${baseDescription}, alors ${requiredAction} cette question`;
    default:
      return baseDescription;
  }
}
</script>

<style scoped>
.question-menu {
  @apply absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50;
}

.menu-item {
  @apply flex items-center space-x-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors;
}
</style>
