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
      <FormSection
        :icon="CalendarDaysIcon"
        tone="blue"
        title="Dates de diffusion"
        subtitle="Ajustez les dates d'ouverture et de fermeture du questionnaire"
      >
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <FormField
            label="Date et heure d'ouverture"
            for="publish-opening-date"
            hint="Laissez vide pour ouverture immédiate à la publication"
          >
            <input
              id="publish-opening-date"
              v-model="openingDate"
              type="datetime-local"
              class="q-input"
            />
          </FormField>
          <FormField
            label="Date et heure de fermeture"
            for="publish-closing-date"
            hint="Laissez vide pour aucune date limite de fermeture"
            :error="dateError"
          >
            <input
              id="publish-closing-date"
              v-model="closingDate"
              type="datetime-local"
              class="q-input"
              :class="{ '!border-red-500': dateError }"
            />
          </FormField>
        </div>

        <div v-if="dateError" class="text-xs text-red-600 dark:text-red-400 mt-1 flex items-center gap-1.5">
          <ExclamationTriangleIcon class="w-4 h-4 shrink-0" />
          <span>{{ dateError }}</span>
        </div>

        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
          Ces dates sont éditables ici et mettent à jour la configuration globale du questionnaire.
        </p>
      </FormSection>

      <!-- KPIs / Statistics Section -->
      <FormSection :icon="ChartPieIcon" tone="purple" title="Aperçu de la structure">
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3 text-center">
          <div
            v-for="kpi in kpis"
            :key="kpi.label"
            class="p-3 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80"
          >
            <span :class="['block font-bold text-primary-600 dark:text-primary-400', kpi.small ? 'text-lg mt-0.5' : 'text-2xl']">
              {{ kpi.value }}
            </span>
            <span class="text-[11px] font-medium text-slate-700 dark:text-slate-300 block">
              {{ kpi.label }}
            </span>
            <span v-if="kpi.detail" class="block text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate">
              {{ kpi.detail }}
            </span>
          </div>
        </div>

        <!-- Alerte informative si sections dynamiques -->
        <div
          v-if="configurableSectionsCount > 0"
          class="mt-2.5 text-xs text-purple-700 dark:text-purple-300 bg-purple-50 dark:bg-purple-950/40 p-2.5 rounded-lg border border-purple-200 dark:border-purple-800/60 flex items-center gap-2"
        >
          <InformationCircleIcon class="w-4 h-4 shrink-0 text-purple-600 dark:text-purple-400" />
          <span>
            Ce questionnaire comporte <strong>{{ configurableSectionsCount }} section{{ configurableSectionsCount > 1 ? 's' : '' }} dynamique{{ configurableSectionsCount > 1 ? 's' : '' }}</strong> répétant les questions pour <strong>{{ totalDynamicElements }} élément{{ totalDynamicElements > 1 ? 's' : '' }}</strong> (chaque élément génère une section distincte pour les participants).
          </span>
        </div>

        <!-- Alerte si questionnaire sans questions -->
        <div
          v-if="uniqueQuestionsCount === 0"
          class="mt-2.5 text-xs text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/40 p-2.5 rounded-lg border border-amber-200 dark:border-amber-800/60 flex items-center gap-2"
        >
          <ExclamationTriangleIcon class="w-4 h-4 shrink-0 text-amber-600 dark:text-amber-400" />
          <span>
            Le questionnaire ne comporte actuellement aucune question. Veuillez ajouter des questions avant de le publier.
          </span>
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
        <div v-if="activeMode === 'manual'" class="space-y-2.5">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <label for="publish-emails" class="q-label !mb-0">
              Adresses email (une par ligne, ou séparées par des virgules)
            </label>
            <div class="flex items-center gap-2">
              <button
                v-if="existingParticipants.length > 0 && !hasImported"
                type="button"
                class="text-xs font-semibold text-primary-600 dark:text-primary-400 hover:underline border-0 bg-transparent cursor-pointer"
                @click="importExistingParticipants"
              >
                Importer existants ({{ existingParticipants.length }})
              </button>

              <label class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-lg text-primary-700 dark:text-primary-300 bg-primary-50 dark:bg-primary-950/60 hover:bg-primary-100 dark:hover:bg-primary-900/60 border border-primary-200 dark:border-primary-800/80 cursor-pointer transition-colors shadow-2xs">
                <ArrowUpTrayIcon class="w-3.5 h-3.5 shrink-0" />
                <span>Importer CSV / XLSX</span>
                <input
                  type="file"
                  accept=".csv,.xlsx,.xls,.tsv,.txt"
                  class="hidden"
                  @change="handleFileUpload"
                />
              </label>
            </div>
          </div>

          <textarea
            id="publish-emails"
            v-model="rawEmails"
            rows="5"
            class="q-input"
            placeholder="invite1@example.com&#10;invite2@example.com&#10;ou importez un fichier CSV / Excel..."
          />

          <!-- Success/Error banner for import -->
          <div
            v-if="importMessage"
            :class="[
              'text-xs p-2.5 rounded-lg border flex items-center justify-between gap-2 transition-all',
              importMessage.type === 'success'
                ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/60'
                : 'bg-red-50 dark:bg-red-950/40 text-red-700 dark:text-red-300 border-red-200 dark:border-red-800/60'
            ]"
          >
            <div class="flex items-center gap-2">
              <CheckCircleIcon v-if="importMessage.type === 'success'" class="w-4 h-4 shrink-0" />
              <ExclamationTriangleIcon v-else class="w-4 h-4 shrink-0" />
              <span>{{ importMessage.text }}</span>
            </div>
            <button
              type="button"
              class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 border-0 bg-transparent cursor-pointer p-0.5"
              @click="importMessage = null"
            >
              <i class="pi pi-times text-xs"></i>
            </button>
          </div>
        </div>

        <!-- 2. PERSONNEL MODE -->
        <div v-else-if="activeMode === 'personnels'" class="space-y-3">
          <div v-if="surveyDept" class="text-xs text-primary-700 dark:text-primary-300 bg-primary-50 dark:bg-primary-950/40 p-2.5 rounded-lg border border-primary-200 dark:border-primary-800/60 flex items-center gap-2">
            <BuildingOfficeIcon class="w-4 h-4 shrink-0 text-primary-600 dark:text-primary-400" />
            <span>Personnels filtrés pour le département <strong>{{ surveyDept.libelle || surveyDept.id }}</strong></span>
          </div>

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
          <div v-if="surveyDept" class="text-xs text-primary-700 dark:text-primary-300 bg-primary-50 dark:bg-primary-950/40 p-2.5 rounded-lg border border-primary-200 dark:border-primary-800/60 flex items-center gap-2">
            <BuildingOfficeIcon class="w-4 h-4 shrink-0 text-primary-600 dark:text-primary-400" />
            <span>Semestres préselectionnés pour le département <strong>{{ surveyDept.libelle || surveyDept.id }}</strong></span>
          </div>

          <div>
            <div class="flex items-center justify-between gap-2 mb-2">
              <label class="q-label !mb-0">Semestres cibles</label>
              <div v-if="semesters.length > 0" class="flex items-center gap-3 text-xs">
                <button type="button" class="font-semibold text-primary-600 dark:text-primary-400 hover:underline border-0 bg-transparent cursor-pointer" @click="selectAllTargetSemestres">
                  Tout sélectionner
                </button>
                <span class="text-slate-300 dark:text-slate-600">|</span>
                <button type="button" class="text-slate-500 dark:text-slate-400 hover:underline border-0 bg-transparent cursor-pointer" @click="clearTargetSemestres">
                  Effacer
                </button>
              </div>
            </div>

            <div v-if="semesters.length === 0 && isLoadingData" class="q-hint">
              Chargement des semestres...
            </div>
            <div v-else-if="semesters.length > 0" class="flex flex-wrap items-center gap-2 p-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-white dark:bg-slate-800">
              <label
                v-for="sem in semesters"
                :key="sem.id"
                :class="[
                  'px-3 py-1.5 rounded-lg border text-xs font-medium cursor-pointer transition-all flex items-center gap-2 select-none',
                  selectedSemestres.includes(sem.id)
                    ? 'bg-primary-50 dark:bg-primary-950/60 border-primary-300 dark:border-primary-700 text-primary-700 dark:text-primary-300 font-semibold'
                    : 'bg-slate-50 dark:bg-slate-900/40 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:border-slate-300'
                ]"
              >
                <input
                  type="checkbox"
                  :value="sem.id"
                  v-model="selectedSemestres"
                  @change="loadSemesterStudents"
                  class="q-check !w-3.5 !h-3.5"
                />
                <span>{{ sem.libelle }}</span>
              </label>
            </div>
            <p v-else class="text-xs text-slate-500 py-4 text-center border border-dashed border-slate-300 dark:border-slate-700 rounded-xl">
              Aucun semestre trouvé.
            </p>
          </div>

          <p v-if="isLoadingData" class="q-hint">Chargement des étudiants...</p>
          <p v-else-if="selectedSemestres.length > 0" class="text-xs text-slate-700 dark:text-slate-300">
            <strong>{{ filteredStudents.length }}</strong> étudiants sélectionnés ({{ selectedSemestres.length }} semestre{{ selectedSemestres.length > 1 ? 's' : '' }}).
          </p>
        </div>

        <!-- Email Count Indicators -->
        <div class="flex flex-wrap items-center gap-4 text-xs pt-3 border-t border-slate-200 dark:border-slate-700/80">
          <span class="flex items-center text-slate-600 dark:text-slate-400">
            Total détectés : <strong class="ms-1">{{ totalRecipientsCount }}</strong>
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
          :disabled="!isConfirmed || !!dateError || uniqueQuestionsCount === 0"
          @click="submitPublish"
        />
      </div>
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import {
  CalendarDaysIcon,
  UsersIcon,
  ChartPieIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
  CheckCircleIcon,
  CheckBadgeIcon,
  PaperAirplaneIcon,
  BuildingOfficeIcon,
  ArrowUpTrayIcon
} from '@heroicons/vue/24/outline';
import * as XLSX from 'xlsx';
import type { Survey, Section } from '@types';
import { useSurveyStore } from '@/stores/survey';
import { useResponseStore } from '@/stores/responses';
import { 
  getMiniSemestres, 
  getAllStatuses, 
  getAllPersonnels, 
  getStudentSemestres 
} from '@/requests/questionnaire_services/questionnaireService';
import { getDepartementSemestresService } from '@requests';
import { DialogHeader, FormField, FormSection, ToggleCard } from '../Form';
import { calculateEstimatedTime, formatEstimatedTime } from '@/utils/survey';

