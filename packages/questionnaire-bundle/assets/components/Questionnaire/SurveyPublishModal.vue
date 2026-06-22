<template>
  <Dialog header="Confirmation de publication"
          :style="{ width: '50vw', minWidth: '450px' }"
          :visible="true"
          @update:visible="$emit('close')"
          :modal="true" :closable="true">

    <div class="bg-white dark:bg-gray-800 rounded-xl p-2 w-full max-h-[80vh] overflow-y-auto" @click.stop>
      <div class="mb-6">
        <p class="text-gray-600 dark:text-gray-400 text-sm">
          Vous êtes sur le point de publier le questionnaire <span class="font-semibold text-gray-900 dark:text-white">"{{ survey?.title }}"</span>. 
          Veuillez passer en revue les détails de publication ci-dessous.
        </p>
      </div>

      <!-- Dates Section -->
      <div class="mb-6 bg-gray-50 dark:bg-gray-700/50 p-4 rounded-xl border border-gray-100 dark:border-gray-700">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center mb-3">
          <CalendarIcon class="w-5 h-5 text-primary-500 me-2" />
          Dates de diffusion
        </h3>
        <div class="grid grid-cols-2 gap-4 text-sm">
          <div>
            <span class="block text-gray-500 dark:text-gray-400 text-xs">Date de début</span>
            <span class="font-medium text-gray-900 dark:text-white">
              {{ formatDate(survey?.openingDate) }}
            </span>
          </div>
          <div>
            <span class="block text-gray-500 dark:text-gray-400 text-xs">Date de fin</span>
            <span class="font-medium text-gray-900 dark:text-white">
              {{ formatDate(survey?.closingDate) }}
            </span>
          </div>
        </div>
      </div>

      <!-- KPIs / Statistics Section -->
      <div class="mb-6 bg-gray-50 dark:bg-gray-700/50 p-4 rounded-xl border border-gray-100 dark:border-gray-700">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center mb-3">
          <ChartPieIcon class="w-5 h-5 text-primary-500 me-2" />
          Aperçu de la structure (KPIs)
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
          <div class="bg-white dark:bg-gray-800 p-3 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
            <span class="block text-2xl font-bold text-primary-600">{{ sectionsCount }}</span>
            <span class="text-xs text-gray-500 dark:text-gray-400">Sections</span>
          </div>
          <div class="bg-white dark:bg-gray-800 p-3 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
            <span class="block text-2xl font-bold text-primary-600">{{ questionsCount }}</span>
            <span class="text-xs text-gray-500 dark:text-gray-400">Questions</span>
          </div>
          <div class="bg-white dark:bg-gray-800 p-3 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
            <span class="block text-2xl font-bold text-primary-600">{{ configurableSectionsCount }}</span>
            <span class="text-xs text-gray-500 dark:text-gray-400">Dyna.</span>
          </div>
          <div class="bg-white dark:bg-gray-800 p-3 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
            <span class="block text-sm font-bold text-gray-800 dark:text-white mt-1.5">{{ isAnonymous ? 'Oui' : 'Non' }}</span>
            <span class="text-xs text-gray-500 dark:text-gray-400">Anonyme</span>
          </div>
        </div>
      </div>

      <!-- Recipients Section -->
      <div class="mb-6 bg-gray-50 dark:bg-gray-700/50 p-4 rounded-xl border border-gray-100 dark:border-gray-700">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center">
            <UsersIcon class="w-5 h-5 text-primary-500 me-2" />
            Destinataires / Participants
          </h3>
          <button v-if="existingParticipants.length > 0 && !hasImported" 
                  type="button" 
                  @click="importExistingParticipants"
                  class="text-xs text-primary-600 dark:text-primary-400 hover:underline">
            Importer les participants existants ({{ existingParticipants.length }})
          </button>
        </div>

        <div class="space-y-3">
          <label class="block text-xs text-gray-600 dark:text-gray-400">
            Saisissez les adresses email des destinataires (un email par ligne, ou séparés par des virgules) :
          </label>
          <textarea
            v-model="rawEmails"
            rows="4"
            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 focus:ring-2 focus:ring-primary-500 focus:outline-none"
            placeholder="invite1@example.com&#10;invite2@example.com"
          />

          <!-- Email Count Indicators -->
          <div class="flex flex-wrap items-center gap-4 text-xs mt-2">
            <span class="flex items-center text-gray-600 dark:text-gray-400">
              Total détectés : <strong class="ms-1">{{ emailsList.length }}</strong>
            </span>
            <span v-if="validEmails.length > 0" class="flex items-center text-green-600 dark:text-green-400">
              <CheckCircleIcon class="w-4 h-4 me-1" />
              Valides : <strong class="ms-1">{{ validEmails.length }}</strong>
            </span>
            <span v-if="invalidEmails.length > 0" class="flex items-center text-red-600 dark:text-red-400">
              <ExclamationTriangleIcon class="w-4 h-4 me-1" />
              Invalides (ignorés) : <strong class="ms-1">{{ invalidEmails.length }}</strong>
            </span>
          </div>
        </div>
      </div>

      <!-- Warning Alert -->
      <div class="mb-6 bg-amber-50 dark:bg-amber-950/30 p-4 rounded-xl border border-amber-200 dark:border-amber-900/50 text-amber-800 dark:text-amber-300 text-sm flex items-start">
        <ExclamationTriangleIcon class="w-5 h-5 me-3 flex-shrink-0 mt-0.5 text-amber-500" />
        <div>
          <h4 class="font-semibold mb-1">Attention</h4>
          <p class="text-xs">
            La publication figera définitivement la structure du questionnaire (sections et questions). Vous ne pourrez plus y apporter de modifications structurelles.
          </p>
        </div>
      </div>

      <!-- Confirmation Checkbox -->
      <div class="mb-6 flex items-start">
        <input
          id="confirmCheckbox"
          v-model="isConfirmed"
          type="checkbox"
          class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
        />
        <label for="confirmCheckbox" class="ml-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer select-none">
          Je confirme vouloir publier ce questionnaire et lancer sa diffusion aux destinataires.
        </label>
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-100 dark:border-gray-700">
        <Button
          severity="secondary"
          type="button" 
          @click="$emit('close')">
          Annuler
        </Button>
        <Button 
          type="button"
          severity="primary"
          :disabled="!isConfirmed"
          @click="submitPublish">
          Confirmer et Publier
        </Button>
      </div>
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';
import {
  CalendarIcon,
  UsersIcon,
  ChartPieIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon
} from '@heroicons/vue/24/outline';
import type { Survey } from '@types';
import { useResponseStore } from '@/stores/responses';

