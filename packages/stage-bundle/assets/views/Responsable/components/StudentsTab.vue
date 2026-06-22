<script setup>
import { ref, computed } from 'vue';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
  periods: {
    type: Array,
    required: true
  },
  selectedPeriodId: {
    type: [Number, String],
    required: true
  },
  periodStudents: {
    type: Array,
    required: true
  },
  kpis: {
    type: Object,
    required: true
  },
  teachers: {
    type: Array,
    required: true
  },
  activePeriodName: {
    type: String,
    required: true
  }
});

const emit = defineEmits(['update:selectedPeriodId', 'update-student']);

const toast = useToast();

const localSelectedPeriodId = computed({
  get: () => props.selectedPeriodId,
  set: (val) => emit('update:selectedPeriodId', val)
});

// Dialog state: review request
const selectedRequest = ref(null);
const showReviewDialog = ref(false);
const tutorSelect = ref('');
const rejectReason = ref('');

const openReviewRequest = (student) => {
  selectedRequest.value = student;
  // Initialize with student's current tutor or the first teacher in list
  tutorSelect.value = student.tutor || (props.teachers[0]?.fullName || 'M. Jean Dupont');
  rejectReason.value = '';
  showReviewDialog.value = true;
};

const approveRequest = () => {
  emit('update-student', {
    id: selectedRequest.value.id,
    changes: {
      conventionStatus: 'Validée',
      tutor: tutorSelect.value
    }
  });

  toast.add({
    severity: 'success',
    summary: 'Convention Validée',
    detail: `La demande de ${selectedRequest.value.studentName} a été approuvée. Tuteur : ${tutorSelect.value}.`,
    life: 4000
  });
  showReviewDialog.value = false;
};

const rejectRequest = () => {
  if (!rejectReason.value.trim()) {
    toast.add({ severity: 'warn', summary: 'Erreur', detail: 'Veuillez saisir un motif de rejet.', life: 3000 });
    return;
  }

  emit('update-student', {
    id: selectedRequest.value.id,
    changes: {
      conventionStatus: 'Rejetée'
    }
  });

  toast.add({
    severity: 'error',
    summary: 'Convention Rejetée',
    detail: `La demande de ${selectedRequest.value.studentName} a été rejetée. Motif : ${rejectReason.value}`,
    life: 4000
  });
  showReviewDialog.value = false;
};

const assignTutorDirectly = (student, newTutor) => {
  emit('update-student', {
    id: student.id,
    changes: {
      tutor: newTutor
    }
  });

  toast.add({
    severity: 'success',
    summary: 'Tuteur affecté',
    detail: `Tuteur de ${student.studentName} mis à jour : ${newTutor}.`,
    life: 3000
  });
};

const contactStudent = (studentName) => {
  toast.add({
    severity: 'info',
    summary: 'Rappel envoyé',
    detail: `Un e-mail de rappel de recherche de stage a été envoyé à ${studentName}.`,
    life: 3500
  });
};

const getInitials = (name) => {
  if (!name) return '';
  return name.split(' ').map(n => n[0]).join('');
};
</script>

