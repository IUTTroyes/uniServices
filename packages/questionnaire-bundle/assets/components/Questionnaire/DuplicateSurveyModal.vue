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
      <DialogHeader :icon="DocumentDuplicateIcon" title="Dupliquer le questionnaire" />
    </template>

    <div class="q-form">
      <FormField label="Titre du nouveau questionnaire" for="dup-survey-title" required>
        <input
          id="dup-survey-title"
          v-model="newTitle"
          type="text"
          class="q-input"
          placeholder="Titre du questionnaire"
          autofocus
        />
      </FormField>

      <FormSection :icon="ClipboardDocumentListIcon" tone="slate" title="Contenu qui sera dupliqué">
        <div class="grid grid-cols-2 gap-3">
          <div class="p-3 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80 text-center">
            <span class="block text-xl font-bold text-primary-600 dark:text-primary-400">{{ totalSections }}</span>
            <span class="text-[11px] text-slate-500 dark:text-slate-400">section(s)</span>
          </div>
          <div class="p-3 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80 text-center">
            <span class="block text-xl font-bold text-primary-600 dark:text-primary-400">{{ totalQuestions }}</span>
            <span class="text-[11px] text-slate-500 dark:text-slate-400">question(s)</span>
          </div>
        </div>
      </FormSection>

      <div class="q-callout q-callout-warning">
        <BoltIcon class="w-4 h-4 shrink-0 mt-0.5" />
        <div>
          <p class="font-semibold">Logique conditionnelle automatiquement transposée</p>
          <p class="mt-0.5 opacity-90">
            Toutes les règles conditionnelles (déclencheurs, masquages/affichages, sauts de section) seront automatiquement ré-associées aux nouvelles questions et sections dupliquées.
          </p>
        </div>
      </div>

      <div class="q-actions">
        <Button severity="secondary" outlined label="Annuler" @click="$emit('close')" />
        <Button
          icon="pi pi-copy"
          label="Dupliquer le questionnaire"
          :disabled="!newTitle.trim()"
          @click="confirmDuplicate"
        />
      </div>
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import { BoltIcon, ClipboardDocumentListIcon, DocumentDuplicateIcon } from '@heroicons/vue/24/outline';
import { DialogHeader, FormField, FormSection } from '../Form';

interface Props {
  survey: Record<string, any>;
}

interface Emits {
  close: [];
  confirm: [payload: { newTitle: string }];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const newTitle = ref(`${props.survey.title || 'Questionnaire'} (Copie)`);

const totalSections = computed(() => props.survey.sections?.length ?? '?');

const totalQuestions = computed(() => {
  if (!props.survey.sections) return '?';
  return props.survey.sections.reduce((acc: number, sec: any) => acc + (sec.questions?.length || 0), 0);
});

function confirmDuplicate() {
  if (!newTitle.value.trim()) return;
  emit('confirm', {
    newTitle: newTitle.value.trim()
  });
}
</script>
