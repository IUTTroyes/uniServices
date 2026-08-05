<template>
  <Dialog
    header="Dupliquer la section"
    :visible="true"
    :modal="true"
    :closable="true"
    :style="{ width: '520px' }"
    @update:visible="$emit('close')"
  >
    <div class="space-y-5 py-2">
      <!-- Title Input -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          Nom de la nouvelle section
        </label>
        <input
          v-model="newTitle"
          type="text"
          class="input-field w-full"
          placeholder="Titre de la section"
        />
      </div>

      <!-- Option 1: Duplicate Questions -->
      <div class="p-3.5 bg-gray-50 dark:bg-gray-800/80 rounded-xl border border-gray-200 dark:border-gray-700">
        <label class="flex items-start space-x-3 cursor-pointer">
          <input
            v-model="duplicateQuestions"
            type="checkbox"
            class="mt-1 text-primary-600 rounded focus:ring-primary-500"
          />
          <div>
            <span class="text-sm font-medium text-gray-900 dark:text-white">
              Dupliquer toutes les questions ({{ section.questions?.length || 0 }})
            </span>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
              Copie également l'ensemble des options, des textes et des paramètres des questions.
            </p>
          </div>
        </label>
      </div>

      <!-- Option 2: Adapt Conditional Rules -->
      <div
        v-if="duplicateQuestions && hasConditionalRules"
        class="p-3.5 bg-amber-50/70 dark:bg-amber-950/30 rounded-xl border border-amber-200 dark:border-amber-800"
      >
        <label class="flex items-start space-x-3 cursor-pointer">
          <input
            v-model="adaptConditionalRules"
            type="checkbox"
            class="mt-1 text-amber-600 rounded focus:ring-amber-500"
          />
          <div>
            <span class="text-sm font-semibold text-amber-900 dark:text-amber-200 flex items-center gap-1.5">
              <span>⚡ Adapter la logique conditionnelle</span>
            </span>
            <p class="text-xs text-amber-800/80 dark:text-amber-300 mt-0.5 leading-relaxed">
              Ré-associe automatiquement les règles conditionnelles vers les nouvelles questions de la section dupliquée pour conserver une logique autonome.
            </p>
          </div>
        </label>
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
        <Button severity="secondary" @click="$emit('close')">
          Annuler
        </Button>
        <Button
          severity="primary"
          :disabled="!newTitle.trim()"
          @click="confirmDuplicate"
        >
          Dupliquer la section
        </Button>
      </div>
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import type { Section } from '@/types/survey';

interface Props {
  section: Section;
}

interface Emits {
  close: [];
  confirm: [payload: { newTitle: string; duplicateQuestions: boolean; adaptConditionalRules: boolean }];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const newTitle = ref(`${props.section.title} (copie)`);
const duplicateQuestions = ref(true);
const adaptConditionalRules = ref(true);

const hasConditionalRules = computed(() => {
  if (!props.section.questions) return false;
  return props.section.questions.some(q => q.conditionalRules && q.conditionalRules.length > 0);
});

function confirmDuplicate() {
  if (!newTitle.value.trim()) return;
  emit('confirm', {
    newTitle: newTitle.value.trim(),
    duplicateQuestions: duplicateQuestions.value,
    adaptConditionalRules: adaptConditionalRules.value
  });
}
</script>
