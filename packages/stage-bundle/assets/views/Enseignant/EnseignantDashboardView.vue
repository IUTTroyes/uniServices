<script setup>
import { ref, computed, onMounted } from 'vue';
import { useToast } from 'primevue/usetoast';
import { FilterMatchMode } from '@primevue/core/api';
import { useUsersStore } from '@stores';
import { getStageEtudiantsService, updateStageEtudiantService, getStagePeriodesService } from '@/requests/stage_service';

const toast = useToast();
const userStore = useUsersStore();

// Filtering state
const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS },
  studentName: { value: null, matchMode: FilterMatchMode.CONTAINS },
  company: { value: null, matchMode: FilterMatchMode.CONTAINS },
  period: { value: null, matchMode: FilterMatchMode.EQUALS },
  reportUploaded: { value: null, matchMode: FilterMatchMode.EQUALS }
});

const periodOptions = ref([]);
const statusOptions = ref([
  { label: 'Déposé', value: true },
  { label: 'En attente', value: false }
]);

const students = ref([]);
const loading = ref(false);

// Map backend StageEtudiant entity format to Vue template format
const mapStageEtudiantToVue = (se) => {
  const etu = se.etudiant || {};
  const ent = se.entreprise || {};
  const tut = se.tuteur || {};
  const p = se.stagePeriode || {};

  const formatDate = (isoStr) => {
    if (!isoStr) return '';
    const parts = isoStr.substring(0, 10).split('-');
    if (parts.length === 3) {
      return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }
    return isoStr;
  };

  const datesStr = (se.dateDebutStage && se.dateFinStage)
    ? `${formatDate(se.dateDebutStage)} au ${formatDate(se.dateFinStage)}`
    : '-';

  const supervisorName = tut.prenom ? `${tut.civilite || 'M.'} ${tut.prenom} ${tut.nom}` : '-';

  const dateDebutDiff = se.dateDebutStage && p.dateDebut && se.dateDebutStage.substring(0, 10) !== p.dateDebut.substring(0, 10);
  const dateFinDiff = se.dateFinStage && p.dateFin && se.dateFinStage.substring(0, 10) !== p.dateFin.substring(0, 10);
  const isDatesDiff = !!(dateDebutDiff || dateFinDiff);

  const address = ent.adresse || {};
  const formattedAddress = address.adresse 
    ? `${address.adresse}${address.complement1 ? ', ' + address.complement1 : ''}${address.complement2 ? ', ' + address.complement2 : ''} - ${address.codePostal} ${address.ville} (${address.pays || 'France'})`
    : '-';

  const tuteurUniv = se.tuteurUniversitaire || {};
  const academicTutorName = tuteurUniv.prenom ? `${tuteurUniv.civilite || 'M.'} ${tuteurUniv.prenom} ${tuteurUniv.nom}` : 'Non affecté';

  // Signatory (Responsable de l'entreprise)
  const resp = ent.responsable || {};
  const signatoryName = resp.prenom ? `${resp.civilite || 'M.'} ${resp.prenom} ${resp.nom}` : '-';

  return {
    id: se.id,
    studentName: `${etu.prenom || ''} ${etu.nom || ''}`.trim() || 'Étudiant inconnu',
    studentPhone: etu.tel1 || etu.tel2 || '',
    studentEmail: etu.mailPerso || etu.mailUniv || '',
    period: p.libelle || 'Période inconnue',
    company: ent.raisonSociale || '-',
    siret: ent.siret || '-',
    companyAddress: formattedAddress,
    companyPhone: resp.telephone || '-',
    signatoryName: signatoryName,
    signatoryTitle: resp.fonction || '-',
    signatoryEmail: resp.email || '-',
    dates: datesStr,
    startDateStr: formatDate(se.dateDebutStage),
    endDateStr: formatDate(se.dateFinStage),
    periodStartDateStr: formatDate(p.dateDebut),
    periodEndDateStr: formatDate(p.dateFin),
    dateDebutDiff,
    dateFinDiff,
    isDatesDiff,
    supervisor: supervisorName,
    supervisorTitle: tut.fonction || '-',
    supervisorEmail: tut.email || '-',
    supervisorPhone: tut.telephone || '-',
    academicTutor: academicTutorName,
    subject: se.sujetStage || 'Non renseigné',
    activities: se.activites || 'Non renseignées',
    weeklyHours: se.dureeHebdomadaire || 35,
    reportUploaded: !!se.reportUploaded,
    reportName: se.reportName || '',
    grade: se.evaluationNote !== null && se.evaluationNote !== undefined ? String(se.evaluationNote) : '',
    comments: se.evaluationCommentaire || '',
    followups: se.suiviRencontres || []
  };
};

