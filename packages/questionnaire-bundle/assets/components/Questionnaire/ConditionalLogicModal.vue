<template>
  <Dialog
    :style="{ width: '92vw', maxWidth: '950px' }"
    :visible="true"
    :modal="true"
    :closable="true"
    :draggable="false"
    @update:visible="$emit('close')"
  >
    <template #header>
      <DialogHeader
        :icon="BoltIcon"
        tone="amber"
        title="Logique conditionnelle"
        :subtitle="`Question active : « ${question.label} »`"
      />
    </template>

    <div class="q-form">
      <!-- Mode Selector -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <!-- Mode 1: Trigger Mode -->
        <div
          :class="['q-choice', logicMode === 'trigger' && 'q-choice-active']"
          @click="setLogicMode('trigger')"
        >
          <div class="p-2 rounded-lg bg-amber-100 dark:bg-amber-950/70 text-amber-700 dark:text-amber-300 shrink-0">
            <BoltIcon class="w-5 h-5" />
          </div>
          <div>
            <span class="q-choice-title">⚡ Déclencheur</span>
            <span class="q-choice-desc block">
              Définir des actions qui se déclenchent selon la réponse à cette question (afficher/masquer d'autres questions, sauter une section...).
            </span>
          </div>
        </div>

        <!-- Mode 2: Dependency Mode -->
        <div
          :class="['q-choice', logicMode === 'dependency' && 'q-choice-active']"
          @click="setLogicMode('dependency')"
        >
          <div class="p-2 rounded-lg bg-blue-100 dark:bg-blue-950/70 text-blue-700 dark:text-blue-300 shrink-0">
            <LinkIcon class="w-5 h-5" />
          </div>
          <div>
            <span class="q-choice-title">🔗 Question conditionnée</span>
            <span class="q-choice-desc block">
              Conditionner l'affichage ou l'obligation de la question actuelle selon les réponses données à des questions précédentes.
            </span>
          </div>
        </div>
      </div>

      <!-- Logic Rule Form -->
      <FormSection :icon="AdjustmentsHorizontalIcon" tone="slate" title="Configuration de la règle">
        <!-- Rule Type Selection (Trigger Mode) -->
        <div v-if="logicMode === 'trigger'">
          <label class="q-label">Type d'action à déclencher</label>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5">
            <div
              v-for="ruleType in ruleTypes"
              :key="ruleType.value"
              :class="[
                'p-2.5 rounded-xl border text-xs cursor-pointer transition-all flex items-center gap-2',
                selectedRuleType === ruleType.value
                  ? 'bg-amber-50 dark:bg-amber-950/40 border-amber-400 dark:border-amber-700 text-amber-900 dark:text-amber-200 font-semibold shadow-2xs'
                  : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700/80 text-slate-700 dark:text-slate-300 hover:border-slate-300'
              ]"
              @click="selectedRuleType = ruleType.value"
            >
              <component :is="ruleType.icon" class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0" />
              <span class="truncate">{{ ruleType.title }}</span>
            </div>
          </div>
        </div>

        <div class="space-y-3 pt-2">
          <!-- Logical operator connector (AND / OR) -->
          <div v-if="rule.conditions.length > 1" class="flex items-center gap-3 p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/80">
            <span class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase">Connecteur logique :</span>
            <div class="q-tabs !p-0.5 max-w-xs">
              <button
                type="button"
                :class="['q-tab !py-1 text-[11px]', rule.logicalOperator === 'AND' && 'q-tab-active']"
                @click="rule.logicalOperator = 'AND'"
              >
                ET (Toutes)
              </button>
              <button
                type="button"
                :class="['q-tab !py-1 text-[11px]', rule.logicalOperator === 'OR' && 'q-tab-active']"
                @click="rule.logicalOperator = 'OR'"
              >
                OU (Au moins une)
              </button>
            </div>
          </div>

          <div class="space-y-2">
            <label class="q-label">Lorsque les conditions suivantes sont remplies :</label>

            <!-- Condition Rows List -->
            <div class="space-y-2">
              <div
                v-for="(cond, idx) in rule.conditions"
                :key="idx"
                class="flex flex-col sm:flex-row gap-2 items-stretch sm:items-center bg-white dark:bg-slate-800 p-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80"
              >
                <span class="px-2 py-1 rounded bg-slate-100 dark:bg-slate-700 text-[10px] font-bold text-slate-600 dark:text-slate-300 uppercase shrink-0 text-center">
                  {{ idx === 0 ? 'Si' : rule.logicalOperator === 'AND' ? 'ET' : 'OU' }}
                </span>

                <!-- Source Question Dropdown -->
                <div class="flex-1 min-w-0">
                  <select
                    v-model="cond.sourceQuestionId"
                    :disabled="logicMode === 'trigger' && idx === 0"
                    class="q-input q-input-sm"
                    @change="onSourceQuestionChange(cond)"
                  >
                    <option value="">Sélectionnez la question...</option>
                    <option
                      v-for="q in (logicMode === 'trigger' ? allQuestions : otherQuestions)"
                      :key="q.uuid || q.id"
                      :value="q.uuid || q.id"
                    >
                      {{ q.label }}
                    </option>
                  </select>
                </div>

                <!-- Operator Dropdown -->
                <div class="w-full sm:w-44">
                  <select
                    v-model="cond.operator"
                    :disabled="!cond.sourceQuestionId"
                    class="q-input q-input-sm"
                  >
                    <option value="">Condition...</option>
                    <option
                      v-for="op in getOperatorsForQuestion(cond.sourceQuestionId)"
                      :key="op.value"
                      :value="op.value"
                    >
                      {{ op.label }}
                    </option>
                  </select>
                </div>

                <!-- Answer Value input -->
                <div class="w-full sm:w-48" v-if="cond.operator && !['is_empty', 'is_not_empty'].includes(cond.operator)">
                  <select
                    v-if="getQuestionById(cond.sourceQuestionId) && ['single_choice', 'multiple_choice', 'ranking'].includes(getQuestionById(cond.sourceQuestionId).typeQuestion)"
                    v-model="cond.value"
                    class="q-input q-input-sm"
                  >
                    <option value="">Option...</option>
                    <option
                      v-for="option in getQuestionById(cond.sourceQuestionId).choices"
                      :key="option.id"
                      :value="option.text"
                    >
                      {{ option.text }}
                    </option>
                  </select>

                  <input
                    v-else-if="getQuestionById(cond.sourceQuestionId)?.typeQuestion === 'scale'"
                    v-model.number="cond.value"
                    type="number"
                    :min="getQuestionById(cond.sourceQuestionId).opt?.min || getQuestionById(cond.sourceQuestionId).validation?.min || 1"
                    :max="getQuestionById(cond.sourceQuestionId).opt?.max || getQuestionById(cond.sourceQuestionId).validation?.max || 10"
                    class="q-input q-input-sm"
                    placeholder="Valeur"
                  />

                  <input
                    v-else
                    v-model="cond.value"
                    type="text"
                    class="q-input q-input-sm"
                    placeholder="Valeur à comparer"
                  />
                </div>

                <!-- Delete condition row button -->
                <Button
                  v-if="rule.conditions.length > 1"
                  severity="danger"
                  text
                  rounded
                  size="small"
                  icon="pi pi-times"
                  aria-label="Supprimer la condition"
                  @click="removeConditionRow(idx)"
                />
              </div>
            </div>

            <!-- Add new condition row button -->
            <Button
              size="small"
              severity="secondary"
              outlined
              icon="pi pi-plus"
              label="Ajouter un critère"
              @click="addConditionRow"
            />
          </div>

          <!-- Targets / Actions configuration -->
          <div v-if="hasValidConditions" class="pt-3 border-t border-slate-200 dark:border-slate-700/80 space-y-3">
            <label class="q-label !mb-0">Action à effectuer :</label>

            <!-- Show/Hide other questions (Trigger Mode) -->
            <div v-if="logicMode === 'trigger' && selectedRuleType === 'show_hide'" class="space-y-3">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <label :class="['q-choice', rule.action === 'show' && 'q-choice-active']">
                  <input v-model="rule.action" type="radio" value="show" class="q-check mt-0.5" />
                  <div>
                    <span class="q-choice-title">Afficher les questions cibles</span>
                    <span class="q-choice-desc block">Masquées par défaut au démarrage du questionnaire</span>
                  </div>
                </label>

                <label :class="['q-choice', rule.action === 'hide' && 'q-choice-active']">
                  <input v-model="rule.action" type="radio" value="hide" class="q-check mt-0.5" />
                  <div>
                    <span class="q-choice-title">Masquer les questions cibles</span>
                    <span class="q-choice-desc block">Visibles par défaut au démarrage du questionnaire</span>
                  </div>
                </label>
              </div>

              <div class="border border-slate-200 dark:border-slate-700/80 bg-white dark:bg-slate-800 rounded-xl p-3 max-h-40 overflow-y-auto space-y-1.5">
                <label v-for="q in otherQuestions" :key="q.uuid || q.id" class="flex items-center gap-2 cursor-pointer p-1 rounded hover:bg-slate-50 dark:hover:bg-slate-700/50">
                  <input v-model="rule.targetQuestionIds" type="checkbox" :value="q.uuid || q.id" class="q-check !w-3.5 !h-3.5" />
                  <span class="text-xs text-slate-800 dark:text-slate-200">{{ q.label }}</span>
                </label>
              </div>
            </div>

            <!-- Jump Section (Trigger Mode) -->
            <div v-else-if="logicMode === 'trigger' && selectedRuleType === 'jump_section'">
              <FormField label="Section destination" for="jump-section-select">
                <select id="jump-section-select" v-model="rule.targetSectionId" class="q-input">
                  <option value="">Sélectionnez la section destination...</option>
                  <option v-for="s in availableSections" :key="s.uuid || s.id" :value="s.uuid || s.id">{{ s.title }}</option>
                </select>
              </FormField>
            </div>

            <!-- End Survey (Trigger Mode) -->
            <div v-else-if="logicMode === 'trigger' && selectedRuleType === 'end_survey'" class="space-y-2">
              <div class="q-callout q-callout-warning">
                <ExclamationCircleIcon class="w-4 h-4 shrink-0 mt-0.5" />
                <p>Cette action terminera immédiatement le questionnaire pour le participant.</p>
              </div>
              <textarea v-model="rule.endMessage" class="q-input" rows="2" placeholder="Message de fin personnalisé (optionnel)" />
            </div>

            <!-- Set Required (Trigger Mode) -->
            <div v-else-if="logicMode === 'trigger' && selectedRuleType === 'set_required'" class="space-y-3">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <label :class="['q-choice', rule.action === 'require' && 'q-choice-active']">
                  <input v-model="rule.action" type="radio" value="require" class="q-check mt-0.5" />
                  <div>
                    <span class="q-choice-title">Rendre obligatoire</span>
                  </div>
                </label>
                <label :class="['q-choice', rule.action === 'optional' && 'q-choice-active']">
                  <input v-model="rule.action" type="radio" value="optional" class="q-check mt-0.5" />
                  <div>
                    <span class="q-choice-title">Rendre facultatif</span>
                  </div>
                </label>
              </div>

              <div class="border border-slate-200 dark:border-slate-700/80 bg-white dark:bg-slate-800 rounded-xl p-3 max-h-40 overflow-y-auto space-y-1.5">
                <label v-for="q in otherQuestions" :key="q.uuid || q.id" class="flex items-center gap-2 cursor-pointer p-1 rounded hover:bg-slate-50 dark:hover:bg-slate-700/50">
                  <input v-model="rule.targetQuestionIds" type="checkbox" :value="q.uuid || q.id" class="q-check !w-3.5 !h-3.5" />
                  <span class="text-xs text-slate-800 dark:text-slate-200">{{ q.label }}</span>
                </label>
              </div>
            </div>

            <!-- Actions list (Dependency Mode) -->
            <div v-else-if="logicMode === 'dependency'" class="grid grid-cols-1 sm:grid-cols-3 gap-2">
              <label :class="['q-choice', rule.action === 'show' && 'q-choice-active']">
                <input v-model="rule.action" type="radio" value="show" class="q-check mt-0.5" />
                <div>
                  <span class="q-choice-title">Afficher la question</span>
                  <span class="q-choice-desc block">Masquée par défaut</span>
                </div>
              </label>

              <label :class="['q-choice', rule.action === 'hide' && 'q-choice-active']">
                <input v-model="rule.action" type="radio" value="hide" class="q-check mt-0.5" />
                <div>
                  <span class="q-choice-title">Masquer la question</span>
                  <span class="q-choice-desc block">Visible par défaut</span>
                </div>
              </label>

              <label :class="['q-choice', rule.action === 'require' && 'q-choice-active']">
                <input v-model="rule.action" type="radio" value="require" class="q-check mt-0.5" />
                <div>
                  <span class="q-choice-title">Rendre obligatoire</span>
                  <span class="q-choice-desc block">Facultative par défaut</span>
                </div>
              </label>
            </div>
          </div>
        </div>
      </FormSection>

      <!-- Live Preview of Current Rule -->
      <div v-if="isRuleComplete" class="q-callout q-callout-success">
        <CheckCircleIcon class="w-4 h-4 shrink-0 mt-0.5" />
        <div>
          <p class="font-bold text-xs">Aperçu de la règle en cours</p>
          <p class="mt-0.5">{{ getRulePreviewText() }}</p>
        </div>
      </div>

      <!-- Existing Rules Summaries -->
      <div v-if="outgoingRules.length > 0 || incomingRules.length > 0" class="space-y-4 pt-2 border-t border-slate-200 dark:border-slate-700/80">
        <!-- Outgoing Rules (Triggered by active question) -->
        <div v-if="outgoingRules.length > 0" class="space-y-2">
          <div class="flex items-center gap-2">
            <h3 class="q-label !mb-0">⚡ Règles déclenchées par cette question</h3>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200">
              {{ outgoingRules.length }}
            </span>
          </div>
          <div class="space-y-2">
            <div
              v-for="(r, index) in outgoingRules"
              :key="index"
              class="flex items-center justify-between gap-3 p-3 border border-amber-200 dark:border-amber-800/50 bg-amber-50/30 dark:bg-amber-950/20 rounded-xl"
            >
              <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold text-slate-900 dark:text-white leading-relaxed">
                  {{ getRuleDescriptionText(r) }}
                </p>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                  Type : {{ getRuleTypeLabel(r.type) }}
                </p>
              </div>
              <Button
                severity="danger"
                text
                rounded
                size="small"
                icon="pi pi-trash"
                aria-label="Supprimer la règle"
                @click="removeRule(r.originalIndex)"
              />
            </div>
          </div>
        </div>

        <!-- Incoming Rules (Dependencies on prior questions) -->
        <div v-if="incomingRules.length > 0" class="space-y-2">
          <div class="flex items-center gap-2">
            <h3 class="q-label !mb-0">🔗 Dépendances (Question conditionnée par d'autres)</h3>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-200">
              {{ incomingRules.length }}
            </span>
          </div>
          <div class="space-y-2">
            <div
              v-for="(inc, index) in incomingRules"
              :key="index"
              class="flex items-center justify-between gap-3 p-3 border border-blue-200 dark:border-blue-800/50 bg-blue-50/30 dark:bg-blue-950/20 rounded-xl"
            >
              <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold text-slate-900 dark:text-white leading-relaxed">
                  {{ getRuleDescriptionText(inc.rule) }}
                </p>
              </div>
              <Button
                severity="danger"
                text
                rounded
                size="small"
                icon="pi pi-trash"
                aria-label="Supprimer la dépendance"
                @click="removeRule(inc.originalIndex)"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="q-actions">
        <Button severity="secondary" outlined label="Annuler" @click="$emit('close')" />
        <Button
          v-if="isRuleComplete"
          severity="secondary"
          icon="pi pi-plus"
          label="Ajouter la règle"
          @click="addRule"
        />
        <Button
          icon="pi pi-check"
          label="Enregistrer et fermer"
          @click="saveRules"
        />
      </div>
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import {
  EyeIcon,
  ArrowRightIcon,
  StopIcon,
  ExclamationCircleIcon,
  BoltIcon,
  LinkIcon,
  AdjustmentsHorizontalIcon,
  CheckCircleIcon
} from '@heroicons/vue/24/outline';
import type { Question, Section, ConditionalRule } from '@/types/survey';
import { DialogHeader, FormField, FormSection } from '../Form';

interface Props {
  question: Question;
  allQuestions: Question[];
  allSections: Section[];
}

interface Emits {
  close: [];
  update: [rules: ConditionalRule[]];
}

interface ConditionDraft {
  sourceQuestionId: string;
  operator: string;
  value: any;
}

interface ConditionalLogicRule {
  type: string;
  logicalOperator: 'AND' | 'OR';
  conditions: ConditionDraft[];
  action?: string;
  targetQuestionIds?: string[];
  targetSectionId?: string;
  endMessage?: string;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const currentQuestionId = (props.question.uuid || props.question.id) as string;
const logicMode = ref<'trigger' | 'dependency'>('trigger');
const selectedRuleType = ref('show_hide');

const rule = ref<ConditionalLogicRule>({
  type: 'show_hide',
  logicalOperator: 'AND',
  conditions: [],
  action: 'show',
  targetQuestionIds: []
});

const existingRules = ref<ConditionalLogicRule[]>([]);

onMounted(() => {
  if (props.question.conditionalRules && props.question.conditionalRules.length > 0) {
    existingRules.value = props.question.conditionalRules.map(r => {
      let conditions: ConditionDraft[] = [];
      if (r.conditions && r.conditions.length > 0) {
        conditions = r.conditions.map(c => ({
          sourceQuestionId: c.dependsOn,
          operator: c.operator,
          value: c.value
        }));
      } else if (r.dependsOn) {
        conditions = [{
          sourceQuestionId: r.dependsOn,
          operator: r.operator || 'equals',
          value: r.value
        }];
      }

      return {
        type: r.type || 'show_hide',
        logicalOperator: r.logicalOperator || 'AND',
        conditions,
        action: r.action || 'show',
        targetQuestionIds: r.targetQuestionIds || [],
        targetSectionId: r.targetSectionId,
        endMessage: r.endMessage
      };
    });
  }
  setLogicMode('trigger');
});

function setLogicMode(mode: 'trigger' | 'dependency') {
  logicMode.value = mode;
  selectedRuleType.value = 'show_hide';

  if (mode === 'trigger') {
    rule.value = {
      type: 'show_hide',
      logicalOperator: 'AND',
      conditions: [{ sourceQuestionId: currentQuestionId, operator: '', value: '' }],
      action: 'show',
      targetQuestionIds: []
    };
  } else {
    rule.value = {
      type: 'show_hide',
      logicalOperator: 'AND',
      conditions: [{ sourceQuestionId: '', operator: '', value: '' }],
      action: 'show',
      targetQuestionIds: [currentQuestionId]
    };
  }
}

function addConditionRow() {
  const defaultSource = logicMode.value === 'trigger' ? currentQuestionId : '';
  rule.value.conditions.push({
    sourceQuestionId: defaultSource,
    operator: '',
    value: ''
  });
}

function removeConditionRow(index: number) {
  if (rule.value.conditions.length > 1) {
    rule.value.conditions.splice(index, 1);
  }
}

function onSourceQuestionChange(cond: ConditionDraft) {
  cond.operator = '';
  cond.value = '';
}

const ruleTypes = [
  { value: 'show_hide', title: 'Afficher / Masquer', icon: EyeIcon },
  { value: 'jump_section', title: 'Aller à une section', icon: ArrowRightIcon },
  { value: 'end_survey', title: 'Terminer', icon: StopIcon },
  { value: 'set_required', title: 'Obligation', icon: ExclamationCircleIcon }
];

const otherQuestions = computed(() => {
  return props.allQuestions.filter(q => {
    const qId = q.uuid || q.id;
    return String(qId) !== String(currentQuestionId);
  });
});

const availableSections = computed(() => props.allSections);

function getQuestionById(id: string) {
  return props.allQuestions.find(q => String(q.uuid || q.id) === String(id));
}

function buildOperatorsForType(typeQuestion: string) {
  const baseOperators = [
    { value: 'equals', label: 'est égal à' },
    { value: 'not_equals', label: 'n\'est pas égal à' }
  ];

  if (['single_choice', 'multiple_choice', 'ranking'].includes(typeQuestion)) {
    return [
      ...baseOperators,
      { value: 'contains', label: 'contient' },
      { value: 'not_contains', label: 'ne contient pas' }
    ];
  }

  if (typeQuestion === 'scale') {
    return [
      ...baseOperators,
      { value: 'greater_than', label: 'est supérieur à' },
      { value: 'less_than', label: 'est inférieur à' },
      { value: 'greater_equal', label: 'est supérieur ou égal à' },
      { value: 'less_equal', label: 'est inférieur ou égal à' }
    ];
  }

  if (['text_short', 'text_long'].includes(typeQuestion)) {
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
}

function getOperatorsForQuestion(questionId: string) {
  const q = getQuestionById(questionId);
  if (!q) return [];
  return buildOperatorsForType(q.typeQuestion);
}

const hasValidConditions = computed(() => {
  if (rule.value.conditions.length === 0) return false;
  return rule.value.conditions.every(c => {
    if (!c.sourceQuestionId || !c.operator) return false;
    const isNoVal = ['is_empty', 'is_not_empty'].includes(c.operator);
    if (!isNoVal && (c.value === '' || c.value === null || c.value === undefined)) return false;
    return true;
  });
});

const isRuleComplete = computed(() => {
  if (!hasValidConditions.value) return false;

  if (logicMode.value === 'trigger') {
    switch (selectedRuleType.value) {
      case 'show_hide':
      case 'set_required':
        return !!rule.value.action && !!rule.value.targetQuestionIds && rule.value.targetQuestionIds.length > 0;
      case 'jump_section':
        return !!rule.value.targetSectionId;
      case 'end_survey':
        return true;
      default:
        return false;
    }
  } else {
    return !!rule.value.action;
  }
});

function addRule() {
  if (!isRuleComplete.value) return;

  if (logicMode.value === 'trigger') {
    rule.value.type = selectedRuleType.value;
  } else {
    rule.value.type = rule.value.action === 'require' ? 'set_required' : 'show_hide';
    rule.value.targetQuestionIds = [currentQuestionId];
  }

  existingRules.value.push(JSON.parse(JSON.stringify(rule.value)));
  setLogicMode(logicMode.value);
}

const outgoingRules = computed(() => {
  return existingRules.value
    .map((r, index) => ({ ...r, originalIndex: index }))
    .filter(r => r.conditions.some(c => String(c.sourceQuestionId) === String(currentQuestionId)));
});

const incomingRules = computed(() => {
  return existingRules.value
    .map((r, index) => ({ ...r, originalIndex: index }))
    .filter(r => !r.conditions.some(c => String(c.sourceQuestionId) === String(currentQuestionId)));
});

function removeRule(originalIndex: number) {
  existingRules.value.splice(originalIndex, 1);
}

function saveRules() {
  if (isRuleComplete.value) {
    if (logicMode.value === 'trigger') {
      rule.value.type = selectedRuleType.value;
    } else {
      rule.value.type = rule.value.action === 'require' ? 'set_required' : 'show_hide';
      rule.value.targetQuestionIds = [currentQuestionId];
    }
    existingRules.value.push(JSON.parse(JSON.stringify(rule.value)));
  }

  const convertedRules: ConditionalRule[] = existingRules.value.map(r => {
    const firstCond = r.conditions[0];
    return {
      dependsOn: firstCond?.sourceQuestionId,
      operator: firstCond?.operator as any,
      value: firstCond?.value,
      logicalOperator: r.logicalOperator,
      conditions: r.conditions.map(c => ({
        dependsOn: c.sourceQuestionId,
        operator: c.operator,
        value: c.value
      })),
      action: r.action,
      targetQuestionIds: r.targetQuestionIds,
      targetSectionId: r.targetSectionId,
      endMessage: r.endMessage,
      type: r.type
    };
  });

  emit('update', convertedRules);
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
      const q = props.allQuestions.find(item => String(item.uuid || item.id) === String(id));
      return q ? `"${q.label}"` : null;
    })
    .filter(Boolean);

  if (names.length === 0) return `${targetIds.length} question(s) cible(s)`;
  if (names.length === 1) return `la question ${names[0]}`;
  return `les questions (${names.join(', ')})`;
}

function getConditionsExplanation(conditionsList: ConditionDraft[], op: 'AND' | 'OR'): string {
  const condTexts = conditionsList.map(c => {
    const q = getQuestionById(c.sourceQuestionId);
    const qLabel = q ? `"${q.label}"` : 'Question inconnue';
    const opLabel = getOperatorLabel(c.operator);
    const isNoVal = ['is_empty', 'is_not_empty'].includes(c.operator);
    return isNoVal
      ? `${qLabel} ${opLabel}`
      : `${qLabel} ${opLabel} "${c.value}"`;
  });

  const connector = op === 'AND' ? ' ET ' : ' OU ';
  return condTexts.join(connector);
}

function getRulePreviewText(): string {
  const conditionsText = getConditionsExplanation(rule.value.conditions, rule.value.logicalOperator);
  const prefix = `Si (${conditionsText})`;

  if (logicMode.value === 'trigger') {
    switch (selectedRuleType.value) {
      case 'show_hide': {
        const act = rule.value.action === 'show' ? 'afficher' : 'masquer';
        const targetsText = getTargetQuestionsNames(rule.value.targetQuestionIds);
        return `${prefix}, alors ${act} ${targetsText}.`;
      }
      case 'jump_section': {
        const sec = availableSections.value.find(s => String(s.uuid || s.id) === String(rule.value.targetSectionId));
        return `${prefix}, alors aller à la section "${sec?.title || 'Inconnue'}".`;
      }
      case 'end_survey':
        return `${prefix}, alors terminer le questionnaire.`;
      case 'set_required': {
        const act = rule.value.action === 'require' ? 'rendre obligatoire' : 'rendre facultatif';
        const targetsText = getTargetQuestionsNames(rule.value.targetQuestionIds);
        return `${prefix}, alors ${act} ${targetsText}.`;
      }
      default:
        return prefix;
    }
  } else {
    const actText = rule.value.action === 'show' ? 'afficher' : rule.value.action === 'hide' ? 'masquer' : 'rendre obligatoire';
    return `${prefix}, alors ${actText} cette question "${props.question.label}".`;
  }
}

function getRuleDescriptionText(r: ConditionalLogicRule): string {
  const conditionsText = getConditionsExplanation(r.conditions, r.logicalOperator);
  const prefix = `Si (${conditionsText})`;

  switch (r.type) {
    case 'show_hide': {
      const act = r.action === 'show' ? 'afficher' : 'masquer';
      const targetsText = getTargetQuestionsNames(r.targetQuestionIds);
      return `${prefix}, alors ${act} ${targetsText}.`;
    }
    case 'jump_section': {
      const sec = availableSections.value.find(s => String(s.uuid || s.id) === String(r.targetSectionId));
      return `${prefix}, alors aller à la section "${sec?.title || 'Inconnue'}".`;
    }
    case 'end_survey':
      return `${prefix}, alors terminer le questionnaire.`;
    case 'set_required': {
      const act = r.action === 'require' ? 'rendre obligatoire' : 'rendre facultatif';
      const targetsText = getTargetQuestionsNames(r.targetQuestionIds);
      return `${prefix}, alors ${act} ${targetsText}.`;
    }
    default:
      return prefix;
  }
}

function getRuleTypeLabel(type: string): string {
  const rt = ruleTypes.find(t => t.value === type);
  return rt?.title || type;
}
</script>
