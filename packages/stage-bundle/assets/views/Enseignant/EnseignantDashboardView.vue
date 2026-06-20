<script setup>
import { ref, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import { FilterMatchMode } from '@primevue/core/api';

const toast = useToast();

// Filtering state
const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS },
  studentName: { value: null, matchMode: FilterMatchMode.CONTAINS },
  company: { value: null, matchMode: FilterMatchMode.CONTAINS },
  period: { value: null, matchMode: FilterMatchMode.EQUALS },
  reportUploaded: { value: null, matchMode: FilterMatchMode.EQUALS }
});

const periodOptions = ref(['BUT3 Informatique', 'BUT3 MMI']);
const statusOptions = ref([
  { label: 'Déposé', value: true },
  { label: 'En attente', value: false }
]);

// Mock students supervised list
const students = ref([
  {
    id: 1,
    studentName: 'Lucas Martin',
    period: 'BUT3 Informatique',
    company: 'Avenir Digital',
    dates: '02/03/2026 au 26/06/2026',
    supervisor: 'M. Antoine Robert',
    reportUploaded: true,
    reportName: 'Rapport_Final_BUT3_Martin.pdf',
    grade: '',
    comments: '',
    followups: [
      { id: 1, date: '15/03/2026', type: 'Appel Téléphonique', summary: 'Premier contact, l\'étudiant s\'intègre bien. Missions validées.' },
      { id: 2, date: '20/04/2026', type: 'Visite Entreprise', summary: 'Rencontre avec le maître de stage. Le projet avance. L\'étudiant est autonome.' }
    ]
  },
  {
    id: 2,
    studentName: 'Emma Bernard',
    period: 'BUT3 Informatique',
    company: 'Innovatech Corp',
    dates: '02/03/2026 au 26/06/2026',
    supervisor: 'Mme. Julie Simon',
    reportUploaded: false,
    reportName: '',
    grade: '',
    comments: '',
    followups: [
      { id: 1, date: '18/03/2026', type: 'Visioconférence', summary: 'Point sur l\'installation. Quelques soucis d\'accès au VPN résolus.' }
    ]
  },
  {
    id: 3,
    studentName: 'Thomas Petit',
    period: 'BUT3 MMI',
    company: 'Creative Studio',
    dates: '09/03/2026 au 03/07/2026',
    supervisor: 'M. Gabriel Dubois',
    reportUploaded: true,
    reportName: 'Portfolio_Thomas_MMI.pdf',
    grade: '15.5',
    comments: 'Très bon portfolio de créations graphiques. Travail sérieux.',
    followups: [
      { id: 1, date: '25/03/2026', type: 'Appel Téléphonique', summary: 'Bonne prise en main des outils Figma et Illustrator.' },
      { id: 2, date: '12/05/2026', type: 'Visite Entreprise', summary: 'Le maître de stage est extrêmement satisfait de la créativité de Thomas.' }
    ]
  }
]);

// Selection state for detail dialog
const selectedStudent = ref(null);
const showDetailDialog = ref(false);

// New follow-up form state
const newFollowup = ref({
  date: new Date().toISOString().split('T')[0],
  type: 'Appel Téléphonique',
  summary: ''
});

// Grading form state
const gradingInput = ref('');
const commentsInput = ref('');

// Computed counts
const totalSupervised = computed(() => students.value.length);
const reportsDeposited = computed(() => students.value.filter(s => s.reportUploaded).length);
const gradedCount = computed(() => students.value.filter(s => s.grade !== '').length);

const openStudentDetails = (student) => {
  selectedStudent.value = student;
  gradingInput.value = student.grade;
  commentsInput.value = student.comments;
  showDetailDialog.value = true;
};

const addFollowup = () => {
  if (!newFollowup.value.summary.trim()) {
    toast.add({ severity: 'warn', summary: 'Erreur', detail: 'Le compte-rendu ne peut pas être vide.', life: 3000 });
    return;
  }

  const followupObj = {
    id: Date.now(),
    date: new Date(newFollowup.value.date).toLocaleDateString('fr-FR'),
    type: newFollowup.value.type,
    summary: newFollowup.value.summary
  };

  // Find index and push
  const studentIndex = students.value.findIndex(s => s.id === selectedStudent.value.id);
  if (studentIndex !== -1) {
    students.value[studentIndex].followups.push(followupObj);
    // Refresh selectedStudent panel
    selectedStudent.value = { ...students.value[studentIndex] };

    // Clear input
    newFollowup.value.summary = '';

    toast.add({
      severity: 'success',
      summary: 'Suivi enregistré',
      detail: 'Le compte-rendu de suivi a bien été ajouté.',
      life: 3000
    });
  }
};

