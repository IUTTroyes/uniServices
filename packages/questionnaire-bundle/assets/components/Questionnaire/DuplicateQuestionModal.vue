<template>
  <Dialog
    :visible="true"
    :modal="true"
    :closable="true"
    :draggable="false"
    :style="{ width: '92vw', maxWidth: '520px' }"
    @update:visible="$emit('close')"
  >
    <template #header>
      <DialogHeader :icon="DocumentDuplicateIcon" title="Dupliquer la question" />
    </template>

    <div class="q-form">
      <FormField label="Libellé de la nouvelle question" for="dup-question-label" required>
        <input
          id="dup-question-label"
          v-model="newLabel"
          type="text"
          class="q-input"
          placeholder="Intitulé de la question"
        />
      </FormField>

      <!-- Conditional Rules Option (only if question has rules) -->
      <FormSection
        v-if="hasConditionalRules"
        :icon="BoltIcon"
        tone="amber"
        title="Logique conditionnelle"
        :subtitle="`Cette question possède ${conditionalRules.length} règle(s) conditionnelle(s)`"
      >
        <div class="space-y-2">
          <label :class="['q-choice', copyRulesMode === 'copy_adapt' && 'q-choice-active']">
            <input v-model="copyRulesMode" type="radio" value="copy_adapt" class="q-check mt-0.5" />
            <div>
              <span class="q-choice-title">Copier et adapter les règles (Recommandé)</span>
              <span class="q-choice-desc block">La nouvelle question sera définie comme déclencheur de ces mêmes actions.</span>
            </div>
          </label>

          <label :class="['q-choice', copyRulesMode === 'none' && 'q-choice-active']">
            <input v-model="copyRulesMode" type="radio" value="none" class="q-check mt-0.5" />
            <div>
              <span class="q-choice-title">Ne pas copier les règles</span>
              <span class="q-choice-desc block">Dupliquer la question sans aucune logique conditionnelle.</span>
            </div>
          </label>
        </div>
      </FormSection>

      <div class="q-actions">
        <Button severity="secondary" outlined label="Annuler" @click="$emit('close')" />
        <Button
          icon="pi pi-copy"
          label="Dupliquer la question"
          :disabled="!newLabel.trim()"
          @click="confirmDuplicate"
        />
      </div>
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { BoltIcon, DocumentDuplicateIcon } from '@heroicons/vue/24/outline';
import type { Question } from '@/types/survey';
import { DialogHeader, FormField, FormSection } from '../Form';

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
