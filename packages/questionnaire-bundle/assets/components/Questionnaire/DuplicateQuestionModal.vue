<template>
  <Dialog
    header="Dupliquer la question"
    :visible="true"
    :modal="true"
    :closable="true"
    :style="{ width: '500px' }"
    @update:visible="$emit('close')"
  >
    <div class="space-y-5 py-2">
      <!-- Label Input -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          Libellé de la nouvelle question
        </label>
        <input
          v-model="newLabel"
          type="text"
          class="input-field w-full"
          placeholder="Intitulé de la question"
        />
      </div>

      <!-- Conditional Rules Option (only if question has rules) -->
      <div
        v-if="hasConditionalRules"
        class="p-3.5 bg-amber-50/70 dark:bg-amber-950/30 rounded-xl border border-amber-200 dark:border-amber-800 space-y-3"
      >
        <div class="flex items-center space-x-2 text-amber-900 dark:text-amber-200 font-semibold text-sm">
          <BoltIcon class="w-4 h-4 text-amber-600 dark:text-amber-400" />
          <span>Cette question possède {{ conditionalRules.length }} règle(s) conditionnelle(s)</span>
        </div>

        <div class="space-y-2">
          <label class="flex items-start space-x-2.5 cursor-pointer p-2 rounded-lg hover:bg-amber-100/50 dark:hover:bg-amber-900/30">
            <input
              v-model="copyRulesMode"
              type="radio"
              value="copy_adapt"
              class="mt-0.5 text-amber-600 focus:ring-amber-500"
            />
            <div class="text-xs">
              <span class="font-medium text-gray-900 dark:text-white">Copier et adapter les règles (Recommandé)</span>
              <p class="text-gray-500 dark:text-gray-400 mt-0.5">La nouvelle question sera définie comme déclencheur de ces mêmes actions.</p>
            </div>
          </label>

          <label class="flex items-start space-x-2.5 cursor-pointer p-2 rounded-lg hover:bg-amber-100/50 dark:hover:bg-amber-900/30">
            <input
              v-model="copyRulesMode"
              type="radio"
              value="none"
              class="mt-0.5 text-amber-600 focus:ring-amber-500"
            />
            <div class="text-xs">
              <span class="font-medium text-gray-900 dark:text-white">Ne pas copier les règles</span>
              <p class="text-gray-500 dark:text-gray-400 mt-0.5">Dupliquer la question sans aucune logique conditionnelle.</p>
            </div>
          </label>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
        <Button severity="secondary" @click="$emit('close')">
          Annuler
        </Button>
        <Button
          severity="primary"
          :disabled="!newLabel.trim()"
          @click="confirmDuplicate"
        >
          Dupliquer la question
        </Button>
      </div>
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { BoltIcon } from '@heroicons/vue/24/outline';
import type { Question } from '@/types/survey';

interface Props {
  question: Question;
}

interface Emits {
  close: [];
  confirm: [payload: { newLabel: string; copyRulesMode: 'copy_adapt' | 'none' }];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const newLabel = ref(`${props.question.label} (Copie)`);
const copyRulesMode = ref<'copy_adapt' | 'none'>('copy_adapt');

const conditionalRules = computed(() => props.question.conditionalRules || []);
const hasConditionalRules = computed(() => conditionalRules.value.length > 0);

function confirmDuplicate() {
  if (!newLabel.value.trim()) return;
  emit('confirm', {
    newLabel: newLabel.value.trim(),
    copyRulesMode: copyRulesMode.value
  });
}
</script>
