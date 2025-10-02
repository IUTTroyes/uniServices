<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click="$emit('close')">
    <div
      class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-2xl mx-4 max-h-[80vh] overflow-y-auto"
      @click.stop
    >
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
          Inviter des participants
        </h2>
        <button
          @click="$emit('close')"
          class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
        >
          <XMarkIcon class="w-5 h-5" />
        </button>
      </div>

      <!-- Tabs -->
      <div class="flex space-x-1 p-1 bg-gray-100 dark:bg-gray-700 rounded-lg mb-6">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            'flex-1 py-2 px-4 text-sm font-medium rounded-md transition-colors',
            activeTab === tab.id
              ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm'
              : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
          ]"
        >
          {{ tab.label }}
        </button>
      </div>

      <!-- Manual Invite -->
      <div v-if="activeTab === 'manual'" class="space-y-4">
        <div class="space-y-3">
          <div
            v-for="(participant, index) in manualParticipants"
            :key="index"
            class="flex items-center space-x-3 p-3 border border-gray-200 dark:border-gray-600 rounded-lg"
          >
            <div class="flex-1 grid grid-cols-3 gap-3">
              <input
                v-model="participant.email"
                type="email"
                placeholder="Email"
                class="input-field"
              />
              <input
                v-model="participant.name"
                type="text"
                placeholder="Nom (optionnel)"
                class="input-field"
              />
              <input
                v-model="participant.group"
                type="text"
                placeholder="Groupe (optionnel)"
                class="input-field"
              />
            </div>
            <button
              v-if="manualParticipants.length > 1"
              @click="removeManualParticipant(index)"
              class="p-2 text-red-500 hover:text-red-700 rounded"
            >
              <XMarkIcon class="w-4 h-4" />
            </button>
          </div>
        </div>

        <button
          @click="addManualParticipant"
          class="w-full btn-secondary"
        >
          <PlusIcon class="w-4 h-4" />
          Ajouter un participant
        </button>
      </div>

      <!-- CSV Import -->
      <div v-else-if="activeTab === 'csv'" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Fichier CSV
          </label>
          <div
            @drop="handleDrop"
            @dragover.prevent
            @dragenter.prevent
            class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center hover:border-gray-400 dark:hover:border-gray-500 transition-colors"
          >
            <input
              ref="fileInput"
              type="file"
              accept=".csv"
              @change="handleFileSelect"
              class="hidden"
            />
            <DocumentArrowUpIcon class="w-12 h-12 text-gray-400 mx-auto mb-4" />
            <p class="text-gray-600 dark:text-gray-400 mb-2">
              Glissez votre fichier CSV ici ou
              <button
                @click="$refs.fileInput?.click()"
                class="text-primary-600 dark:text-primary-400 hover:underline"
              >
                cliquez pour sélectionner
              </button>
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">
              Format attendu: email, nom, groupe
            </p>
          </div>
        </div>

        <div v-if="csvPreview.length > 0" class="space-y-3">
          <h4 class="text-sm font-medium text-gray-900 dark:text-white">
            Aperçu ({{ csvPreview.length }} participants)
          </h4>
          <div class="max-h-40 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg">
            <table class="w-full text-sm">
              <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                  <th class="px-3 py-2 text-left">Email</th>
                  <th class="px-3 py-2 text-left">Nom</th>
                  <th class="px-3 py-2 text-left">Groupe</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="participant in csvPreview.slice(0, 5)"
                  :key="participant.email"
                  class="border-t border-gray-200 dark:border-gray-600"
                >
                  <td class="px-3 py-2">{{ participant.email }}</td>
                  <td class="px-3 py-2">{{ participant.name || '-' }}</td>
                  <td class="px-3 py-2">{{ participant.group || '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-if="csvPreview.length > 5" class="text-xs text-gray-500 dark:text-gray-400">
            ... et {{ csvPreview.length - 5 }} autres participants
          </p>
        </div>
      </div>

      <!-- Email Template -->
      <div v-else-if="activeTab === 'template'" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Sujet de l'email
          </label>
          <input
            v-model="emailTemplate.subject"
            type="text"
            class="input-field"
            placeholder="Invitation à participer au questionnaire"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Corps du message
          </label>
          <textarea
            v-model="emailTemplate.body"
            rows="8"
            class="input-field"
            placeholder="Bonjour {{name}},

Vous êtes invité(e) à participer à notre questionnaire...

Cliquez sur le lien suivant pour commencer : {{link}}

Merci pour votre participation !"
          />
        </div>

        <div class="bg-blue-50 dark:bg-blue-900 p-3 rounded-lg">
          <h4 class="text-sm font-medium text-blue-900 dark:text-blue-200 mb-2">
            Variables disponibles
          </h4>
          <div class="text-xs text-blue-700 dark:text-blue-300 space-y-1">
            <p><code>{{name}}</code> - Nom du participant</p>
            <p><code>{{email}}</code> - Email du participant</p>
            <p><code>{{link}}</code> - Lien vers le questionnaire</p>
            <p><code>{{survey_title}}</code> - Titre du questionnaire</p>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-between pt-6 mt-6 border-t border-gray-200 dark:border-gray-700">
        <div class="text-sm text-gray-600 dark:text-gray-400">
          {{ totalParticipants }} participant{{ totalParticipants !== 1 ? 's' : '' }} à inviter
        </div>
        <div class="flex items-center space-x-3">
          <button @click="$emit('close')" class="btn-secondary">
            Annuler
          </button>
          <button @click="sendInvitations" class="btn-primary" :disabled="totalParticipants === 0">
            Envoyer les invitations
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import {
  XMarkIcon,
  PlusIcon,
  DocumentArrowUpIcon
} from '@heroicons/vue/24/outline';
import { useResponseStore } from '@/stores/responses';
import { useUIStore } from '@/stores/ui';

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
