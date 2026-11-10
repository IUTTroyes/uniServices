<script setup lang="ts">
import { computed } from 'vue';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Tag from 'primevue/tag';
import { ClockIcon } from '@heroicons/vue/24/outline';
import { EmptyState } from '@components';
import { formatDateTime, formatRelativeTime } from '@/utils/date';
import { initiales } from '@/utils/participants';
import { DialogHeader } from '@/components/Form';

/** Historique des relances d'un participant (du plus récent au plus ancien). */
const props = defineProps<{ visible: boolean; response: any | null }>();
const emit = defineEmits<{ close: []; relancer: [response: any] }>();

const historique = computed(() =>
  [...(props.response?.remindersHistory || [])].sort((a: any, b: any) => new Date(b.sentAt).getTime() - new Date(a.sentAt).getTime())
);
</script>

<template>
<Dialog
  :style="{ width: '92vw', maxWidth: '720px' }"
  :visible="visible"
  :modal="true"
  :closable="true"
  :draggable="false"
  @update:visible="(v) => !v && emit('close')"
>
  <template #header>
    <DialogHeader
      :icon="ClockIcon"
      tone="amber"
      title="Historique des relances"
      :subtitle="`${response?.participantName || 'Participant'} (${response?.participantEmail || ''})`"
      @close="emit('close')"
    />
  </template>

  <div v-if="response" class="space-y-5 pt-1">
    <!-- Participant Summary Banner -->
    <div class="p-3.5 rounded-2xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/70 dark:bg-slate-900/40 flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-amber-100 dark:bg-amber-950/70 text-amber-800 dark:text-amber-300 rounded-full flex items-center justify-center font-bold text-sm border border-amber-200 dark:border-amber-800/60 shadow-xs">
          {{ initiales(response.participantName) }}
        </div>
        <div>
          <p class="text-sm font-semibold text-slate-900 dark:text-white">
            {{ response.participantName }}
          </p>
          <p class="text-sm text-slate-500 dark:text-slate-400">
            {{ response.participantEmail }}
          </p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <Tag
          :value="response.statusLabel"
          :severity="response.statusSeverity"
          class="text-sm font-sans"
        />
        <Tag
          :value="`${response.remindersCount || 0} relance${(response.remindersCount || 0) > 1 ? 's' : ''}`"
          :severity="(response.remindersCount || 0) > 0 ? 'warn' : 'secondary'"
          class="text-sm font-sans"
        />
      </div>
    </div>

    <!-- History List / Timeline -->
    <div v-if="response.remindersHistory && response.remindersHistory.length > 0" class="space-y-3">
      <div class="flex items-center justify-between pb-1">
        <h4 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
          <i class="pi pi-history text-sm" />
          <span>Détail chronologique des envois ({{ response.remindersHistory.length }})</span>
        </h4>
        <span class="text-sm text-slate-400 dark:text-slate-500">
          Classé du plus récent au plus ancien
        </span>
      </div>

      <div class="relative pl-6 space-y-4 before:content-[''] before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200 dark:before:bg-slate-700">
        <div
          v-for="(item, idx) in historique"
          :key="item.id || idx"
          class="relative group"
        >
          <!-- Timeline node dot -->
          <div class="absolute -left-6 top-2 w-5 h-5 rounded-full bg-amber-500 dark:bg-amber-400 border-2 border-white dark:border-slate-800 flex items-center justify-center text-white shadow-xs">
            <i class="pi pi-bell text-sm" />
          </div>

          <!-- Card Content -->
          <div class="p-3.5 bg-white dark:bg-slate-800/90 border border-slate-200 dark:border-slate-700/80 rounded-xl shadow-xs transition hover:border-amber-300 dark:hover:border-amber-700">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-1.5">
              <div class="flex items-center gap-2">
                <span class="text-sm font-bold text-slate-900 dark:text-white">
                  Relance #{{ response.remindersHistory.length - idx }}
                </span>
                <Tag
                  :value="item.type === 'bulk' ? 'Relance groupée' : (item.type === 'automatic' ? 'Automatique' : 'Relance individuelle')"
                  :severity="item.type === 'bulk' ? 'info' : (item.type === 'automatic' ? 'help' : 'warn')"
                  class="text-sm font-sans !py-0.5 !px-2"
                />
              </div>
              <div class="text-right">
                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                  {{ formatDateTime(item.sentAt) }}
                </span>
                <span class="text-sm text-slate-400 dark:text-slate-500 block">
                  ({{ formatRelativeTime(item.sentAt) }})
                </span>
              </div>
            </div>

            <div v-if="item.subject" class="text-sm font-medium text-slate-800 dark:text-slate-200 mb-1">
              <span class="text-slate-400 dark:text-slate-500 font-normal">Objet :</span> {{ item.subject }}
            </div>

            <div v-if="item.message" class="mt-2 p-2.5 bg-slate-50 dark:bg-slate-900/50 rounded-lg border border-slate-100 dark:border-slate-800 text-sm text-slate-600 dark:text-slate-300 whitespace-pre-line font-mono leading-relaxed">
              {{ item.message }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty History State -->
    <div v-else class="py-8 text-center bg-slate-50/50 dark:bg-slate-900/30 border border-dashed border-slate-200 dark:border-slate-700/80 rounded-2xl">
      <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 mb-3">
        <i class="pi pi-bell-slash text-xl" />
      </div>
      <h4 class="text-sm font-semibold text-slate-800 dark:text-slate-200">
        Aucune relance effectuée
      </h4>
      <p class="text-sm text-slate-500 dark:text-slate-400 max-w-sm mx-auto mt-1 mb-4">
        Ce participant n'a encore reçu aucun rappel ou email de relance pour ce questionnaire.
      </p>
      <Button
        v-if="!response.completed"
        type="button"
        icon="pi pi-bell"
        label="Envoyer une première relance"
        severity="warn"
        size="small"
        class="text-sm shadow-xs"
        @click="emit('relancer', response)"
      />
    </div>

    <!-- Modal Actions Footer -->
    <div class="flex items-center justify-between pt-3 border-t border-slate-200 dark:border-slate-700/80">
      <Button
        type="button"
        label="Fermer"
        severity="secondary"
        outlined
        size="small"
        class="text-sm !py-1.5 !px-3"
        @click="emit('close')"
      />
      <Button
        v-if="!response.completed"
        type="button"
        icon="pi pi-bell"
        label="Nouvelle relance"
        severity="warn"
        size="small"
        class="text-sm !py-1.5 !px-3 shadow-xs"
        @click="emit('relancer', response)"
      />
    </div>
  </div>
</Dialog>
</template>
