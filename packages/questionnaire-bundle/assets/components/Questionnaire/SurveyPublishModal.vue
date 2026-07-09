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
        </div>

        <!-- Mode Tabs -->
        <div class="mb-4 bg-gray-200/80 dark:bg-gray-800 p-1 rounded-xl flex gap-1 shadow-inner border border-gray-300/30 dark:border-gray-700/30">
          <button
            v-for="mode in modes"
            :key="mode.id"
            type="button"
            @click="selectMode(mode.id)"
            :class="[
              'flex-1 flex items-center justify-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold transition-all duration-200 border-0 cursor-pointer',
              activeMode === mode.id
                ? 'bg-white dark:bg-gray-700 text-primary-600 dark:text-white shadow-md'
                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 bg-transparent'
            ]"
          >
            {{ mode.label }}
          </button>
        </div>

        <div class="space-y-3">
          <!-- 1. MANUAL MODE -->
          <div v-if="activeMode === 'manual'" class="space-y-2">
            <div class="flex items-center justify-between">
              <label class="block text-xs text-gray-650 dark:text-gray-400">
                Saisissez les adresses email (un email par ligne, ou séparés par des virgules) :
              </label>
              <button v-if="existingParticipants.length > 0 && !hasImported" 
                      type="button" 
                      @click="importExistingParticipants"
                      class="text-xs text-primary-600 dark:text-primary-400 hover:underline border-0 bg-transparent cursor-pointer">
                Importer existants ({{ existingParticipants.length }})
              </button>
            </div>
            <textarea
              v-model="rawEmails"
              rows="4"
              class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 focus:ring-2 focus:ring-primary-500 focus:outline-none"
              placeholder="invite1@example.com&#10;invite2@example.com"
            />
          </div>

          <!-- 2. PERSONNEL MODE -->
          <div v-else-if="activeMode === 'personnels'" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1.5 font-medium">Type de personnel</label>
                <select v-model="personnelFilterType" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-primary-500">
                  <option value="all">Tous les personnels</option>
                  <option value="permanent">Permanents</option>
                  <option value="vacataire">Vacataires</option>
                </select>
              </div>
              <div>
                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1.5 font-medium">Par statut précis</label>
                <select v-model="personnelFilterStatut" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-primary-500">
                  <option value="">Tous les statuts</option>
                  <option v-for="st in statuses" :key="st" :value="st">{{ st }}</option>
                </select>
              </div>
            </div>
            <div v-if="isLoadingData" class="text-xs text-gray-550 dark:text-gray-400">
              Chargement des personnels...
            </div>
            <div v-else class="text-xs text-gray-700 dark:text-gray-300">
              <strong>{{ filteredPersonnels.length }}</strong> personnels sélectionnés.
            </div>
          </div>

          <!-- 3. STUDENT MODE -->
          <div v-else-if="activeMode === 'etudiants'" class="space-y-4">
            <div>
              <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1.5 font-medium">Sélectionnez le semestre</label>
              <select v-model="selectedSemestre" @change="loadSemesterStudents" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-primary-500">
                <option value="">Choisir un semestre...</option>
                <option v-for="sem in semesters" :key="sem.id" :value="sem.id">{{ sem.libelle }}</option>
              </select>
            </div>
            <div v-if="isLoadingData" class="text-xs text-gray-550 dark:text-gray-400">
              Chargement des étudiants...
            </div>
            <div v-else-if="selectedSemestre" class="text-xs text-gray-700 dark:text-gray-300">
              <strong>{{ filteredStudents.length }}</strong> étudiants sélectionnés pour ce semestre.
            </div>
          </div>

          <!-- Email Count Indicators -->
          <div class="flex flex-wrap items-center gap-4 text-xs mt-2 border-t border-gray-200/50 dark:border-gray-700/50 pt-2">
            <span class="flex items-center text-gray-600 dark:text-gray-400">
              Total détectés : <strong class="ms-1">{{ activeMode === 'manual' ? emailsList.length : validEmails.length }}</strong>
            </span>
            <span v-if="validEmails.length > 0" class="flex items-center text-green-600 dark:text-green-400">
              <CheckCircleIcon class="w-4 h-4 me-1" />
              Valides : <strong class="ms-1">{{ validEmails.length }}</strong>
            </span>
            <span v-if="activeMode === 'manual' && invalidEmails.length > 0" class="flex items-center text-red-600 dark:text-red-400">
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
import { 
  getMiniSemestres, 
  getAllStatuses, 
  getAllPersonnels, 
  getStudentSemestres 
} from '@/requests/questionnaire_services/questionnaireService';

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

const activeMode = ref('manual'); // 'manual' | 'personnels' | 'etudiants'
const modes = [
  { id: 'manual', label: 'Mails à saisir' },
  { id: 'personnels', label: 'Personnels' },
  { id: 'etudiants', label: 'Étudiants' }
];

const semesters = ref<any[]>([]);
const statuses = ref<string[]>([]);
const personnels = ref<any[]>([]);
const students = ref<any[]>([]);

const selectedSemestre = ref('');
const personnelFilterType = ref('all'); // 'all' | 'permanent' | 'vacataire'
const personnelFilterStatut = ref('');
const isLoadingData = ref(false);

async function selectMode(mode: string) {
  activeMode.value = mode;
  if (mode === 'personnels' && personnels.value.length === 0) {
    isLoadingData.value = true;
    try {
      const [resPers, resStats] = await Promise.all([
        getAllPersonnels(),
        getAllStatuses()
      ]);
      personnels.value = resPers?.member || resPers || [];
      statuses.value = resStats || [];
    } catch (e) {
      console.error(e);
    } finally {
      isLoadingData.value = false;
    }
  } else if (mode === 'etudiants' && semesters.value.length === 0) {
    isLoadingData.value = true;
    try {
      const resSem = await getMiniSemestres();
      semesters.value = resSem?.member || resSem || [];
    } catch (e) {
      console.error(e);
    } finally {
      isLoadingData.value = false;
    }
  }
}

async function loadSemesterStudents() {
  if (!selectedSemestre.value) {
    students.value = [];
    return;
  }
  isLoadingData.value = true;
  try {
    const resStud = await getStudentSemestres(selectedSemestre.value);
    students.value = resStud?.member || resStud || [];
  } catch (e) {
    console.error(e);
    students.value = [];
  } finally {
    isLoadingData.value = false;
  }
}

const filteredPersonnels = computed(() => {
  return personnels.value.filter(p => {
    if (personnelFilterType.value === 'vacataire') {
      if (p.statut !== 'vacataire') return false;
    } else if (personnelFilterType.value === 'permanent') {
      if (p.statut === 'vacataire') return false;
    }
    if (personnelFilterStatut.value && p.statut !== personnelFilterStatut.value) {
      return false;
    }
    return true;
  });
});

const filteredStudents = computed(() => {
  return students.value;
});

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
  if (activeMode.value === 'manual') {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailsList.value.filter(email => emailRegex.test(email));
  } else if (activeMode.value === 'personnels') {
    return filteredPersonnels.value.map(p => p.mailUniv).filter(Boolean);
  } else if (activeMode.value === 'etudiants') {
    return filteredStudents.value.map(s => s.etudiant?.mailUniv).filter(Boolean);
  }
  return [];
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
