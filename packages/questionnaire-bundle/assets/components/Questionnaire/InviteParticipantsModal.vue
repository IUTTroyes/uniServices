<script setup lang="ts">
import { ref, computed } from 'vue';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import { DocumentArrowUpIcon, InformationCircleIcon, UserPlusIcon, UsersIcon } from '@heroicons/vue/24/outline';
import { showDanger, showSuccess } from '@helpers/toast.js';
import { useResponseStore } from '@/stores/responses';
import { extraireAdresses, extraireAdressesCsv } from '@/utils/participants';
import { DialogHeader, FormField, FormSection } from '../Form';

/**
 * Invitation de nouveaux participants à un questionnaire publié : le serveur crée les invitations et envoie
 * l'e-mail « Questionnaires — invitation à répondre » (modèle réglable dans l'éditeur des e-mails).
 */
const props = defineProps<{ surveyId: string }>();
const emit = defineEmits<{ close: []; invited: [] }>();

const responseStore = useResponseStore();

const fileInput = ref<HTMLInputElement | null>(null);
const activeTab = ref<'manual' | 'csv'>('manual');
const saisie = ref('');
const csv = ref<{ valides: string[]; invalides: string[] }>({ valides: [], invalides: [] });
const envoi = ref(false);

const tabs = [
  { id: 'manual', label: 'Saisie des adresses' },
  { id: 'csv', label: 'Import CSV' },
] as const;

const adresses = computed(() => (activeTab.value === 'manual' ? extraireAdresses(saisie.value) : csv.value));

function lireFichier(file?: File | null) {
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    csv.value = extraireAdressesCsv(String(e.target?.result ?? ''));
  };
  reader.readAsText(file);
}

function handleFileSelect(event: Event) {
  lireFichier((event.target as HTMLInputElement).files?.[0]);
}

function handleDrop(event: DragEvent) {
  event.preventDefault();
  lireFichier(event.dataTransfer?.files[0]);
}

async function sendInvitations() {
  if (adresses.value.valides.length === 0) return;
  envoi.value = true;
  try {
    const res = await responseStore.inviteParticipants(props.surveyId, adresses.value.valides);
    const parties = [`${res.invitationsCount} invitation(s) créée(s)`, `${res.emailsCount} e-mail(s) envoyé(s)`];
    if (res.ignores) parties.push(`${res.ignores} adresse(s) déjà invitée(s)`);
    showSuccess(parties.join(', ') + '.');
    emit('invited');
  } catch (e: any) {
    showDanger(e?.response?.data?.detail || e?.response?.data?.error || 'Les invitations n\'ont pas pu être envoyées.');
  } finally {
    envoi.value = false;
  }
}
</script>

<template>
  <Dialog
    :style="{ width: '92vw', maxWidth: '720px' }"
    :visible="true"
    :modal="true"
    :closable="true"
    :draggable="false"
    @update:visible="emit('close')"
  >
    <template #header>
      <DialogHeader :icon="UserPlusIcon" title="Inviter des participants" subtitle="Chaque adresse reçoit un lien personnel par e-mail" />
    </template>

    <div class="q-form">
      <div class="q-tabs">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          type="button"
          :class="['q-tab', activeTab === tab.id && 'q-tab-active']"
          @click="activeTab = tab.id"
        >
          {{ tab.label }}
        </button>
      </div>

      <FormSection v-if="activeTab === 'manual'" :icon="UsersIcon" tone="blue" title="Adresses e-mail" subtitle="Une par ligne, ou séparées par des virgules">
        <FormField label="Adresses à inviter" for="invite-adresses">
          <textarea id="invite-adresses" v-model="saisie" rows="6" class="q-input" placeholder="prenom.nom@exemple.fr" />
        </FormField>
      </FormSection>

      <FormSection v-else :icon="DocumentArrowUpIcon" tone="purple" title="Fichier CSV" subtitle="Colonne « email » (séparateur , ou ;)">
        <div
          class="border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-xl p-6 text-center bg-white dark:bg-slate-800"
          @drop="handleDrop"
          @dragover.prevent
          @dragenter.prevent
        >
          <input ref="fileInput" type="file" accept=".csv,text/csv" class="hidden" aria-label="Fichier CSV" @change="handleFileSelect" />
          <DocumentArrowUpIcon class="w-10 h-10 text-slate-400 mx-auto mb-3" />
          <p class="text-sm text-slate-600 dark:text-slate-400">
            Glissez votre fichier ici ou
            <Button link label="choisissez un fichier" class="!p-0 text-sm" @click="fileInput?.click()" />
          </p>
        </div>
      </FormSection>

      <div v-if="adresses.invalides.length" class="q-callout q-callout-warning text-sm">
        Adresses ignorées car invalides : {{ adresses.invalides.slice(0, 5).join(', ') }}<span v-if="adresses.invalides.length > 5"> et {{ adresses.invalides.length - 5 }} autre(s)</span>.
      </div>

      <div class="q-callout q-callout-info text-sm">
        <InformationCircleIcon class="w-4 h-4 shrink-0 mt-0.5" />
        <p>Le message envoyé est le modèle « Questionnaires — invitation à répondre », modifiable dans la configuration des e-mails. Les adresses déjà invitées sont ignorées.</p>
      </div>

      <div class="q-actions !justify-between">
        <span class="text-sm text-slate-600 dark:text-slate-400">
          {{ adresses.valides.length }} adresse{{ adresses.valides.length !== 1 ? 's' : '' }} à inviter
        </span>
        <div class="flex items-center gap-3">
          <Button severity="secondary" outlined label="Annuler" @click="emit('close')" />
          <Button icon="pi pi-send" label="Envoyer les invitations" :loading="envoi" :disabled="adresses.valides.length === 0" @click="sendInvitations" />
        </div>
      </div>
    </div>
  </Dialog>
</template>