const fetchSupervisedStudents = async () => {
  if (!userStore.userId) return;
  loading.value = true;
  try {
    const data = await getStageEtudiantsService({ tuteurUniversitaire: `/api/personnels/${userStore.userId}` });
    students.value = (data || []).map(mapStageEtudiantToVue);
  } catch (error) {
    console.error('Erreur lors du chargement des étudiants suivis:', error);
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de charger vos étudiants.', life: 3000 });
  } finally {
    loading.value = false;
  }
};

const fetchPeriodOptions = async () => {
  try {
    const data = await getStagePeriodesService();
    periodOptions.value = (data || []).map(p => p.libelle).filter((v, i, a) => a.indexOf(v) === i);
  } catch (error) {
    console.error('Erreur lors du chargement des périodes pour filtre:', error);
  }
};

onMounted(async () => {
  await userStore.getUser();
  await fetchSupervisedStudents();
  await fetchPeriodOptions();
});

// Selection state for detail dialog
const selectedStudent = ref(null);
const showDetailDialog = ref(false);
const showInfoDialog = ref(false);

const openStudentInfo = (student) => {
  selectedStudent.value = student;
  showInfoDialog.value = true;
};

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

const addFollowup = async () => {
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

  const updatedFollowups = [...(selectedStudent.value.followups || []), followupObj];

  try {
    await updateStageEtudiantService(selectedStudent.value.id, {
      suiviRencontres: updatedFollowups
    }, false);

    selectedStudent.value.followups = updatedFollowups;
    const studentIndex = students.value.findIndex(s => s.id === selectedStudent.value.id);
    if (studentIndex !== -1) {
      students.value[studentIndex].followups = updatedFollowups;
    }

    // Clear input
    newFollowup.value.summary = '';

    toast.add({
      severity: 'success',
      summary: 'Suivi enregistré',
      detail: 'Le compte-rendu de suivi a bien été ajouté.',
      life: 3000
    });
  } catch (error) {
    console.error('Erreur lors de la sauvegarde du suivi:', error);
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible d\'enregistrer le suivi.', life: 3000 });
  }
};

