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
      <DialogHeader :icon="DocumentDuplicateIcon" title="Dupliquer la section" />
    </template>

    <div class="q-form">
      <FormField label="Nom de la nouvelle section" for="dup-section-title" required>
        <input
          id="dup-section-title"
          v-model="newTitle"
          type="text"
          class="q-input"
          placeholder="Titre de la section"
        />
      </FormField>

      <div class="space-y-3">
        <ToggleCard
          v-model="duplicateQuestions"
          :icon="QueueListIcon"
          :title="`Dupliquer toutes les questions (${section.questions?.length || 0})`"
          description="Copie également l'ensemble des options, des textes et des paramètres des questions."
        />

        <ToggleCard
          v-if="duplicateQuestions && hasConditionalRules"
          v-model="adaptConditionalRules"
          :icon="BoltIcon"
          tone="amber"
          title="Adapter la logique conditionnelle"
          description="Ré-associe automatiquement les règles conditionnelles vers les nouvelles questions de la section dupliquée pour conserver une logique autonome."
        />
      </div>

      <div class="q-actions">
        <Button severity="secondary" outlined label="Annuler" @click="$emit('close')" />
        <Button
          icon="pi pi-copy"
          label="Dupliquer la section"
          :disabled="!newTitle.trim()"
          @click="confirmDuplicate"
        />
      </div>
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { BoltIcon, DocumentDuplicateIcon, QueueListIcon } from '@heroicons/vue/24/outline';
import type { Section } from '@/types/survey';
import { DialogHeader, FormField, ToggleCard } from '../Form';

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
