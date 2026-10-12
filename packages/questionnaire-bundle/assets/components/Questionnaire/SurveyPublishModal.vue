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
        :icon="PaperAirplaneIcon"
        title="Confirmation de publication"
        subtitle="Passez en revue les détails avant de lancer la diffusion"
      />
    </template>

    <div class="q-form">
      <p class="text-sm text-slate-600 dark:text-slate-400">
        Vous êtes sur le point de publier le questionnaire
        <span class="font-semibold text-slate-900 dark:text-white">"{{ survey?.title }}"</span>.
      </p>

      <!-- Dates Section -->
      <FormSection :icon="CalendarDaysIcon" tone="blue" title="Dates de diffusion">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div class="p-3 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80">
            <span class="q-label !mb-0.5">Date de début</span>
            <span class="text-sm font-medium text-slate-900 dark:text-white">{{ formatDate(survey?.openingDate) }}</span>
          </div>
          <div class="p-3 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80">
            <span class="q-label !mb-0.5">Date de fin</span>
            <span class="text-sm font-medium text-slate-900 dark:text-white">{{ formatDate(survey?.closingDate) }}</span>
          </div>
        </div>
      </FormSection>

      <!-- KPIs / Statistics Section -->
      <FormSection :icon="ChartPieIcon" tone="purple" title="Aperçu de la structure">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-center">
          <div v-for="kpi in kpis" :key="kpi.label" class="p-3 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80">
            <span :class="['block font-bold text-primary-600 dark:text-primary-400', kpi.small ? 'text-sm mt-1.5' : 'text-2xl']">{{ kpi.value }}</span>
            <span class="text-[11px] text-slate-500 dark:text-slate-400">{{ kpi.label }}</span>
          </div>
        </div>
      </FormSection>

      <!-- Recipients Section -->
      <FormSection :icon="UsersIcon" tone="emerald" title="Destinataires / Participants">
        <div class="q-tabs">
          <button
            v-for="mode in modes"
            :key="mode.id"
            type="button"
            :class="['q-tab', activeMode === mode.id && 'q-tab-active']"
            @click="selectMode(mode.id)"
          >
            {{ mode.label }}
          </button>
        </div>

        <!-- 1. MANUAL MODE -->
        <div v-if="activeMode === 'manual'">
          <div class="flex items-center justify-between gap-3">
            <label for="publish-emails" class="q-label">
              Adresses email (une par ligne, ou séparées par des virgules)
            </label>
            <button
              v-if="existingParticipants.length > 0 && !hasImported"
              type="button"
              class="text-xs font-semibold text-primary-600 dark:text-primary-400 hover:underline border-0 bg-transparent cursor-pointer mb-1.5"
              @click="importExistingParticipants"
            >
              Importer existants ({{ existingParticipants.length }})
            </button>
          </div>
          <textarea
            id="publish-emails"
            v-model="rawEmails"
            rows="4"
            class="q-input"
            placeholder="invite1@example.com&#10;invite2@example.com"
          />
        </div>

        <!-- 2. PERSONNEL MODE -->
        <div v-else-if="activeMode === 'personnels'" class="space-y-3">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <FormField label="Type de personnel" for="publish-pers-type">
              <select id="publish-pers-type" v-model="personnelFilterType" class="q-input">
                <option value="all">Tous les personnels</option>
                <option value="permanent">Permanents</option>
                <option value="vacataire">Vacataires</option>
              </select>
            </FormField>
            <FormField label="Par statut précis" for="publish-pers-statut">
              <select id="publish-pers-statut" v-model="personnelFilterStatut" class="q-input">
                <option value="">Tous les statuts</option>
                <option v-for="st in statuses" :key="st" :value="st">{{ st }}</option>
              </select>
            </FormField>
          </div>
          <p v-if="isLoadingData" class="q-hint">Chargement des personnels...</p>
          <p v-else class="text-xs text-slate-700 dark:text-slate-300">
            <strong>{{ filteredPersonnels.length }}</strong> personnels sélectionnés.
          </p>
        </div>

        <!-- 3. STUDENT MODE -->
        <div v-else-if="activeMode === 'etudiants'" class="space-y-3">
          <FormField label="Sélectionnez le semestre" for="publish-semestre">
            <select id="publish-semestre" v-model="selectedSemestre" class="q-input" @change="loadSemesterStudents">
              <option value="">Choisir un semestre...</option>
              <option v-for="sem in semesters" :key="sem.id" :value="sem.id">{{ sem.libelle }}</option>
            </select>
          </FormField>
          <p v-if="isLoadingData" class="q-hint">Chargement des étudiants...</p>
          <p v-else-if="selectedSemestre" class="text-xs text-slate-700 dark:text-slate-300">
            <strong>{{ filteredStudents.length }}</strong> étudiants sélectionnés pour ce semestre.
          </p>
        </div>

        <!-- Email Count Indicators -->
        <div class="flex flex-wrap items-center gap-4 text-xs pt-3 border-t border-slate-200 dark:border-slate-700/80">
          <span class="flex items-center text-slate-600 dark:text-slate-400">
            Total détectés : <strong class="ms-1">{{ activeMode === 'manual' ? emailsList.length : validEmails.length }}</strong>
          </span>
          <span v-if="validEmails.length > 0" class="flex items-center text-emerald-600 dark:text-emerald-400">
            <CheckCircleIcon class="w-4 h-4 me-1" />
            Valides : <strong class="ms-1">{{ validEmails.length }}</strong>
          </span>
          <span v-if="activeMode === 'manual' && invalidEmails.length > 0" class="flex items-center text-red-600 dark:text-red-400">
            <ExclamationTriangleIcon class="w-4 h-4 me-1" />
            Invalides (ignorés) : <strong class="ms-1">{{ invalidEmails.length }}</strong>
          </span>
        </div>
      </FormSection>

      <!-- Warning Alert -->
      <div class="q-callout q-callout-warning">
        <ExclamationTriangleIcon class="w-4 h-4 shrink-0 mt-0.5" />
        <div>
          <p class="font-semibold">Attention</p>
          <p class="mt-0.5 opacity-90">
            La publication figera définitivement la structure du questionnaire (sections et questions). Vous ne pourrez plus y apporter de modifications structurelles.
          </p>
        </div>
      </div>

      <!-- Confirmation -->
      <ToggleCard
        v-model="isConfirmed"
        control="checkbox"
        tone="emerald"
        :icon="CheckBadgeIcon"
        title="Je confirme vouloir publier ce questionnaire"
        description="et lancer sa diffusion aux destinataires."
      />

      <div class="q-actions">
        <Button severity="secondary" outlined type="button" label="Annuler" @click="$emit('close')" />
        <Button
          type="button"
          icon="pi pi-send"
          label="Confirmer et Publier"
          :disabled="!isConfirmed"
          @click="submitPublish"
        />
      </div>
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';
import {
  CalendarDaysIcon,
  UsersIcon,
  ChartPieIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon,
  CheckBadgeIcon,
  PaperAirplaneIcon
} from '@heroicons/vue/24/outline';
import type { Survey } from '@types';
import { useResponseStore } from '@/stores/responses';
import { 
  getMiniSemestres, 
  getAllStatuses, 
  getAllPersonnels, 
  getStudentSemestres 
} from '@/requests/questionnaire_services/questionnaireService';
import { DialogHeader, FormField, FormSection, ToggleCard } from '../Form';

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

const kpis = computed(() => [
  { label: 'Sections', value: sectionsCount.value },
  { label: 'Questions', value: questionsCount.value },
  { label: 'Dyna.', value: configurableSectionsCount.value },
  { label: 'Anonyme', value: isAnonymous.value ? 'Oui' : 'Non', small: true }
]);

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
