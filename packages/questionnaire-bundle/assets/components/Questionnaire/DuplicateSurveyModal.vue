<template>
  <Teleport to="body">
    <div
      class="fixed inset-0 z-[9999] flex items-center justify-center"
      @click.self="$emit('close')"
    >
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="$emit('close')" />

      <!-- Modal Panel -->
      <div
        class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg mx-4 p-6 space-y-5"
        style="z-index: 10000;"
      >
        <!-- Header -->
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/40 rounded-xl flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75" />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              Dupliquer le questionnaire
            </h3>
          </div>
          <button
            @click="$emit('close')"
            class="p-2 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Title Input -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
            Titre du nouveau questionnaire
          </label>
          <input
            v-model="newTitle"
            type="text"
            class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
            placeholder="Titre du questionnaire"
            autofocus
          />
        </div>

        <!-- Overview Info Box -->
        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600 space-y-2">
          <h4 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
            <span>📋 Contenu qui sera dupliqué</span>
          </h4>
          <div class="grid grid-cols-2 gap-3 text-xs text-gray-600 dark:text-gray-400 pt-1">
            <div class="flex items-center gap-1.5">
              <span class="font-medium text-gray-900 dark:text-white">{{ totalSections }}</span> section(s)
            </div>
            <div class="flex items-center gap-1.5">
              <span class="font-medium text-gray-900 dark:text-white">{{ totalQuestions }}</span> question(s)
            </div>
          </div>
        </div>

        <!-- Logic Adaptation Banner -->
        <div class="p-4 bg-amber-50 dark:bg-amber-950/30 rounded-xl border border-amber-200 dark:border-amber-800 space-y-1.5">
          <div class="flex items-center space-x-2 text-amber-900 dark:text-amber-200 font-semibold text-sm">
            <span>⚡ Logique conditionnelle automatiquement transposée</span>
          </div>
          <p class="text-xs text-amber-800/80 dark:text-amber-300 leading-relaxed">
            Toutes les règles conditionnelles (déclencheurs, masquages/affichages, sauts de section) seront automatiquement ré-associées aux nouvelles questions et sections dupliquées.
          </p>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end space-x-3 pt-2 border-t border-gray-200 dark:border-gray-700">
          <button
            @click="$emit('close')"
            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors"
          >
            Annuler
          </button>
          <button
            :disabled="!newTitle.trim()"
            @click="confirmDuplicate"
            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed rounded-lg transition-colors"
          >
            Dupliquer le questionnaire
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';

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
