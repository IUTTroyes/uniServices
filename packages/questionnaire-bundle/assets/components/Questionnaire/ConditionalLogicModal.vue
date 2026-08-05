<template>
  <div class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[100]" @click="$emit('close')">
    <div
      class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-4xl mx-4 max-h-[90vh] overflow-y-auto shadow-2xl"
      @click.stop
    >
      <div class="flex items-center justify-between mb-4 border-b border-gray-100 dark:border-gray-700 pb-3">
        <div>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <span>⚙️ Logique conditionnelle</span>
          </h2>
          <p class="text-sm text-gray-550 dark:text-gray-400 mt-1">
            Question active : <span class="font-medium text-gray-805 dark:text-gray-250">"{{ question.label }}"</span>
          </p>
        </div>
        <button
          @click="$emit('close')"
          class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors border-0 text-gray-405 hover:text-gray-600"
        >
          <XMarkIcon class="w-5 h-5" />
        </button>
      </div>

      <!-- Mode Selector -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <!-- Mode 1: Trigger Mode -->
        <div
          :class="[
            'p-4 border-2 rounded-xl cursor-pointer transition-all',
            logicMode === 'trigger'
              ? 'border-amber-500 bg-amber-50/70 dark:bg-amber-950/40 shadow-sm'
              : 'border-gray-200 dark:border-gray-700 hover:border-amber-300 dark:hover:border-amber-700'
          ]"
          @click="setLogicMode('trigger')"
        >
          <div class="flex items-start space-x-3">
            <div class="p-2 rounded-lg bg-amber-100 dark:bg-amber-900 text-amber-600 dark:text-amber-300 flex-shrink-0">
              <BoltIcon class="w-6 h-6" />
            </div>
            <div>
              <h3 class="font-semibold text-gray-900 dark:text-white text-base flex items-center gap-2">
                ⚡ Déclencheur
              </h3>
              <p class="text-xs text-gray-650 dark:text-gray-400 mt-1 leading-relaxed">
                Définir des actions qui se déclenchent selon la réponse à cette question (afficher/masquer d'autres questions, sauter une section, etc.).
              </p>
            </div>
          </div>
        </div>

        <!-- Mode 2: Dependency Mode -->
        <div
          :class="[
            'p-4 border-2 rounded-xl cursor-pointer transition-all',
            logicMode === 'dependency'
              ? 'border-blue-500 bg-blue-50/70 dark:bg-blue-950/40 shadow-sm'
              : 'border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-700'
          ]"
          @click="setLogicMode('dependency')"
        >
          <div class="flex items-start space-x-3">
            <div class="p-2 rounded-lg bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 flex-shrink-0">
              <LinkIcon class="w-6 h-6" />
            </div>
            <div>
              <h3 class="font-semibold text-gray-900 dark:text-white text-base flex items-center gap-2">
                🔗 Question conditionnée
              </h3>
              <p class="text-xs text-gray-650 dark:text-gray-400 mt-1 leading-relaxed">
                Conditionner l'affichage ou l'obligation de la question actuelle selon les réponses données à d'autres questions précédentes.
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Logic Rule Form -->
      <div class="space-y-6 bg-gray-50/50 dark:bg-gray-800/40 p-5 rounded-xl border border-gray-200 dark:border-gray-700">
        <!-- Rule Type Selection -->
        <div v-if="logicMode === 'trigger'">
          <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
            Quel type d'action déclencher ?
          </label>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
            <div
              v-for="ruleType in ruleTypes"
              :key="ruleType.value"
              :class="[
                'p-3 border-2 rounded-lg cursor-pointer transition-all',
                selectedRuleType === ruleType.value
                  ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/40'
                  : 'border-gray-200 dark:border-gray-700 hover:border-gray-350 dark:hover:border-gray-600'
              ]"
              @click="selectedRuleType = ruleType.value"
            >
              <div class="flex items-center space-x-2">
                <component :is="ruleType.icon" class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0" />
                <h4 class="text-xs font-semibold text-gray-900 dark:text-white">{{ ruleType.title }}</h4>
              </div>
            </div>
          </div>
        </div>

        <div class="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-4">
          <!-- Logical operator connector (AND / OR) -->
          <div v-if="rule.conditions.length > 1" class="flex items-center space-x-3 bg-indigo-50/50 dark:bg-indigo-950/20 p-3 rounded-lg border border-indigo-100 dark:border-indigo-900">
            <span class="text-xs font-bold text-indigo-900 dark:text-indigo-200 uppercase">Connecteur logique :</span>
            <div class="flex bg-gray-200 dark:bg-gray-750 p-0.5 rounded-lg">
              <button
                type="button"
                @click="rule.logicalOperator = 'AND'"
                :class="['px-3 py-1 text-xs font-semibold rounded-md transition-colors', rule.logicalOperator === 'AND' ? 'bg-white dark:bg-gray-600 shadow text-indigo-700 dark:text-indigo-150' : 'text-gray-600 dark:text-gray-400']"
              >
                ET (Toutes les conditions)
              </button>
              <button
                type="button"
                @click="rule.logicalOperator = 'OR'"
                :class="['px-3 py-1 text-xs font-semibold rounded-md transition-colors', rule.logicalOperator === 'OR' ? 'bg-white dark:bg-gray-600 shadow text-indigo-700 dark:text-indigo-150' : 'text-gray-600 dark:text-gray-400']"
              >
                OU (Au moins une condition)
              </button>
            </div>
          </div>

          <div class="space-y-3">
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
              Lorsque les conditions suivantes sont remplies :
            </label>

            <!-- Condition Rows List -->
            <div class="space-y-3">
              <div
                v-for="(cond, idx) in rule.conditions"
                :key="idx"
                class="flex flex-col md:flex-row gap-3 items-stretch md:items-center bg-white dark:bg-gray-800 p-3 rounded-xl border border-gray-200 dark:border-gray-700 relative"
              >
                <!-- Condition index / logic connector label -->
                <div class="absolute -left-2 top-3 md:top-auto bg-gray-150 dark:bg-gray-700 px-2 py-0.5 rounded text-2xs font-bold text-gray-600 dark:text-gray-400 uppercase">
                  {{ idx === 0 ? 'Si' : rule.logicalOperator === 'AND' ? 'ET' : 'OU' }}
                </div>

                <!-- Source Question Dropdown -->
                <div class="flex-1 min-w-0 pl-3 md:pl-0">
                  <select
                    v-model="cond.sourceQuestionId"
                    :disabled="logicMode === 'trigger' && idx === 0"
                    class="input-field w-full text-xs"
                    @change="onSourceQuestionChange(cond)"
                  >
                    <option value="">Sélectionnez la question</option>
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
                <div class="w-full md:w-48">
                  <select v-model="cond.operator" :disabled="!cond.sourceQuestionId" class="input-field w-full text-xs">
                    <option value="">Sélectionnez une condition</option>
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
                <div class="w-full md:w-56" v-if="cond.operator && !['is_empty', 'is_not_empty'].includes(cond.operator)">
                  <!-- Choice / Choice List options -->
                  <select
                    v-if="getQuestionById(cond.sourceQuestionId) && ['single_choice', 'multiple_choice', 'ranking'].includes(getQuestionById(cond.sourceQuestionId).typeQuestion)"
                    v-model="cond.value"
                    class="input-field w-full text-xs"
                  >
                    <option value="">Sélectionnez une option</option>
                    <option
                      v-for="option in getQuestionById(cond.sourceQuestionId).choices"
                      :key="option.id"
                      :value="option.text"
                    >
                      {{ option.text }}
                    </option>
                  </select>

                  <!-- Scale values -->
                  <input
                    v-else-if="getQuestionById(cond.sourceQuestionId)?.typeQuestion === 'scale'"
                    v-model.number="cond.value"
                    type="number"
                    :min="getQuestionById(cond.sourceQuestionId).opt?.min || getQuestionById(cond.sourceQuestionId).validation?.min || 1"
                    :max="getQuestionById(cond.sourceQuestionId).opt?.max || getQuestionById(cond.sourceQuestionId).validation?.max || 10"
                    class="input-field w-full text-xs"
                    placeholder="Valeur"
                  />

                  <!-- Text input -->
                  <input
                    v-else
                    v-model="cond.value"
                    type="text"
                    class="input-field w-full text-xs"
                    placeholder="Valeur à comparer"
                  />
                </div>

                <!-- Delete condition row button -->
                <button
                  type="button"
                  v-if="rule.conditions.length > 1"
                  @click="removeConditionRow(idx)"
                  class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-950/20 rounded-lg transition-colors border-0 self-end md:self-auto"
                >
                  <TrashIcon class="w-4 h-4" />
                </button>
              </div>
            </div>

            <!-- Add new condition row button -->
            <button
              type="button"
              @click="addConditionRow"
              class="flex items-center space-x-2 text-xs font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-700 border-0 bg-transparent cursor-pointer mt-2"
            >
              <span>➕ Ajouter un critère</span>
            </button>
          </div>

          <!-- Targets / Actions configuration -->
          <div v-if="hasValidConditions" class="pt-4 border-t border-gray-200 dark:border-gray-700">
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
              Alors effectuer l'action suivante :
            </label>

            <!-- Show/Hide other questions (Trigger Mode) -->
            <div v-if="logicMode === 'trigger' && selectedRuleType === 'show_hide'" class="space-y-3">
              <div class="flex flex-col space-y-2 mb-3">
                <label class="flex items-start cursor-pointer p-2 rounded-lg hover:bg-gray-150 dark:hover:bg-gray-700/50">
                  <input v-model="rule.action" type="radio" value="show" class="text-primary-600 focus:ring-primary-500 mt-0.5" />
                  <div class="ml-2">
                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">Afficher les questions cibles</span>
                    <p class="text-2xs text-gray-500 dark:text-gray-400 mt-0.5">💡 Les questions cibles seront masquées par défaut au démarrage du questionnaire.</p>
                  </div>
                </label>

                <label class="flex items-start cursor-pointer p-2 rounded-lg hover:bg-gray-150 dark:hover:bg-gray-700/50">
                  <input v-model="rule.action" type="radio" value="hide" class="text-primary-600 focus:ring-primary-500 mt-0.5" />
                  <div class="ml-2">
                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">Masquer les questions cibles</span>
                    <p class="text-2xs text-gray-500 dark:text-gray-400 mt-0.5">💡 Les questions cibles seront affichées par défaut au démarrage du questionnaire.</p>
                  </div>
                </label>
              </div>

              <div class="border border-gray-200 dark:border-gray-650 bg-white dark:bg-gray-800 rounded-lg p-3 max-h-40 overflow-y-auto space-y-2">
                <label v-for="q in otherQuestions" :key="q.uuid || q.id" class="flex items-center space-x-2 cursor-pointer">
                  <input v-model="rule.targetQuestionIds" type="checkbox" :value="q.uuid || q.id" class="text-primary-600 focus:ring-primary-500 rounded" />
                  <span class="text-xs text-gray-700 dark:text-gray-300">{{ q.label }}</span>
                </label>
              </div>
            </div>

            <!-- Jump Section (Trigger Mode) -->
            <div v-else-if="logicMode === 'trigger' && selectedRuleType === 'jump_section'">
              <select v-model="rule.targetSectionId" class="input-field">
                <option value="">Sélectionnez la section destination</option>
                <option v-for="s in availableSections" :key="s.uuid || s.id" :value="s.uuid || s.id">{{ s.title }}</option>
              </select>
            </div>

            <!-- End Survey (Trigger Mode) -->
            <div v-else-if="logicMode === 'trigger' && selectedRuleType === 'end_survey'" class="space-y-3">
              <p class="text-xs text-yellow-800 dark:text-yellow-250 bg-yellow-50 dark:bg-yellow-950/20 p-3 rounded-lg border border-yellow-200 dark:border-yellow-900">
                ⚠️ Cette action terminera prématurément le questionnaire pour le répondant.
              </p>
              <textarea v-model="rule.endMessage" class="w-full input-field" rows="2" placeholder="Message de fin personnalisé (optionnel)" />
            </div>

            <!-- Set Required (Trigger Mode) -->
            <div v-else-if="logicMode === 'trigger' && selectedRuleType === 'set_required'" class="space-y-3">
              <div class="flex flex-col space-y-2 mb-3">
                <label class="flex items-start cursor-pointer p-2 rounded-lg hover:bg-gray-150 dark:hover:bg-gray-700/50">
                  <input v-model="rule.action" type="radio" value="require" class="text-primary-600 focus:ring-primary-500 mt-0.5" />
                  <div class="ml-2">
                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">Rendre obligatoire</span>
                  </div>
                </label>
                <label class="flex items-start cursor-pointer p-2 rounded-lg hover:bg-gray-150 dark:hover:bg-gray-700/50">
                  <input v-model="rule.action" type="radio" value="optional" class="text-primary-600 focus:ring-primary-500 mt-0.5" />
                  <div class="ml-2">
                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">Rendre facultatif</span>
                  </div>
                </label>
              </div>

              <div class="border border-gray-200 dark:border-gray-650 bg-white dark:bg-gray-800 rounded-lg p-3 max-h-40 overflow-y-auto space-y-2">
                <label v-for="q in otherQuestions" :key="q.uuid || q.id" class="flex items-center space-x-2 cursor-pointer">
                  <input v-model="rule.targetQuestionIds" type="checkbox" :value="q.uuid || q.id" class="text-primary-600 focus:ring-primary-500 rounded" />
                  <span class="text-xs text-gray-700 dark:text-gray-300">{{ q.label }}</span>
                </label>
              </div>
            </div>

            <!-- Actions list (Dependency Mode) -->
            <div v-else-if="logicMode === 'dependency'" class="grid grid-cols-1 md:grid-cols-3 gap-3">
              <label :class="['p-3 border rounded-lg cursor-pointer flex flex-col justify-between text-xs', rule.action === 'show' ? 'border-primary-500 bg-primary-50/50 dark:bg-primary-950/20 text-primary-950 dark:text-primary-200' : 'border-gray-200 dark:border-gray-700']">
                <div class="flex items-center space-x-2 font-semibold">
                  <input v-model="rule.action" type="radio" value="show" class="text-primary-600 focus:ring-primary-500" />
                  <span>Afficher la question</span>
                </div>
                <span class="text-2xs text-gray-500 dark:text-gray-400 mt-2">💡 Masquée par défaut, s'affiche si la condition est vraie.</span>
              </label>

              <label :class="['p-3 border rounded-lg cursor-pointer flex flex-col justify-between text-xs', rule.action === 'hide' ? 'border-primary-500 bg-primary-50/50 dark:bg-primary-950/20 text-primary-950 dark:text-primary-200' : 'border-gray-200 dark:border-gray-700']">
                <div class="flex items-center space-x-2 font-semibold">
                  <input v-model="rule.action" type="radio" value="hide" class="text-primary-600 focus:ring-primary-500" />
                  <span>Masquer la question</span>
                </div>
                <span class="text-2xs text-gray-500 dark:text-gray-400 mt-2">💡 Visible par défaut, se masque si la condition est vraie.</span>
              </label>

              <label :class="['p-3 border rounded-lg cursor-pointer flex flex-col justify-between text-xs', rule.action === 'require' ? 'border-primary-500 bg-primary-50/50 dark:bg-primary-950/20 text-primary-950 dark:text-primary-200' : 'border-gray-200 dark:border-gray-700']">
                <div class="flex items-center space-x-2 font-semibold">
                  <input v-model="rule.action" type="radio" value="require" class="text-primary-600 focus:ring-primary-500" />
                  <span>Rendre obligatoire</span>
                </div>
                <span class="text-2xs text-gray-500 dark:text-gray-400 mt-2">💡 Devient obligatoire si la condition est vraie.</span>
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- Live Preview of Current Rule -->
      <div v-if="isRuleComplete" class="mt-4 p-4 rounded-xl border bg-emerald-50/80 dark:bg-emerald-950/30 border-emerald-205 dark:border-emerald-800 shadow-sm">
        <h4 class="text-2xs font-semibold uppercase tracking-wider text-emerald-800 dark:text-emerald-300 mb-1">
          ✓ Aperçu de la nouvelle règle
        </h4>
        <p class="text-xs text-emerald-900 dark:text-emerald-250 font-medium">
          {{ getRulePreviewText() }}
        </p>
      </div>

      <!-- Existing Rules Summaries -->
      <div class="border-t border-gray-200 dark:border-gray-700 pt-6 mt-6">
        <!-- Outgoing Rules (Triggered by active question) -->
        <div v-if="outgoingRules.length > 0" class="mb-6">
          <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
            <span>⚡ Règles déclenchées par cette question</span>
            <span class="text-2xs bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200 px-2 py-0.5 rounded-full font-semibold">
              {{ outgoingRules.length }}
            </span>
          </h3>
          <div class="space-y-2">
            <div
              v-for="(r, index) in outgoingRules"
              :key="index"
              class="flex items-center justify-between p-3 border border-amber-200 dark:border-amber-800/50 bg-amber-50/30 dark:bg-amber-950/20 rounded-lg shadow-sm"
            >
              <div class="flex-1 min-w-0 pr-4">
                <p class="text-xs font-semibold text-gray-900 dark:text-white leading-relaxed">
                  {{ getRuleDescriptionText(r) }}
                </p>
                <p class="text-2xs text-gray-500 dark:text-gray-400 mt-1">
                  Type d'action: {{ getRuleTypeLabel(r.type) }}
                </p>
              </div>
              <button
                @click="removeRule(r.originalIndex)"
                class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-lg transition-colors border-0"
              >
                <TrashIcon class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>

        <!-- Incoming Rules (Dependencies on prior questions) -->
        <div v-if="incomingRules.length > 0">
          <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
            <span>🔗 Dépendances (Question conditionnée par d'autres)</span>
            <span class="text-2xs bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 px-2 py-0.5 rounded-full font-semibold">
              {{ incomingRules.length }}
            </span>
          </h3>
          <div class="space-y-2">
            <div
              v-for="(inc, index) in incomingRules"
              :key="index"
              class="flex items-center justify-between p-3 border border-blue-200 dark:border-blue-800/50 bg-blue-50/30 dark:bg-blue-950/20 rounded-lg shadow-sm"
            >
              <div class="flex-1 min-w-0 pr-4">
                <p class="text-xs font-semibold text-gray-900 dark:text-white leading-relaxed">
                  {{ getRuleDescriptionText(inc.rule) }}
                </p>
              </div>
              <button
                @click="removeRule(inc.originalIndex)"
                class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-lg transition-colors border-0"
              >
                <TrashIcon class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center justify-end space-x-3 pt-6 mt-6 border-t border-gray-200 dark:border-gray-700">
        <button
          @click="$emit('close')"
          class="px-4 py-2 text-xs font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors border-0 cursor-pointer"
        >
          Annuler
        </button>
        <button
          v-if="isRuleComplete"
          @click="addRule"
          class="px-4 py-2 text-xs font-semibold text-white bg-primary-600 hover:bg-primary-700 rounded-lg transition-colors border-0 cursor-pointer shadow"
        >
          Ajouter la règle
        </button>
        <button
          @click="saveRules"
          class="px-4 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors border-0 cursor-pointer shadow"
        >
          Enregistrer et fermer
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from 'vue';
import {
  XMarkIcon,
  EyeIcon,
  ArrowRightIcon,
  StopIcon,
  ExclamationCircleIcon,
  BoltIcon,
  LinkIcon,
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
        // Fallback backward compatibility
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
  { value: 'show_hide', title: 'Afficher / Masquer des questions', icon: EyeIcon },
  { value: 'jump_section', title: 'Aller à une section', icon: ArrowRightIcon },
  { value: 'end_survey', title: 'Terminer le questionnaire', icon: StopIcon },
  { value: 'set_required', title: 'Modifier obligation', icon: ExclamationCircleIcon }
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
    // Dependency Mode
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

// Outgoing rules: rules where the current question is part of the triggering conditions
const outgoingRules = computed(() => {
  return existingRules.value
    .map((r, index) => ({ ...r, originalIndex: index }))
    .filter(r => r.conditions.some(c => String(c.sourceQuestionId) === String(currentQuestionId)));
});

// Incoming rules: dependencies targeting the current question, triggered by other questions
const incomingRules = computed(() => {
  return existingRules.value
    .map((r, index) => ({ ...r, originalIndex: index }))
    .filter(r => !r.conditions.some(c => String(c.sourceQuestionId) === String(currentQuestionId)));
});

function removeRule(originalIndex: number) {
  existingRules.value.splice(originalIndex, 1);
}

function saveRules() {
  const convertedRules: ConditionalRule[] = existingRules.value.map(r => {
    // Backwards compatibility fallback properties from the first condition
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

// Generate verbal explanation of rule conditions
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
