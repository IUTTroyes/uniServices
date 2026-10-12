<template>
  <Dialog
    :style="{ width: '92vw', maxWidth: '780px' }"
    :visible="true"
    :modal="true"
    :closable="true"
    :draggable="false"
    @update:visible="$emit('close')"
  >
    <template #header>
      <DialogHeader
        :icon="UserPlusIcon"
        title="Inviter des participants"
        subtitle="Saisie manuelle, import CSV et message d'invitation"
      />
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

      <!-- Manual Invite -->
      <FormSection
        v-if="activeTab === 'manual'"
        :icon="UsersIcon"
        tone="blue"
        title="Participants"
        subtitle="Seul l'email est obligatoire"
      >
        <div class="space-y-2">
          <div
            v-for="(participant, index) in manualParticipants"
            :key="index"
            class="flex items-center gap-2"
          >
            <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-2">
              <input v-model="participant.email" type="email" placeholder="Email" class="q-input" :aria-label="`Email du participant ${index + 1}`" />
              <input v-model="participant.name" type="text" placeholder="Nom (optionnel)" class="q-input" :aria-label="`Nom du participant ${index + 1}`" />
              <input v-model="participant.group" type="text" placeholder="Groupe (optionnel)" class="q-input" :aria-label="`Groupe du participant ${index + 1}`" />
            </div>
            <Button
              v-if="manualParticipants.length > 1"
              icon="pi pi-times"
              severity="danger"
              text
              rounded
              aria-label="Retirer"
              @click="removeManualParticipant(index)"
            />
          </div>
        </div>

        <Button
          icon="pi pi-plus"
          label="Ajouter un participant"
          severity="secondary"
          outlined
          class="w-full"
          @click="addManualParticipant"
        />
      </FormSection>

      <!-- CSV Import -->
      <FormSection
        v-else-if="activeTab === 'csv'"
        :icon="DocumentArrowUpIcon"
        tone="purple"
        title="Fichier CSV"
        subtitle="Format attendu : email, nom, groupe"
      >
        <div
          class="border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-xl p-6 text-center bg-white dark:bg-slate-800 hover:border-primary-400 dark:hover:border-primary-600 transition-colors"
          @drop="handleDrop"
          @dragover.prevent
          @dragenter.prevent
        >
          <input ref="fileInput" type="file" accept=".csv" class="hidden" @change="handleFileSelect" />
          <DocumentArrowUpIcon class="w-10 h-10 text-slate-400 mx-auto mb-3" />
          <p class="text-sm text-slate-600 dark:text-slate-400">
            Glissez votre fichier CSV ici ou
            <button
              type="button"
              class="font-semibold text-primary-600 dark:text-primary-400 hover:underline border-0 bg-transparent cursor-pointer"
              @click="fileInput?.click()"
            >
              cliquez pour sélectionner
            </button>
          </p>
        </div>

        <div v-if="csvPreview.length > 0" class="space-y-2">
          <h4 class="q-label !mb-0">Aperçu ({{ csvPreview.length }} participants)</h4>
          <div class="max-h-40 overflow-y-auto rounded-xl border border-slate-200 dark:border-slate-700/80 bg-white dark:bg-slate-800">
            <table class="w-full text-xs">
              <thead class="bg-slate-50 dark:bg-slate-900/60 text-slate-600 dark:text-slate-300">
                <tr>
                  <th class="px-3 py-2 text-left font-semibold">Email</th>
                  <th class="px-3 py-2 text-left font-semibold">Nom</th>
                  <th class="px-3 py-2 text-left font-semibold">Groupe</th>
                </tr>
              </thead>
              <tbody class="text-slate-800 dark:text-slate-200">
                <tr
                  v-for="participant in csvPreview.slice(0, 5)"
                  :key="participant.email"
                  class="border-t border-slate-200 dark:border-slate-700/80"
                >
                  <td class="px-3 py-2">{{ participant.email }}</td>
                  <td class="px-3 py-2">{{ participant.name || '-' }}</td>
                  <td class="px-3 py-2">{{ participant.group || '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-if="csvPreview.length > 5" class="q-hint">
            ... et {{ csvPreview.length - 5 }} autres participants
          </p>
        </div>
      </FormSection>

      <!-- Email Template -->
      <FormSection
        v-else-if="activeTab === 'template'"
        :icon="EnvelopeIcon"
        tone="amber"
        title="Message d'invitation"
        subtitle="Personnalisez l'email envoyé aux participants"
      >
        <FormField label="Sujet de l'email" for="invite-subject">
          <input
            id="invite-subject"
            v-model="emailTemplate.subject"
            type="text"
            class="q-input"
            placeholder="Invitation à participer au questionnaire"
          />
        </FormField>

        <FormField label="Corps du message" for="invite-body">
          <textarea id="invite-body" v-model="emailTemplate.body" rows="8" class="q-input" />
        </FormField>

        <div class="q-callout q-callout-info">
          <InformationCircleIcon class="w-4 h-4 shrink-0 mt-0.5" />
          <div class="space-y-1">
            <p class="font-semibold">Variables disponibles</p>
            <p v-for="v in templateVariables" :key="v.code">
              <code class="px-1 py-0.5 rounded bg-white/70 dark:bg-slate-900/60">{{ v.code }}</code> – {{ v.label }}
            </p>
          </div>
        </div>
      </FormSection>

      <!-- Actions -->
      <div class="q-actions !justify-between">
        <span class="text-xs text-slate-600 dark:text-slate-400">
          {{ totalParticipants }} participant{{ totalParticipants !== 1 ? 's' : '' }} à inviter
        </span>
        <div class="flex items-center gap-3">
          <Button severity="secondary" outlined label="Annuler" @click="$emit('close')" />
          <Button
            icon="pi pi-send"
            label="Envoyer les invitations"
            :disabled="totalParticipants === 0"
            @click="sendInvitations"
          />
        </div>
      </div>
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import {
  DocumentArrowUpIcon,
  EnvelopeIcon,
  InformationCircleIcon,
  UserPlusIcon,
  UsersIcon
} from '@heroicons/vue/24/outline';
import { useResponseStore } from '@/stores/responses';
import { useUIStore } from '@/stores/ui';
import { DialogHeader, FormField, FormSection } from '../Form';

interface Props {
  surveyId: string;
}

interface Emits {
  close: [];
  invited: [];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const responseStore = useResponseStore();
const uiStore = useUIStore();

const fileInput = ref<HTMLInputElement | null>(null);
const activeTab = ref('manual');
const manualParticipants = ref([{ email: '', name: '', group: '' }]);
const csvPreview = ref<Array<{ email: string; name?: string; group?: string }>>([]);
const emailTemplate = ref({
  subject: 'Invitation à participer au questionnaire',
  body: `Bonjour {{name}},

Vous êtes invité(e) à participer à notre questionnaire "{{survey_title}}".

Cliquez sur le lien suivant pour commencer : {{link}}

Merci pour votre participation !`
});

const tabs = [
  { id: 'manual', label: 'Saisie manuelle' },
  { id: 'csv', label: 'Import CSV' },
  { id: 'template', label: 'Email' }
];

const templateVariables = [
  { code: '{{name}}', label: 'Nom du participant' },
  { code: '{{email}}', label: 'Email du participant' },
  { code: '{{link}}', label: 'Lien vers le questionnaire' },
  { code: '{{survey_title}}', label: 'Titre du questionnaire' }
];

const totalParticipants = computed(() => {
  if (activeTab.value === 'manual') {
    return manualParticipants.value.filter(p => p.email.trim()).length;
  }
  return csvPreview.value.length;
});

function addManualParticipant() {
  manualParticipants.value.push({ email: '', name: '', group: '' });
}

function removeManualParticipant(index: number) {
  manualParticipants.value.splice(index, 1);
}

function handleFileSelect(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0];
  if (file) {
    parseCSV(file);
  }
}

function handleDrop(event: DragEvent) {
  event.preventDefault();
  const file = event.dataTransfer?.files[0];
  if (file && file.type === 'text/csv') {
    parseCSV(file);
  }
}

function parseCSV(file: File) {
  const reader = new FileReader();
  reader.onload = (e) => {
    const csvText = e.target?.result as string;
    const participants = responseStore.importParticipants(csvText, props.surveyId);
    csvPreview.value = participants.map(p => ({
      email: p.email || '',
      name: p.name,
      group: p.group
    }));
  };
  reader.readAsText(file);
}

function sendInvitations() {
  let participants: Array<{ email: string; name?: string; group?: string }> = [];

  if (activeTab.value === 'manual') {
    participants = manualParticipants.value.filter(p => p.email.trim());
  } else if (activeTab.value === 'csv') {
    participants = csvPreview.value;
  }

  // Create participants
  participants.forEach(p => {
    responseStore.createParticipant(p.email, p.name, p.group);
  });

  uiStore.addNotification(
    'success',
    'Invitations envoyées',
    `${participants.length} invitation${participants.length !== 1 ? 's ont' : ' a'} été envoyée${participants.length !== 1 ? 's' : ''}.`
  );

  emit('invited');
}
</script>