<template>
  <div class="space-y-4">
    <!-- Active Period Dropdown Switcher (Always Visible for quick context switcher) -->
    <div
      class="bg-white dark:bg-slate-800 p-4 border border-slate-100 dark:border-slate-700/60 rounded-3xl shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div class="flex items-center gap-3 w-full sm:w-auto">
        <div
          class="w-8 h-8 rounded-lg bg-violet-50 dark:bg-violet-950 flex items-center justify-center text-violet-600 shrink-0">
          <i class="pi pi-filter text-xs"></i>
        </div>
        <div class="flex-1 sm:flex-initial">
          <label class="text-[10px] font-bold text-slate-400 block uppercase tracking-wider">Filtrer par Période Académique</label>
          <select v-model="localSelectedPeriodId"
            class="p-1.5 border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-950 text-xs font-bold text-slate-800 dark:text-slate-200 min-w-[240px] max-w-full focus:outline-none">
            <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
          </select>
        </div>
      </div>

      <div class="text-[10px] text-slate-400 dark:text-slate-500 font-mono italic">
        Sujets & indicateurs synchronisés sur la période sélectionnée.
      </div>
    </div>

    <!-- Dynamic KPIs for the selected Period -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
      <!-- KPI 1: Effectif Total -->
      <div
        class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700/60 rounded-3xl p-5 shadow-sm flex items-center gap-3">
        <div
          class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-slate-700/40 flex items-center justify-center text-slate-600 dark:text-slate-300 shrink-0">
          <i class="pi pi-users text-base"></i>
        </div>
        <div>
          <span class="text-[10px] text-slate-400 block font-medium uppercase tracking-wider">Effectif Total</span>
          <span class="text-xl font-black text-slate-900 dark:text-white leading-none block mt-1">{{ kpis.total }}</span>
        </div>
      </div>

      <!-- KPI 2: Placed Count -->
      <div
        class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700/60 rounded-3xl p-5 shadow-sm flex items-center gap-3">
        <div
          class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0">
          <i class="pi pi-check text-base"></i>
        </div>
        <div>
          <span class="text-[10px] text-slate-400 block font-medium uppercase tracking-wider">Étudiants Placés</span>
          <span class="text-xl font-black text-slate-900 dark:text-white leading-none block mt-1">
            {{ kpis.placed }} / {{ kpis.total }}
          </span>
        </div>
      </div>

      <!-- KPI 3: Placement Rate -->
      <div
        class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700/60 rounded-3xl p-5 shadow-sm flex items-center gap-3">
        <div
          class="w-10 h-10 rounded-xl bg-violet-50 dark:bg-violet-500/10 flex items-center justify-center text-violet-600 dark:text-violet-400 shrink-0">
          <i class="pi pi-percentage text-base"></i>
        </div>
        <div>
          <span class="text-[10px] text-slate-400 block font-medium uppercase tracking-wider">Taux de Placement</span>
          <span class="text-xl font-black text-slate-900 dark:text-white leading-none block mt-1">{{ kpis.rate }} %</span>
        </div>
      </div>

      <!-- KPI 4: Pending Conventions -->
      <div
        class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700/60 rounded-3xl p-5 shadow-sm flex items-center gap-3">
        <div
          class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-500/10 flex items-center justify-center text-amber-600 dark:text-amber-400 shrink-0">
          <i class="pi pi-clock text-base"></i>
        </div>
        <div>
          <span class="text-[10px] text-slate-400 block font-medium uppercase tracking-wider">À Valider</span>
          <span class="text-xl font-black text-slate-900 dark:text-white leading-none block mt-1">{{ kpis.pending }}</span>
        </div>
      </div>

      <!-- KPI 5: Reports Deposited -->
      <div
        class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700/60 rounded-3xl p-5 shadow-sm flex items-center gap-3 col-span-2 lg:col-span-1">
        <div
          class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center text-blue-600 dark:text-blue-400 shrink-0">
          <i class="pi pi-file-import text-base"></i>
        </div>
        <div>
          <span class="text-[10px] text-slate-400 block font-medium uppercase tracking-wider">Rapports reçus</span>
          <span class="text-xl font-black text-slate-900 dark:text-white leading-none block mt-1">
            {{ kpis.reports }} / {{ kpis.total }}
          </span>
        </div>
      </div>
    </div>

    <!-- Table List -->
    <div class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700/60 rounded-3xl shadow-sm overflow-hidden">
      <div class="p-6 border-b border-slate-50 dark:border-slate-700/50 flex flex-wrap justify-between items-center gap-4">
        <div>
          <h3 class="text-sm font-bold text-slate-900 dark:text-white">Liste des étudiants de la période</h3>
          <p class="text-[11px] text-slate-400 mt-0.5">Suivi de placement, tuteur universitaire et livrables.</p>
        </div>
        <div class="text-xs font-semibold text-slate-400">
          Période : <span class="text-violet-600 dark:text-violet-400 font-bold">{{ activePeriodName }}</span>
        </div>
      </div>

      <DataTable :value="periodStudents" responsiveLayout="scroll" class="text-xs text-slate-700 dark:text-slate-300" stripedRows>
        <!-- Column 1: Student Identity -->
        <Column header="Étudiant" class="font-semibold text-slate-900 dark:text-white min-w-[180px]">
          <template #body="slotProps">
            <div class="flex items-center gap-3 py-1">
              <div
                class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 flex items-center justify-center font-bold text-xs">
                {{ getInitials(slotProps.data.studentName) }}
              </div>
              <div>
                <span class="font-bold text-sm block">{{ slotProps.data.studentName }}</span>
                <span class="text-[9px] text-slate-400 block" v-if="slotProps.data.hasStage">Maître : {{ slotProps.data.supervisor }}</span>
              </div>
            </div>
          </template>
        </Column>

        <!-- Column 2: Stage status -->
        <Column header="État Placement" class="min-w-[150px]">
          <template #body="slotProps">
            <div v-if="slotProps.data.hasStage" class="space-y-1">
              <span
                class="bg-emerald-100 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 px-2 py-0.5 rounded font-bold text-[9px] uppercase tracking-wider inline-block">
                Placé
              </span>
              <span class="text-[10px] font-medium text-slate-500 dark:text-slate-400 block truncate max-w-[140px]">
                {{ slotProps.data.company }}
              </span>
            </div>
            <div v-else class="space-y-1">
              <span
                class="bg-rose-100 dark:bg-rose-950/20 text-rose-800 dark:text-rose-400 px-2 py-0.5 rounded font-bold text-[9px] uppercase tracking-wider inline-block">
                Sans Stage
              </span>
              <span class="text-[9px] text-slate-400 block">Recherche active</span>
            </div>
          </template>
        </Column>

        <!-- Column 3: Convention progress -->
        <Column header="Convention" class="min-w-[120px]">
          <template #body="slotProps">
            <span v-if="slotProps.data.conventionStatus === 'Validée'"
              class="bg-emerald-100 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 px-2 py-0.5 rounded font-bold text-[9px] flex items-center gap-1 w-max">
              <i class="pi pi-check-circle text-[8px]"></i>
              <span>Validée</span>
            </span>
            <span v-else-if="slotProps.data.conventionStatus === 'Rejetée'"
              class="bg-rose-100 dark:bg-rose-950/30 text-rose-800 dark:text-rose-400 px-2 py-0.5 rounded font-bold text-[9px] flex items-center gap-1 w-max">
              <i class="pi pi-times-circle text-[8px]"></i>
              <span>Refusée</span>
            </span>
            <span v-else-if="slotProps.data.conventionStatus === 'En attente'"
              class="bg-amber-100 dark:bg-amber-950/30 text-amber-800 dark:text-amber-300 px-2 py-0.5 rounded font-bold text-[9px] flex items-center gap-1 w-max animate-pulse">
              <i class="pi pi-clock text-[8px]"></i>
              <span>À valider</span>
            </span>
            <span v-else
              class="bg-slate-100 dark:bg-slate-700/50 text-slate-400 dark:text-slate-500 px-2 py-0.5 rounded font-bold text-[9px] inline-block">
              Aucune demande
            </span>
          </template>
        </Column>

        <!-- Column 4: Tutor assignment drop-down -->
        <Column header="Tuteur Universitaire" class="min-w-[170px]">
          <template #body="slotProps">
            <!-- Dropdown directly editable in table if they have a stage -->
            <select v-if="slotProps.data.hasStage" :value="slotProps.data.tutor || ''"
              @change="assignTutorDirectly(slotProps.data, $event.target.value)"
              class="p-1 border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-900 text-[10px] font-semibold text-slate-800 dark:text-slate-200 focus:outline-none w-full max-w-[150px]">
              <option value="">-- Assigner --</option>
              <option v-for="t in teachers" :key="t.iri || t" :value="t.fullName || t">{{ t.fullName || t }}</option>
            </select>
            <span v-else class="text-[10px] text-slate-400">Non applicable</span>
          </template>
        </Column>

        <!-- Column 5: Student Report uploaded -->
        <Column header="Rapport" class="text-center min-w-[100px]">
          <template #body="slotProps">
            <span v-if="slotProps.data.reportUploaded"
              class="bg-blue-100 dark:bg-blue-950/40 text-blue-800 dark:text-blue-300 px-2.5 py-0.5 rounded-full font-bold text-[9px] flex items-center gap-1 w-max mx-auto"
              v-tooltip="slotProps.data.reportName">
              <i class="pi pi-file text-[8px]"></i>
              <span>Reçu</span>
            </span>
            <span v-else class="text-slate-400 text-[10px]">-</span>
          </template>
        </Column>

        <!-- Column 6: Contextual Action Buttons -->
        <Column header="Actions" class="text-right min-w-[120px]">
          <template #body="slotProps">
            <!-- Case A: Convention needs validation -->
            <button v-if="slotProps.data.conventionStatus === 'En attente'" @click="openReviewRequest(slotProps.data)"
              class="text-[10px] font-bold px-3 py-1.5 bg-violet-600 hover:bg-violet-700 text-white rounded-lg transition-all flex items-center gap-1.5 ml-auto">
              <i class="pi pi-check text-[8px]"></i>
              <span>Valider</span>
            </button>

            <!-- Case B: Student has no placement, send reminder -->
            <button v-else-if="!slotProps.data.hasStage" @click="contactStudent(slotProps.data.studentName)"
              class="text-[10px] font-bold px-3 py-1.5 bg-slate-50 dark:bg-slate-700 hover:bg-slate-100 dark:hover:bg-slate-600/60 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-600 rounded-lg transition-all flex items-center gap-1.5 ml-auto">
              <i class="pi pi-envelope text-[8px]"></i>
              <span>Contacter</span>
            </button>

            <!-- Case C: Convention already validated, show detailed sheet -->
            <button v-else @click="openReviewRequest(slotProps.data)"
              class="text-[10px] font-bold px-3 py-1.5 bg-slate-50 dark:bg-slate-700 hover:bg-slate-100 dark:hover:bg-slate-600/60 text-slate-700 dark:text-slate-300 rounded-lg transition-all flex items-center gap-1.5 ml-auto">
              <i class="pi pi-info-circle text-[8px]"></i>
              <span>Détails</span>
            </button>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- DIALOG: EXAMINER / VALIDATION FLOW & DETAIL SHEET -->
    <Dialog v-model:visible="showReviewDialog" modal header="Instruction de la demande de convention"
      :style="{ width: '85vw', maxWidth: '750px' }" class="text-xs dark:bg-slate-800 dark:text-slate-200">
      <div v-if="selectedRequest" class="space-y-6 py-4">
        <!-- Recal of student inputs -->
        <div class="bg-slate-50 dark:bg-slate-900/40 border border-slate-100 dark:border-slate-800 rounded-2xl p-5 space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <span class="text-slate-400 block text-[10px]">Étudiant déclarant :</span>
              <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">{{ selectedRequest.studentName }}</span>
            </div>
            <div>
              <span class="text-slate-400 block text-[10px]">Entreprise d'accueil :</span>
              <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">{{ selectedRequest.company }}</span>
            </div>
            <div>
              <span class="text-slate-400 block text-[10px]">Dates du stage :</span>
              <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">{{ selectedRequest.dates }}</span>
            </div>
            <div>
              <span class="text-slate-400 block text-[10px]">Tuteur Entreprise (Maître de stage) :</span>
              <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">{{ selectedRequest.supervisor }}</span>
            </div>
            <div>
              <span class="text-slate-400 block text-[10px]">Gratification :</span>
              <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">{{ selectedRequest.salary }}</span>
            </div>
            <div>
              <span class="text-slate-400 block text-[10px]">Tuteur Universitaire :</span>
              <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">{{ selectedRequest.tutor || 'Non affecté' }}</span>
            </div>
            <div class="md:col-span-2 pt-2 border-t border-slate-200/40">
              <span class="text-slate-400 block text-[10px]">Sujet de stage / Missions :</span>
              <p class="font-semibold text-slate-700 dark:text-slate-300 mt-1 leading-relaxed">{{ selectedRequest.subject }}</p>
            </div>
          </div>
        </div>

        <div v-if="selectedRequest.conventionStatus === 'En attente'"
          class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-slate-100 dark:border-slate-700/50">
          <!-- Validation controls -->
          <div class="space-y-4">
            <h4 class="text-xs font-bold text-slate-800 dark:text-slate-100 uppercase tracking-wider">Option A : Valider la demande</h4>

            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-bold text-slate-500">Affecter un Tuteur Universitaire</label>
              <select v-model="tutorSelect"
                class="w-full p-2.5 border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-900 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:outline-none">
                <option v-for="t in teachers" :key="t.iri || t" :value="t.fullName || t">{{ t.fullName || t }}</option>
              </select>
            </div>

            <button @click="approveRequest"
              class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs transition-all flex items-center justify-center gap-2">
              <i class="pi pi-check"></i>
              <span>Approuver & Assigner le tuteur</span>
            </button>
          </div>

          <!-- Rejection controls -->
          <div class="space-y-4 md:border-l md:border-slate-100 md:dark:border-slate-700/50 md:pl-6">
            <h4 class="text-xs font-bold text-slate-800 dark:text-slate-100 uppercase tracking-wider">Option B : Rejeter la demande</h4>

            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-bold text-slate-500">Motif du rejet (Sera envoyé à l'étudiant)</label>
              <textarea v-model="rejectReason" rows="2.5"
                placeholder="Ex: Le SIRET saisi est erroné. Veuillez corriger."
                class="w-full p-2.5 border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-rose-500"></textarea>
            </div>

            <button @click="rejectRequest"
              class="w-full py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs transition-all flex items-center justify-center gap-2">
              <i class="pi pi-times"></i>
              <span>Rejeter la demande</span>
            </button>
          </div>
        </div>

        <div v-else class="flex justify-end pt-4 border-t border-slate-100 dark:border-slate-700/50">
          <button @click="showReviewDialog = false"
            class="px-5 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 font-bold rounded-xl text-xs text-slate-700 dark:text-slate-300 transition-all">
            Fermer
          </button>
        </div>
      </div>
    </Dialog>
  </div>
</template>