const saveGrading = async () => {
  const note = gradingInput.value.trim() !== '' ? parseFloat(gradingInput.value) : null;
  if (gradingInput.value.trim() !== '' && (isNaN(note) || note < 0 || note > 20)) {
    toast.add({ severity: 'warn', summary: 'Erreur', detail: 'La note doit être un nombre compris entre 0 et 20.', life: 3000 });
    return;
  }

  try {
    await updateStageEtudiantService(selectedStudent.value.id, {
      evaluationNote: note,
      evaluationCommentaire: commentsInput.value
    }, false);

    selectedStudent.value.grade = gradingInput.value.trim() !== '' ? String(note) : '';
    selectedStudent.value.comments = commentsInput.value;

    const studentIndex = students.value.findIndex(s => s.id === selectedStudent.value.id);
    if (studentIndex !== -1) {
      students.value[studentIndex].grade = selectedStudent.value.grade;
      students.value[studentIndex].comments = selectedStudent.value.comments;
    }

    toast.add({
      severity: 'success',
      summary: 'Évaluation enregistrée',
      detail: 'La note et les appréciations ont bien été mises à jour.',
      life: 3000
    });
  } catch (error) {
    console.error('Erreur lors de la sauvegarde de la note:', error);
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de sauvegarder l\'évaluation.', life: 3000 });
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
      <DataTable v-model:filters="filters" :value="students" :loading="loading" responsiveLayout="scroll" class="text-xs text-slate-700 dark:text-slate-300"
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
        <Column header="Dates">
          <template #body="slotProps">
            <span :class="{'text-amber-600 dark:text-amber-400 font-black flex items-center gap-1 w-max': slotProps.data.isDatesDiff}" :title="slotProps.data.isDatesDiff ? 'Dates différentes de la période de stage (' + slotProps.data.periodStartDateStr + ' au ' + slotProps.data.periodEndDateStr + ')' : ''">
              <span>{{ slotProps.data.dates }}</span>
              <i v-if="slotProps.data.isDatesDiff" class="pi pi-exclamation-triangle text-[10px]"></i>
            </span>
          </template>
        </Column>
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

        <Column header="Actions" class="text-right py-1">
          <template #body="slotProps">
            <div class="flex gap-2 justify-end">
              <button @click="openStudentInfo(slotProps.data)"
                class="text-xs font-bold px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-800 dark:text-slate-200 rounded-xl transition-all flex items-center gap-1.5">
                <i class="pi pi-eye text-[9px]"></i>
                <span>Détails</span>
              </button>
              <button @click="openStudentDetails(slotProps.data)"
                class="text-xs font-bold px-2.5 py-1.5 bg-slate-100 dark:bg-slate-700 hover:bg-violet-600 hover:text-white dark:hover:bg-violet-600 rounded-xl transition-all flex items-center gap-1.5">
                <i class="pi pi-pencil text-[9px]"></i>
                <span>Suivre</span>
              </button>
            </div>
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

    <!-- Internship Details Modal -->
    <Dialog v-model:visible="showInfoDialog" modal header="Informations détaillées du stage"
      :style="{ width: '90vw', maxWidth: '650px' }" class="text-xs dark:bg-slate-800 dark:text-slate-200">
      <div v-if="selectedStudent" class="space-y-6 py-2">
        
        <!-- Section: L'Étudiant & Le Stage -->
        <div class="bg-violet-500/5 dark:bg-violet-500/10 p-4 rounded-2xl border border-violet-500/15">
          <h3 class="text-sm font-bold text-violet-700 dark:text-violet-400 flex items-center gap-2 mb-3">
            <i class="pi pi-user"></i>
            <span>L'Étudiant & Le Stage</span>
          </h3>
          <div class="grid grid-cols-2 gap-4 text-[11px]">
            <div>
              <span class="text-slate-400 block">Nom de l'étudiant</span>
              <span class="font-bold text-slate-800 dark:text-slate-200">{{ selectedStudent.studentName }}</span>
            </div>
            <div>
              <span class="text-slate-400 block">Formation</span>
              <span class="font-bold text-slate-800 dark:text-slate-200">{{ selectedStudent.period }}</span>
            </div>
            <div>
              <span class="text-slate-400 block">Durée hebdomadaire</span>
              <span class="font-bold text-slate-800 dark:text-slate-200">{{ selectedStudent.weeklyHours }} heures</span>
            </div>
            <div>
              <span class="text-slate-400 block">Dates de stage</span>
              <span class="font-bold text-slate-800 dark:text-slate-200" :class="{'text-amber-600 dark:text-amber-400': selectedStudent.isDatesDiff}">
                {{ selectedStudent.dates }}
                <i v-if="selectedStudent.isDatesDiff" class="pi pi-exclamation-triangle text-[10px] ml-1"></i>
              </span>
            </div>
          </div>
        </div>

        <!-- Section: L'Entreprise -->
        <div class="bg-slate-50 dark:bg-slate-900/40 p-4 rounded-2xl border border-slate-100 dark:border-slate-800">
          <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2 mb-3">
            <i class="pi pi-building"></i>
            <span>L'Entreprise</span>
          </h3>
          <div class="grid grid-cols-2 gap-4 text-[11px] mb-3">
            <div>
              <span class="text-slate-400 block">Raison Sociale</span>
              <span class="font-bold text-slate-800 dark:text-slate-200">{{ selectedStudent.company }}</span>
            </div>
            <div>
              <span class="text-slate-400 block">SIRET</span>
              <span class="font-bold text-slate-800 dark:text-slate-200">{{ selectedStudent.siret }}</span>
            </div>
          </div>
          <div class="text-[11px] mb-3">
            <span class="text-slate-400 block">Adresse de stage</span>
            <span class="font-bold text-slate-800 dark:text-slate-200">{{ selectedStudent.companyAddress }}</span>
          </div>
          <div class="grid grid-cols-2 gap-4 text-[11px] pt-3 border-t border-slate-200/40">
            <div>
              <span class="text-slate-400 block">Signataire de la convention</span>
              <span class="font-bold text-slate-800 dark:text-slate-200">{{ selectedStudent.signatoryName }} ({{ selectedStudent.signatoryTitle }})</span>
            </div>
            <div>
              <span class="text-slate-400 block">Contact Signataire</span>
              <span class="font-bold text-slate-800 dark:text-slate-200">{{ selectedStudent.signatoryEmail }} / {{ selectedStudent.companyPhone }}</span>
            </div>
          </div>
        </div>

        <!-- Section: Les Tuteurs -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="bg-slate-50 dark:bg-slate-900/40 p-4 rounded-2xl border border-slate-100 dark:border-slate-800">
            <h4 class="text-xs font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2 mb-2">
              <i class="pi pi-user-plus text-violet-600"></i>
              <span>Maître de Stage (Entreprise)</span>
            </h4>
            <div class="space-y-2 text-[11px]">
              <div>
                <span class="text-slate-400">Nom :</span> <span class="font-bold text-slate-800 dark:text-slate-200">{{ selectedStudent.supervisor }}</span>
              </div>
              <div>
                <span class="text-slate-400">Fonction :</span> <span class="font-bold text-slate-800 dark:text-slate-200">{{ selectedStudent.supervisorTitle }}</span>
              </div>
              <div>
                <span class="text-slate-400">Email :</span> <span class="font-bold text-slate-800 dark:text-slate-200">{{ selectedStudent.supervisorEmail }}</span>
              </div>
              <div>
                <span class="text-slate-400">Téléphone :</span> <span class="font-bold text-slate-800 dark:text-slate-200">{{ selectedStudent.supervisorPhone }}</span>
              </div>
            </div>
          </div>

          <div class="bg-slate-50 dark:bg-slate-900/40 p-4 rounded-2xl border border-slate-100 dark:border-slate-800">
            <h4 class="text-xs font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2 mb-2">
              <i class="pi pi-user text-violet-600"></i>
              <span>Tuteur Universitaire</span>
            </h4>
            <div class="space-y-2 text-[11px]">
              <div>
                <span class="text-slate-400">Nom :</span> <span class="font-bold text-slate-800 dark:text-slate-200">{{ selectedStudent.academicTutor }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Section: Sujet & Activités -->
        <div class="bg-slate-50 dark:bg-slate-900/40 p-4 rounded-2xl border border-slate-100 dark:border-slate-800">
          <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2 mb-3">
            <i class="pi pi-book"></i>
            <span>Sujet & Activités</span>
          </h3>
          <div class="space-y-3 text-[11px]">
            <div>
              <span class="text-slate-400 block">Sujet du stage</span>
              <p class="font-semibold text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900 p-2.5 rounded-xl border border-slate-100 dark:border-slate-800 mt-1 leading-relaxed">
                {{ selectedStudent.subject }}
              </p>
            </div>
            <div>
              <span class="text-slate-400 block">Activités confiées</span>
              <p class="font-semibold text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900 p-2.5 rounded-xl border border-slate-100 dark:border-slate-800 mt-1 leading-relaxed whitespace-pre-line">
                {{ selectedStudent.activities }}
              </p>
            </div>
          </div>
        </div>

      </div>
    </Dialog>

  </div>
</template>


