<script setup lang="ts">
import { ref, watch } from 'vue';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Tag from 'primevue/tag';
import { BellIcon, EnvelopeIcon, UsersIcon } from '@heroicons/vue/24/outline';
import { showDanger, showSuccess, showWarn } from '@helpers/toast.js';
import { useResponseStore } from '@/stores/responses';
import { initiales } from '@/utils/participants';
import { DialogHeader, FormField, FormSection } from '@/components/Form';

/**
 * Relance d'un ou plusieurs participants. Objet et message sont facultatifs : vides, le modèle d'e-mail
 * « Questionnaires — relance » s'applique ; le lien personnel de chaque participant est ajouté par le serveur.
 */
interface Cible {
  id: string;
  participantName: string;
  participantEmail: string;
  statusLabel: string;
  statusSeverity: string;
}

const props = defineProps<{ visible: boolean; surveyId: string; surveyTitle: string; targets: Cible[] }>();
const emit = defineEmits<{ close: []; sent: [] }>();

const responseStore = useResponseStore();
const objet = ref('');
const message = ref('');
const envoi = ref(false);

function reinitialiser() {
  objet.value = '';
  message.value = '';
}

watch(() => props.visible, (v) => { if (v) reinitialiser(); });

async function envoyer() {
  envoi.value = true;
  try {
    const res = await responseStore.sendReminders(
      props.surveyId,
      props.targets.map(r => r.id),
      objet.value.trim() || undefined,
      message.value.trim() || undefined,
    );
    if (res?.failedCount) {
      showWarn(`${res.sentCount} relance(s) envoyée(s), ${res.failedCount} échec(s) d'envoi.`);
    } else {
      showSuccess(`${res?.sentCount ?? 0} relance(s) envoyée(s).`);
    }
    emit('sent');
  } catch (e: any) {
    showDanger(e?.response?.data?.error || e?.response?.data?.detail || 'Les relances n\'ont pas pu être envoyées.');
  } finally {
    envoi.value = false;
  }
}
</script>

<template>
<Dialog
  :style="{ width: '92vw', maxWidth: '680px' }"
  :visible="visible"
  :modal="true"
  :closable="true"
  :draggable="false"
  @update:visible="(v) => !v && emit('close')"
>
  <template #header>
    <DialogHeader
      :icon="BellIcon"
      tone="amber"
      title="Envoyer un rappel"
      :subtitle="`${targets.length} participant${targets.length > 1 ? 's' : ''} ciblé${targets.length > 1 ? 's' : ''}`"
    />
  </template>

  <div class="q-form">
    <!-- Recipients list -->
    <FormSection :icon="UsersIcon" tone="slate" title="Destinataires">
      <div class="max-h-36 overflow-y-auto space-y-1.5 pr-1">
        <div
          v-for="response in targets"
          :key="response.id"
          class="flex items-center justify-between bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3 py-2"
        >
          <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center text-sm font-bold text-slate-700 dark:text-slate-300">
              {{ initiales(response.participantName) }}
            </div>
            <div>
              <p class="text-sm font-medium text-slate-900 dark:text-white">{{ response.participantName }}</p>
              <p class="text-sm text-slate-500 dark:text-slate-400">{{ response.participantEmail }}</p>
            </div>
          </div>
          <Tag
            :value="response.statusLabel"
            :severity="response.statusSeverity"
            class="text-sm font-sans"
          />
        </div>
      </div>
    </FormSection>

    <!-- Message preview / editor -->
    <FormSection :icon="EnvelopeIcon" tone="amber" title="Message de rappel">
      <template #actions>
        <button
          type="button"
          class="text-sm font-semibold text-primary-600 dark:text-primary-400 hover:underline border-0 bg-transparent cursor-pointer"
          @click="reinitialiser"
        >
          Réinitialiser
        </button>
      </template>

      <FormField label="Objet (facultatif)" for="reminder-subject">
        <input
          id="reminder-subject"
          v-model="objet"
          type="text"
          maxlength="255"
          class="q-input"
          :placeholder="`Rappel : questionnaire « ${surveyTitle} » en attente`"
        />
      </FormField>

      <FormField label="Message (facultatif)" for="reminder-message">
        <textarea
          id="reminder-message"
          v-model="message"
          rows="6"
          maxlength="5000"
          class="q-input text-sm leading-relaxed"
          placeholder="Laissez vide pour utiliser le message du modèle d'e-mail."
        />
      </FormField>

      <p class="text-sm text-slate-500 dark:text-slate-400">
        Chaque participant reçoit son lien personnel sous le message.
      </p>
    </FormSection>

    <!-- Actions -->
    <div class="q-actions">
      <Button
        label="Annuler"
        severity="secondary"
        outlined
        @click="emit('close')"
      />
      <Button
        :label="`Envoyer ${targets.length > 1 ? 'les ' + targets.length + ' rappels' : 'le rappel'}`"
        severity="warn"
        :loading="envoi"
        icon="pi pi-bell"
        @click="envoyer"
      />
    </div>
  </div>
</Dialog>
</template>