interface Props {
  survey: Survey | null;
  sections?: Section[];
  estimatedTime?: number;
}

export interface PublishConfirmPayload {
  recipients: string[];
  openingDate?: string | null;
  closingDate?: string | null;
}

interface Emits {
  close: [];
  confirm: [payload: PublishConfirmPayload];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const surveyStore = useSurveyStore();
const responseStore = useResponseStore();

// Date editing state linked with configuration
function toInputDateTime(val: any): string {
  if (!val) return '';
  const d = val instanceof Date ? val : new Date(val);
  if (isNaN(d.getTime())) return '';
  const pad = (n: number) => String(n).padStart(2, '0');
  const year = d.getFullYear();
  const month = pad(d.getMonth() + 1);
  const day = pad(d.getDate());
  const hours = pad(d.getHours());
  const minutes = pad(d.getMinutes());
  return `${year}-${month}-${day}T${hours}:${minutes}`;
}

const openingDate = ref(toInputDateTime(props.survey?.openingDate));
const closingDate = ref(toInputDateTime(props.survey?.closingDate));

watch(
  () => props.survey,
  (s) => {
    if (s) {
      openingDate.value = toInputDateTime(s.openingDate);
      closingDate.value = toInputDateTime(s.closingDate);
    } else {
      openingDate.value = '';
      closingDate.value = '';
    }
  },
  { immediate: true }
);

const dateError = computed(() => {
  if (openingDate.value && closingDate.value) {
    const oTime = new Date(openingDate.value).getTime();
    const cTime = new Date(closingDate.value).getTime();
    if (!isNaN(oTime) && !isNaN(cTime) && cTime <= oTime) {
      return 'La date de fermeture doit être postérieure à la date d\'ouverture.';
    }
  }
  return '';
});

// Sections & Structure KPIs
const effectiveSections = computed<Section[]>(() => {
  if (props.sections && props.sections.length > 0) return props.sections;
  if (props.survey?.sections && props.survey.sections.length > 0) return props.survey.sections;
  return (surveyStore.currentSections as Section[]) || [];
});

const baseSectionsCount = computed(() => effectiveSections.value.length);

const configurableSections = computed(() => {
  return effectiveSections.value.filter(s => s.typeSection === 'configurable');
});

const configurableSectionsCount = computed(() => configurableSections.value.length);

const totalDynamicElements = computed(() => {
  return configurableSections.value.reduce((sum, s) => sum + (s.opt?.elements?.length || 0), 0);
});

const totalGeneratedSectionsCount = computed(() => {
  return effectiveSections.value.reduce((sum, s) => {
    if (s.typeSection === 'configurable') {
      return sum + (s.opt?.elements?.length || 0);
    }
    return sum + 1;
  }, 0);
});

const uniqueQuestionsCount = computed(() => {
  return effectiveSections.value.reduce((sum, s) => sum + (s.questions?.length || 0), 0);
});

const totalQuestionsCount = computed(() => {
  return effectiveSections.value.reduce((sum, s) => {
    const qCount = s.questions?.length || 0;
    if (s.typeSection === 'configurable') {
      const elCount = s.opt?.elements?.length || 0;
      return sum + (qCount * elCount);
    }
    return sum + qCount;
  }, 0);
});

const estimatedSeconds = computed(() => {
  if (props.estimatedTime !== undefined) {
    return props.estimatedTime;
  }
  if (effectiveSections.value.length > 0) {
    return calculateEstimatedTime(effectiveSections.value);
  }
  return props.survey?.estimatedTime || 0;
});

const formattedEstimatedTime = computed(() => {
  return formatEstimatedTime(estimatedSeconds.value);
});

const isAnonymous = computed(() => props.survey?.opt?.anonymous ?? false);

const kpis = computed(() => [
  {
    label: 'Sections',
    value: configurableSectionsCount.value > 0 ? totalGeneratedSectionsCount.value : baseSectionsCount.value,
    detail: configurableSectionsCount.value > 0 ? `${baseSectionsCount.value} dans le modèle` : 'Sections fixes',
  },
  {
    label: 'Questions',
    value: configurableSectionsCount.value > 0 ? totalQuestionsCount.value : uniqueQuestionsCount.value,
    detail: configurableSectionsCount.value > 0 ? `${uniqueQuestionsCount.value} uniques` : 'Au total',
  },
  {
    label: 'Sections dyna.',
    value: configurableSectionsCount.value,
    detail: configurableSectionsCount.value > 0 ? `${totalDynamicElements.value} éléments` : 'Aucune',
  },
  {
    label: 'Temps estimé',
    value: formattedEstimatedTime.value,
    detail: 'Durée de réponse',
    small: true,
  },
  {
    label: 'Anonymat',
    value: isAnonymous.value ? 'Anonyme' : 'Nominatif',
    detail: isAnonymous.value ? 'Réponses anonymes' : 'Identifié par mail',
    small: true,
  }
]);

// Recipients state & modes
const rawEmails = ref('');
const isConfirmed = ref(false);
const hasImported = ref(false);
const importMessage = ref<{ type: 'success' | 'error'; text: string } | null>(null);

const EMAIL_REGEX = /[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/g;

async function handleFileUpload(event: Event) {
  const input = event.target as HTMLInputElement;
  const file = input.files?.[0];
  if (!file) return;

  importMessage.value = null;

  try {
    const data = await file.arrayBuffer();
    const workbook = XLSX.read(data, { type: 'array' });

    const foundEmails = new Set<string>();

    for (const sheetName of workbook.SheetNames) {
      const sheet = workbook.Sheets[sheetName];
      if (!sheet) continue;

      const rows: any[] = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: '' });
      for (const row of rows) {
        if (Array.isArray(row)) {
          for (const cell of row) {
            if (cell !== null && cell !== undefined) {
              const str = String(cell).trim();
              const matches = str.match(EMAIL_REGEX);
              if (matches) {
                matches.forEach(email => foundEmails.add(email.toLowerCase().trim()));
              }
            }
          }
        }
      }
    }

    if (foundEmails.size === 0) {
      importMessage.value = {
        type: 'error',
        text: `Aucune adresse email valide trouvée dans le fichier "${file.name}".`
      };
      input.value = '';
      return;
    }

    const currentEmails = new Set(
      rawEmails.value
        .split(/[\n,;]+/)
        .map(e => e.trim().toLowerCase())
        .filter(Boolean)
    );

    let addedCount = 0;
    const newEmailsList: string[] = [];
    foundEmails.forEach(email => {
      if (!currentEmails.has(email)) {
        newEmailsList.push(email);
        addedCount++;
      }
    });

    if (newEmailsList.length > 0) {
      if (rawEmails.value.trim() !== '') {
        rawEmails.value = rawEmails.value.trim() + '\n' + newEmailsList.join('\n');
      } else {
        rawEmails.value = newEmailsList.join('\n');
      }
    }

    importMessage.value = {
      type: 'success',
      text: `${foundEmails.size} adresse(s) email détectée(s) dans "${file.name}" (${addedCount} nouvelle(s) ajoutée(s)).`
    };
  } catch (error) {
    console.error('Erreur lors de la lecture du fichier:', error);
    importMessage.value = {
      type: 'error',
      text: `Erreur lors de la lecture du fichier "${file.name}". Assurez-vous qu'il s'agit d'un fichier CSV ou Excel valide.`
    };
  } finally {
    input.value = '';
  }
}