const saveGrading = () => {
  const studentIndex = students.value.findIndex(s => s.id === selectedStudent.value.id);
  if (studentIndex !== -1) {
    students.value[studentIndex].grade = gradingInput.value;
    students.value[studentIndex].comments = commentsInput.value;
    selectedStudent.value = { ...students.value[studentIndex] };

    toast.add({
      severity: 'success',
      summary: 'Évaluation enregistrée',
      detail: 'La note et les appréciations ont bien été mises à jour.',
      life: 3000
    });
  }
};

const getInitials = (name) => {
  return name.split(' ').map(n => n[0]).join('');
};
</script>

<template>
  <div class="mx-auto space-y-6">
    <Toast />

    <!-- Top Header -->
    <div class="border-b border-slate-100 dark:border-slate-800 pb-5">
      <h1 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2">
        <i class="pi pi-users text-violet-600"></i>
        <span>Mes Étudiants en Stage</span>
      </h1>
      <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
        Suivez l'avancement des stages dont vous êtes le tuteur universitaire désigné.
      </p>
    </div>

    <!-- Quick Stats Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

      <div
        class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700/60 rounded-3xl p-6 shadow-sm flex items-center gap-4">
        <div
          class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shrink-0">
          <i class="pi pi-user text-xl"></i>
        </div>
        <div>
          <span class="text-xs text-slate-400 block font-medium">Étudiants suivis</span>
          <span class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ totalSupervised }}</span>
        </div>
      </div>

      <div
        class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700/60 rounded-3xl p-6 shadow-sm flex items-center gap-4">
        <div
          class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0">
          <i class="pi pi-file-import text-xl"></i>
        </div>
        <div>
          <span class="text-xs text-slate-400 block font-medium">Rapports déposés</span>
          <span class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ reportsDeposited }} / {{
            totalSupervised }}</span>
        </div>
      </div>

      <div
        class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700/60 rounded-3xl p-6 shadow-sm flex items-center gap-4">
        <div
          class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-500/10 flex items-center justify-center text-amber-600 dark:text-amber-400 shrink-0">
          <i class="pi pi-star text-xl"></i>
        </div>
        <div>
          <span class="text-xs text-slate-400 block font-medium">Évaluations saisies</span>
          <span class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ gradedCount }} / {{ totalSupervised
            }}</span>
        </div>
      </div>

    </div>

    <!-- Students DataTable -->
    <div
      class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700/60 rounded-3xl shadow-sm overflow-hidden">
      <DataTable v-model:filters="filters" :value="students" responsiveLayout="scroll" class="text-xs text-slate-700 dark:text-slate-300"
        stripedRows :globalFilterFields="['studentName', 'company', 'supervisor', 'period']">
        <template #header>
          <div class="flex flex-wrap gap-4 items-center justify-between p-4 bg-slate-50/50 dark:bg-slate-900/20 border-b border-slate-100 dark:border-slate-800">
            <div class="flex items-center gap-2">
              <span class="font-bold text-slate-800 dark:text-slate-200">Liste des étudiants</span>
              <span class="bg-violet-50 dark:bg-violet-950/40 text-violet-700 dark:text-violet-400 px-2 py-0.5 rounded-full text-[10px] font-black">
                {{ totalSupervised }} étudiants
              </span>
            </div>
            <div class="flex flex-wrap gap-3 items-center">
              <!-- Global Search -->
              <IconField>
                <InputIcon>
                  <i class="pi pi-search text-slate-400" />
                </InputIcon>
                <InputText v-model="filters.global.value" placeholder="Rechercher..." class="p-inputtext-sm text-xs w-[180px] bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-xl" />
              </IconField>
              
              <!-- Filter by Period -->
              <Select v-model="filters.period.value" :options="periodOptions" showClear placeholder="Formation" class="p-select-sm text-xs min-w-[150px] bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-xl" />

              <!-- Filter by Report Status -->
              <Select v-model="filters.reportUploaded.value" :options="statusOptions" optionLabel="label" optionValue="value" showClear placeholder="État du rapport" class="p-select-sm text-xs min-w-[150px] bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-xl" />
            </div>
          </div>
        </template>
        <Column header="Étudiant" class="font-semibold text-slate-900 dark:text-white">
          <template #body="slotProps">
            <div class="flex items-center gap-3 py-1">
              <div
                class="w-8 h-8 rounded-lg bg-violet-100 dark:bg-violet-950 text-violet-700 dark:text-violet-300 flex items-center justify-center font-bold text-xs uppercase">
                {{ getInitials(slotProps.data.studentName) }}
              </div>
              <div>
                <span class="font-bold text-sm block">{{ slotProps.data.studentName }}</span>
                <span class="text-[10px] text-slate-400 block">{{ slotProps.data.period }}</span>
              </div>
            </div>
          </template>
        </Column>

        <Column field="company" header="Entreprise" />
        <Column field="dates" header="Dates" />
        <Column field="supervisor" header="Maître de Stage" />

        <Column header="Rapport" class="text-center">
          <template #body="slotProps">
            <span v-if="slotProps.data.reportUploaded"
              class="bg-emerald-100 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 px-2 py-0.5 rounded font-bold text-[10px] flex items-center gap-1 w-max">
              <i class="pi pi-check text-[8px]"></i>
              <span>Déposé</span>
            </span>
            <span v-else
              class="bg-rose-100 dark:bg-rose-950/30 text-rose-800 dark:text-rose-400 px-2 py-0.5 rounded font-bold text-[10px] flex items-center gap-1 w-max">
              <i class="pi pi-times text-[8px]"></i>
              <span>En attente</span>
            </span>
          </template>
        </Column>

        <Column header="Note" class="text-center font-bold">
          <template #body="slotProps">
            <span>{{ slotProps.data.grade ? slotProps.data.grade + ' / 20' : '-' }}</span>
          </template>
        </Column>

        <Column header="Actions" class="text-right">
          <template #body="slotProps">
            <button @click="openStudentDetails(slotProps.data)"
              class="text-xs font-bold px-3 py-2 bg-slate-50 dark:bg-slate-700 hover:bg-violet-600 hover:text-white dark:hover:bg-violet-600 rounded-xl transition-all flex items-center gap-2.5 ml-auto">
              <i class="pi pi-pencil text-[9px]"></i>
              <span>Suivre</span>
            </button>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Student Detail & Follow-up Slideout Dialog -->
    <Dialog v-model:visible="showDetailDialog" modal header="Suivi individualisé de l'étudiant"
      :style="{ width: '80vw', maxWidth: '850px' }" class="text-xs dark:bg-slate-800 dark:text-slate-200">
      <div v-if="selectedStudent" class="grid grid-cols-1 lg:grid-cols-2 gap-8 py-4">

        <!-- Left Column: Internship Info & Followups history -->
        <div class="space-y-6">

          <!-- Summary Info Box -->
          <div class="bg-slate-50 dark:bg-slate-900/40 rounded-2xl p-5 border border-slate-100 dark:border-slate-800">
            <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
              <i class="pi pi-info-circle text-violet-600"></i>
              <span>Détails du stage</span>
            </h4>
            <div class="grid grid-cols-2 gap-4 mt-4 text-[11px]">
              <div>
                <span class="text-slate-400 block">Étudiant</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">{{ selectedStudent.studentName }}</span>
              </div>
              <div>
                <span class="text-slate-400 block">Entreprise</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">{{ selectedStudent.company }}</span>
              </div>
              <div>
                <span class="text-slate-400 block">Maître de stage</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">{{ selectedStudent.supervisor }}</span>
              </div>
              <div>
                <span class="text-slate-400 block">Période de stage</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">{{ selectedStudent.dates }}</span>
              </div>
            </div>

            <!-- Report Download link if uploaded -->
            <div v-if="selectedStudent.reportUploaded"
              class="mt-4 pt-3 border-t border-slate-200/40 flex items-center justify-between bg-white dark:bg-slate-800 p-2.5 rounded-xl border border-slate-100 dark:border-slate-700/60">
              <span class="font-bold text-slate-700 dark:text-slate-300 truncate max-w-[200px] flex items-center gap-2">
                <i class="pi pi-file-pdf text-red-500"></i>
                {{ selectedStudent.reportName }}
              </span>
              <a href="#"
                @click.prevent="toast.add({ severity: 'success', summary: 'Téléchargement', detail: 'Le rapport de stage a été téléchargé.', life: 2000 })"
                class="text-[10px] font-bold text-violet-600 dark:text-violet-400 hover:underline flex items-center gap-1">
                <i class="pi pi-download"></i>
                <span>Télécharger</span>
              </a>
            </div>
          </div>

          <!-- Logged Follow-ups List -->
          <div>
            <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100 mb-4 flex items-center gap-2">
              <i class="pi pi-calendar-plus text-violet-600"></i>
              <span>Historique des Suivis ({{ selectedStudent.followups.length }})</span>
            </h4>

            <div class="space-y-4 max-h-[220px] overflow-y-auto pr-2">
              <div v-for="follow in selectedStudent.followups" :key="follow.id"
                class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-700/50 rounded-xl p-4 space-y-1 relative">
                <div class="flex items-center justify-between">
                  <span
                    class="bg-violet-50 dark:bg-violet-950/40 text-violet-700 dark:text-violet-400 px-2 py-0.5 rounded font-bold text-[9px]">
                    {{ follow.type }}
                  </span>
                  <span class="text-[9px] text-slate-400">{{ follow.date }}</span>
                </div>
                <p class="text-[10px] text-slate-600 dark:text-slate-300 leading-relaxed pt-1">
                  {{ follow.summary }}
                </p>
              </div>

              <div v-if="selectedStudent.followups.length === 0" class="text-center py-6 text-slate-400">
                Aucun entretien de suivi saisi pour le moment.
              </div>
            </div>
          </div>

        </div>

        <!-- Right Column: Add follow-up & Grading -->
        <div class="space-y-6 lg:border-l lg:border-slate-100 lg:dark:border-slate-700/50 lg:pl-6">

          <!-- Add Follow-up Form -->
          <div class="space-y-4">
            <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
              <i class="pi pi-plus text-violet-600"></i>
              <span>Ajouter un suivi</span>
            </h4>

            <div class="grid grid-cols-2 gap-4">
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-bold text-slate-500">Date du suivi</label>
                <input type="date" v-model="newFollowup.date"
                  class="p-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs" />
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-bold text-slate-500">Canal</label>
                <select v-model="newFollowup.type"
                  class="p-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-semibold">
                  <option value="Appel Téléphonique">Appel Téléphonique</option>
                  <option value="Visite Entreprise">Visite Entreprise</option>
                  <option value="Visioconférence">Visioconférence</option>
                  <option value="Échange d'E-mails">Échange d'E-mails</option>
                </select>
              </div>
            </div>

            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-bold text-slate-500">Compte-rendu du suivi</label>
              <textarea v-model="newFollowup.summary" rows="2" placeholder="Renseignez le déroulement de l'échange..."
                class="w-full p-2.5 border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-violet-500"></textarea>
            </div>

            <button @click="addFollowup"
              class="w-full py-2 bg-violet-600 hover:bg-violet-700 text-white font-bold rounded-lg text-xs transition-all flex items-center justify-center gap-2">
              <i class="pi pi-check text-[10px]"></i>
              <span>Enregistrer le suivi</span>
            </button>
          </div>

          <!-- Grading Evaluation Panel -->
          <div class="pt-4 border-t border-slate-100 dark:border-slate-700/50 space-y-4">
            <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
              <i class="pi pi-star text-violet-600"></i>
              <span>Notation & Appréciation</span>
            </h4>

            <div class="flex gap-4 items-end">
              <div class="flex flex-col gap-1.5 w-1/3">
                <label class="text-[10px] font-bold text-slate-500">Note / 20</label>
                <input type="text" v-model="gradingInput" placeholder="Ex: 15.5"
                  class="p-2.5 border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-xs font-bold text-center" />
              </div>
              <div class="flex-1">
                <span class="text-[10px] text-slate-400 block pb-1">
                  La note doit être saisie après réception et lecture du rapport de stage.
                </span>
              </div>
            </div>

            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-bold text-slate-500">Appréciations pédagogiques</label>
              <textarea v-model="commentsInput" rows="3"
                placeholder="Rédigez l'appréciation globale qui figurera sur le procès-verbal de stage..."
                class="w-full p-2.5 border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-violet-500"></textarea>
            </div>

            <button @click="saveGrading"
              class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-xs transition-all flex items-center justify-center gap-2">
              <i class="pi pi-save text-[10px]"></i>
              <span>Sauvegarder l'évaluation</span>
            </button>
          </div>

        </div>

      </div>
    </Dialog>

  </div>
</template>