interface Props {
  survey: Survey | null;
}

interface Emits {
  close: [];
  confirm: [recipients: string[]];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const responseStore = useResponseStore();

const rawEmails = ref('');
const isConfirmed = ref(false);
const hasImported = ref(false);

// Date formatter helper
function formatDate(date: any): string {
  if (!date) return 'Non configurée (début immédiat / permanent)';
  const d = new Date(date);
  if (isNaN(d.getTime())) return 'Non configurée (début immédiat / permanent)';
  return format(d, 'dd MMMM yyyy à HH:mm', { locale: fr });
}

// Structure KPIs
const sectionsCount = computed(() => props.survey?.sections?.length || 0);
const questionsCount = computed(() => {
  if (!props.survey?.sections) return 0;
  return props.survey.sections.reduce((sum, section) => sum + (section.questions?.length || 0), 0);
});
const configurableSectionsCount = computed(() => {
  if (!props.survey?.sections) return 0;
  return props.survey.sections.filter(s => s.typeSection === 'configurable').length;
});
const isAnonymous = computed(() => props.survey?.opt?.anonymous ?? false);

// Recipients calculations
const emailsList = computed(() => {
  return rawEmails.value
    .split(/[\n,;]+/)
    .map(email => email.trim())
    .filter(email => email !== '');
});

const validEmails = computed(() => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailsList.value.filter(email => emailRegex.test(email));
});

const invalidEmails = computed(() => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailsList.value.filter(email => !emailRegex.test(email));
});

// Load existing participants in the system
const existingParticipants = computed(() => {
  return responseStore.participants || [];
});

function importExistingParticipants() {
  const emails = existingParticipants.value
    .map(p => p.email)
    .filter((email): email is string => !!email);
  
  if (emails.length > 0) {
    if (rawEmails.value.trim() !== '') {
      rawEmails.value += '\n' + emails.join('\n');
    } else {
      rawEmails.value = emails.join('\n');
    }
    hasImported.value = true;
  }
}

function submitPublish() {
  if (isConfirmed.value) {
    emit('confirm', validEmails.value);
  }
}
</script>