const activeMode = ref('manual'); // 'manual' | 'personnels' | 'etudiants'
const modes = [
  { id: 'manual', label: 'Mails à saisir' },
  { id: 'personnels', label: 'Personnels' },
  { id: 'etudiants', label: 'Étudiants' }
];

const surveyDept = computed(() => {
  if (props.survey?.departement) {
    if (typeof props.survey.departement === 'object') {
      return props.survey.departement;
    }
    return { id: props.survey.departement };
  }
  return null;
});

const surveyDeptId = computed(() => surveyDept.value?.id || null);

const configuredSemesterIds = computed(() => {
  const ids = new Set<string | number>();
  effectiveSections.value.forEach(s => {
    if (s.typeSection === 'configurable' && s.opt?.selectedSemesters) {
      s.opt.selectedSemesters.forEach((id: any) => ids.add(id));
    }
  });
  return Array.from(ids);
});

const semesters = ref<any[]>([]);
const statuses = ref<string[]>([]);
const personnels = ref<any[]>([]);
const students = ref<any[]>([]);

const selectedSemestres = ref<any[]>([]);
const personnelFilterType = ref('all'); // 'all' | 'permanent' | 'vacataire'
const personnelFilterStatut = ref('');
const isLoadingData = ref(false);

function selectAllTargetSemestres() {
  selectedSemestres.value = semesters.value.map(s => s.id);
  loadSemesterStudents();
}

