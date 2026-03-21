<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click="$emit('close')">
    <div
      class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-4xl mx-4 max-h-[90vh] overflow-y-auto"
      @click.stop
    >
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
          Logique conditionnelle
        </h2>
        <Button
          @click="$emit('close')"
          class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
        >
          <XMarkIcon class="w-5 h-5" />
        </Button>
      </div>

      <div class="space-y-6">
        <!-- Rule Type Selection -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
            Type de règle
          </label>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div
              v-for="ruleType in ruleTypes"
              :key="ruleType.value"
              :class="[
                'p-4 border-2 rounded-lg cursor-pointer transition-all',
                selectedRuleType === ruleType.value
                  ? 'border-primary-500 bg-primary-50 dark:bg-primary-900'
                  : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
              ]"
              @click="selectedRuleType = ruleType.value"
            >
              <div class="flex items-start space-x-3">
                <component :is="ruleType.icon" class="w-6 h-6 text-primary-600 dark:text-primary-400 mt-1" />
                <div>
                  <h3 class="font-medium text-gray-900 dark:text-white">{{ ruleType.title }}</h3>
                  <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ ruleType.description }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Rule Configuration -->
        <div v-if="selectedRuleType" class="space-y-4">
          <!-- Source Question -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Question déclencheur
            </label>
            <select v-model="rule.sourceQuestionId" class="input-field">
              <option value="">Sélectionnez une question</option>
              <option
                v-for="question in availableQuestions"
                :key="question.id"
                :value="question.id"
              >
                {{ question.libelle }}
              </option>
            </select>
          </div>

          <!-- Condition -->
          <div v-if="rule.sourceQuestionId">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Condition
            </label>
            <div class="grid grid-cols-2 gap-4">
              <select v-model="rule.operator" class="input-field">
                <option
                  v-for="operator in availableOperators"
                  :key="operator.value"
                  :value="operator.value"
                >
                  {{ operator.label }}
                </option>
              </select>

              <!-- Value input based on source question type -->
              <div v-if="sourceQuestion">
                <!-- Single/Multiple Choice -->
                <select
                  v-if="['single_choice', 'multiple_choice'].includes(sourceQuestion.typeQuestion)"
                  v-model="rule.value"
                  class="input-field"
                >
                  <option value="">Sélectionnez une option</option>
                  <option
                    v-for="option in sourceQuestion.reponses"
                    :key="option.id"
                    :value="option.text"
                  >
                    {{ option.text }}
                  </option>
                </select>

                <!-- Scale -->
                <input
                  v-else-if="sourceQuestion.typeQuestion === 'scale'"
                  v-model.number="rule.value"
                  type="number"
                  :min="sourceQuestion.validation?.min || 1"
                  :max="sourceQuestion.validation?.max || 10"
                  class="input-field"
                  placeholder="Valeur"
                />

                <!-- Text -->
                <input
                  v-else-if="['text_short', 'text_long'].includes(sourceQuestion.typeQuestion)"
                  v-model="rule.value"
                  type="text"
                  class="input-field"
                  placeholder="Texte à comparer"
                />
              </div>
            </div>
          </div>

          <!-- Action Configuration -->
          <div v-if="rule.sourceQuestionId && rule.operator && rule.value !== ''">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Action
            </label>

            <!-- Show/Hide Questions -->
            <div v-if="selectedRuleType === 'show_hide'">
              <div class="space-y-3">
                <div class="flex items-center space-x-4">
                  <label class="flex items-center">
                    <input
                      v-model="rule.action"
                      type="radio"
                      value="show"
                      class="text-primary-600 focus:ring-primary-500"
                    />
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Afficher les questions</span>
                  </label>
                  <label class="flex items-center">
                    <input
                      v-model="rule.action"
                      type="radio"
                      value="hide"
                      class="text-primary-600 focus:ring-primary-500"
                    />
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Masquer les questions</span>
                  </label>
                </div>

                <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                  <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                    Questions concernées
                  </h4>

                  <div class="space-y-2 max-h-40 overflow-y-auto">
                    <label
                      v-for="question in targetQuestions"
                      :key="question.id"
                      class="flex items-center space-x-2"
                    >
                      <input
                        v-model="rule.targetQuestionIds"
                        type="checkbox"
                        :value="question.id"
                        class="text-primary-600 focus:ring-primary-500 rounded"
                      />
                      <span class="text-sm text-gray-700 dark:text-gray-300">{{ question.libelle }}</span>
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- Jump to Section -->
            <div v-else-if="selectedRuleType === 'jump_section'">
              <select v-model="rule.targetSectionId" class="input-field">
                <option value="">Sélectionnez une section</option>
                <option
                  v-for="section in availableSections"
                  :key="section.id"
                  :value="section.id"
                >
                  {{ section.titre }}
                </option>
              </select>
            </div>

            <!-- End Survey -->
            <div v-else-if="selectedRuleType === 'end_survey'">
              <div class="bg-yellow-50 dark:bg-yellow-900 p-4 rounded-lg">
                <div class="flex items-start space-x-3">
                  <ExclamationTriangleIcon class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mt-0.5" />
                  <div>
                    <h4 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                      Terminer le questionnaire
                    </h4>
                    <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                      Cette action terminera immédiatement le questionnaire pour le participant.
                    </p>
                  </div>
                </div>
                <textarea
                  v-model="rule.endMessage"
                  class="mt-3 w-full input-field"
                  rows="3"
                  placeholder="Message de fin personnalisé (optionnel)"
                />
              </div>
            </div>

            <!-- Set Required -->
            <div v-else-if="selectedRuleType === 'set_required'">
              <div class="space-y-3">
                <div class="flex items-center space-x-4">
                  <label class="flex items-center">
                    <input
                      v-model="rule.action"
                      type="radio"
                      value="require"
                      class="text-primary-600 focus:ring-primary-500"
                    />
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Rendre obligatoire</span>
                  </label>
                  <label class="flex items-center">
                    <input
                      v-model="rule.action"
                      type="radio"
                      value="optional"
                      class="text-primary-600 focus:ring-primary-500"
                    />
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Rendre optionnel</span>
                  </label>
                </div>

                <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                  <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                    Questions concernées
                  </h4>
                  <div class="space-y-2 max-h-40 overflow-y-auto">
                    <label
                      v-for="question in targetQuestions"
                      :key="question.id"
                      class="flex items-center space-x-2"
                    >
                      <input
                        v-model="rule.targetQuestionIds"
                        type="checkbox"
                        :value="question.id"
                        class="text-primary-600 focus:ring-primary-500 rounded"
                      />
                      <span class="text-sm text-gray-700 dark:text-gray-300">{{ question.libelle }}</span>
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Rule Preview -->
          <div v-if="isRuleComplete" class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">
            <h4 class="text-sm font-medium text-blue-900 dark:text-blue-200 mb-2">
              Aperçu de la règle
            </h4>
            <p class="text-sm text-blue-800 dark:text-blue-300">
              {{ getRuleDescription() }}
            </p>
          </div>
        </div>

        <!-- Existing Rules -->
        <div v-if="existingRules.length > 0">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            Règles existantes
          </h3>
          <div class="space-y-3">
            <div
              v-for="(existingRule, index) in existingRules"
              :key="index"
              class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-600 rounded-lg"
            >
              <div>
                <p class="text-sm text-gray-900 dark:text-white">
                  {{ getExistingRuleDescription(existingRule) }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                  {{ getRuleTypeLabel(existingRule.type) }}
                </p>
              </div>
              <Button
                  severity="danger"
                @click="removeRule(index)"
              >
                <TrashIcon class="w-4 h-4" />
              </Button>
            </div>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-end space-x-3 pt-6 mt-6 border-t border-gray-200 dark:border-gray-700">
        <Button @click="$emit('close')" severity="secondary">
          Annuler
        </Button>
        <Button
          v-if="isRuleComplete"
          @click="addRule"
          severity="primary"
        >
          Ajouter la règle
        </Button>
        <Button
          @click="saveRules"
          severity="success"
        >
          Enregistrer
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import {
  XMarkIcon,
  EyeIcon,
  EyeSlashIcon,
  ArrowRightIcon,
  StopIcon,
  ExclamationCircleIcon,
  ExclamationTriangleIcon,
  TrashIcon
} from '@heroicons/vue/24/outline';
import type { Question, Section, ConditionalRule } from '@/types/survey';

interface Props {
  question: Question;
  allQuestions: Question[];
  allSections: Section[];
}

interface Emits {
  close: [];
  update: [rules: ConditionalRule[]];
}

interface ConditionalLogicRule {
  type: string;
  sourceQuestionId: string;
  operator: string;
  value: any;
  action?: string;
  targetQuestionIds?: string[];
  targetSectionId?: string;
  endMessage?: string;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const selectedRuleType = ref('');
const rule = ref<ConditionalLogicRule>({
  type: '',
  sourceQuestionId: '',
  operator: '',
  value: '',
  targetQuestionIds: []
});
const existingRules = ref<ConditionalLogicRule[]>([]);

const ruleTypes = [
  {
    value: 'show_hide',
    title: 'Afficher/Masquer des questions',
    description: 'Affiche ou masque des questions selon la réponse',
    icon: EyeIcon
  },
  {
    value: 'jump_section',
    title: 'Aller à une section',
    description: 'Redirige vers une section spécifique',
    icon: ArrowRightIcon
  },
  {
    value: 'end_survey',
    title: 'Terminer le questionnaire',
    description: 'Termine le questionnaire immédiatement',
    icon: StopIcon
  },
  {
    value: 'set_required',
    title: 'Modifier le caractère obligatoire',
    description: 'Rend des questions obligatoires ou optionnelles',
    icon: ExclamationCircleIcon
  }
];

const availableOperators = computed(() => {
  if (!sourceQuestion.value) return [];

  const baseOperators = [
    { value: 'equals', label: 'est égal à' },
    { value: 'not_equals', label: 'n\'est pas égal à' }
  ];

  if (['single_choice', 'multiple_choice'].includes(sourceQuestion.value.type)) {
    return [
      ...baseOperators,
      { value: 'contains', label: 'contient' },
      { value: 'not_contains', label: 'ne contient pas' }
    ];
  }

  if (sourceQuestion.value.type === 'scale') {
    return [
      ...baseOperators,
      { value: 'greater_than', label: 'est supérieur à' },
      { value: 'less_than', label: 'est inférieur à' },
      { value: 'greater_equal', label: 'est supérieur ou égal à' },
      { value: 'less_equal', label: 'est inférieur ou égal à' }
    ];
  }

  if (['text_short', 'text_long'].includes(sourceQuestion.value.type)) {
    return [
      ...baseOperators,
      { value: 'contains', label: 'contient' },
      { value: 'not_contains', label: 'ne contient pas' },
      { value: 'starts_with', label: 'commence par' },
      { value: 'ends_with', label: 'se termine par' },
      { value: 'is_empty', label: 'est vide' },
      { value: 'is_not_empty', label: 'n\'est pas vide' }
    ];
  }

  return baseOperators;
});

const availableQuestions = computed(() =>
  props.allQuestions.filter(q => q.id !== props.question.id)
);

const availableSections = computed(() => props.allSections);

const sourceQuestion = computed(() =>
  props.allQuestions.find(q => q.id === rule.value.sourceQuestionId)
);

const targetQuestions = computed(() =>
  props.allQuestions.filter(q => q.id !== rule.value.sourceQuestionId && q.id !== props.question.id)
);

const isRuleComplete = computed(() => {
  const baseComplete = rule.value.sourceQuestionId && rule.value.operator && rule.value.value !== '';

  if (!baseComplete) return false;

  switch (selectedRuleType.value) {
    case 'show_hide':
    case 'set_required':
      return rule.value.action && rule.value.targetQuestionIds && rule.value.targetQuestionIds.length > 0;
    case 'jump_section':
      return rule.value.targetSectionId;
    case 'end_survey':
      return true;
    default:
      return false;
  }
});

function addRule() {
  if (!isRuleComplete.value) return;

  const newRule: ConditionalLogicRule = {
    ...rule.value,
    type: selectedRuleType.value
  };

  existingRules.value.push(newRule);

  // Reset form
  rule.value = {
    type: '',
    sourceQuestionId: '',
    operator: '',
    value: '',
    targetQuestionIds: []
  };
  selectedRuleType.value = '';
}

function removeRule(index: number) {
  existingRules.value.splice(index, 1);
}

function saveRules() {
  // Convert to the format expected by the Question type
  const convertedRules: ConditionalRule[] = existingRules.value.map(rule => ({
    dependsOn: rule.sourceQuestionId,
    operator: rule.operator as any,
    value: rule.value,
    action: rule.action,
    targetQuestionIds: rule.targetQuestionIds,
    targetSectionId: rule.targetSectionId,
    endMessage: rule.endMessage,
    type: rule.type
  }));

  emit('update', convertedRules);
}

function getRuleDescription(): string {
  const sourceQ = sourceQuestion.value;
  if (!sourceQ) return '';

  const operatorLabel = availableOperators.value.find(op => op.value === rule.value.operator)?.label || rule.value.operator;
  const baseDescription = `Si "${sourceQ.libelle}" ${operatorLabel} "${rule.value.value}"`;

  switch (selectedRuleType.value) {
    case 'show_hide':
      const actionLabel = rule.value.action === 'show' ? 'afficher' : 'masquer';
      const questionCount = rule.value.targetQuestionIds?.length || 0;
      return `${baseDescription}, alors ${actionLabel} ${questionCount} question${questionCount > 1 ? 's' : ''}`;

    case 'jump_section':
      const targetSection = availableSections.value.find(s => s.id === rule.value.targetSectionId);
      return `${baseDescription}, alors aller à la section "${targetSection?.titre || 'Inconnue'}"`;

    case 'end_survey':
      return `${baseDescription}, alors terminer le questionnaire`;

    case 'set_required':
      const requiredAction = rule.value.action === 'require' ? 'rendre obligatoires' : 'rendre optionnelles';
      const targetCount = rule.value.targetQuestionIds?.length || 0;
      return `${baseDescription}, alors ${requiredAction} ${targetCount} question${targetCount > 1 ? 's' : ''}`;

    default:
      return baseDescription;
  }
}

function getExistingRuleDescription(existingRule: ConditionalLogicRule): string {
  const sourceQ = props.allQuestions.find(q => q.id === existingRule.sourceQuestionId);
  if (!sourceQ) return 'Règle invalide';

  const operatorLabels: Record<string, string> = {
    equals: 'est égal à',
    not_equals: 'n\'est pas égal à',
    contains: 'contient',
    not_contains: 'ne contient pas',
    greater_than: 'est supérieur à',
    less_than: 'est inférieur à'
  };

  const operatorLabel = operatorLabels[existingRule.operator] || existingRule.operator;
  return `Si "${sourceQ.libelle}" ${operatorLabel} "${existingRule.value}"`;
}

function getRuleTypeLabel(type: string): string {
  const ruleType = ruleTypes.find(rt => rt.value === type);
  return ruleType?.title || type;
}
</script>