function clearTargetSemestres() {
  selectedSemestres.value = [];
  students.value = [];
}

async function selectMode(mode: string) {
  activeMode.value = mode;
  if (mode === 'personnels' && personnels.value.length === 0) {
    isLoadingData.value = true;
    try {
      const deptId = surveyDeptId.value;
      const [resPers, resStats] = await Promise.all([
        getAllPersonnels(deptId),
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
      let resSem: any;
      if (surveyDeptId.value) {
        resSem = await getDepartementSemestresService(surveyDeptId.value, true);
      } else {
        resSem = await getMiniSemestres();
      }
      semesters.value = resSem?.member || resSem || [];

      // Preselection of semesters if department or configured in sections
      if (semesters.value.length > 0) {
        if (configuredSemesterIds.value.length > 0) {
          const matched = semesters.value.filter(s => configuredSemesterIds.value.includes(s.id)).map(s => s.id);
          selectedSemestres.value = matched.length > 0 ? matched : semesters.value.map(s => s.id);
        } else if (surveyDeptId.value) {
          selectedSemestres.value = semesters.value.map(s => s.id);
        }
        if (selectedSemestres.value.length > 0) {
          await loadSemesterStudents();
        }
      }
    } catch (e) {
      console.error(e);
    } finally {
      isLoadingData.value = false;
    }
  }
}

async function loadSemesterStudents() {
  if (selectedSemestres.value.length === 0) {
    students.value = [];
    return;
  }
  isLoadingData.value = true;
  try {
    const responses = await Promise.all(
      selectedSemestres.value.map(semId => getStudentSemestres(semId))
    );
    const all = responses.flatMap(res => res?.member || res || []);
    const map = new Map();
    for (const item of all) {
      const email = item.etudiant?.mailUniv || item.mailUniv;
      const key = item.id || email;
      if (key && !map.has(key)) {
        map.set(key, item);
      }
    }
    students.value = Array.from(map.values());
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

// Recipients calculations
const emailsList = computed(() => {
  return rawEmails.value
    .split(/[\n,;]+/)
    .map(email => email.trim())
    .filter(email => email !== '');
});

const totalRecipientsCount = computed(() => {
  if (activeMode.value === 'manual') return emailsList.value.length;
  if (activeMode.value === 'personnels') return filteredPersonnels.value.length;
  if (activeMode.value === 'etudiants') return filteredStudents.value.length;
  return 0;
});

const validEmails = computed(() => {
  if (activeMode.value === 'manual') {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailsList.value.filter(email => emailRegex.test(email));
  } else if (activeMode.value === 'personnels') {
    return filteredPersonnels.value.map(p => p.mailUniv).filter(Boolean);
  } else if (activeMode.value === 'etudiants') {
    return filteredStudents.value.map(s => s.etudiant?.mailUniv || s.mailUniv).filter(Boolean);
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
  if (!isConfirmed.value || !!dateError.value || uniqueQuestionsCount.value === 0) return;

  const openingIso = openingDate.value ? new Date(openingDate.value).toISOString() : null;
  const closingIso = closingDate.value ? new Date(closingDate.value).toISOString() : null;

  emit('confirm', {
    recipients: validEmails.value,
    openingDate: openingIso,
    closingDate: closingIso
  });
}

onMounted(async () => {
  if (configurableSectionsCount.value > 0 || surveyDeptId.value) {
    await selectMode('etudiants');
  }
});
</script>
