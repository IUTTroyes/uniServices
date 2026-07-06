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

// local form state for proofreading & editing
const parseSupervisor = (supervisorStr) => {
  if (!supervisorStr || supervisorStr === '-') {
    return { civilite: 'M', prenom: 'Robert', nom: 'LEGRAND', function: 'Tuteur en entreprise' };
  }
  const clean = supervisorStr.replace(/^(M\.|Mme\.|Mme|M)\s+/i, '').trim();
  const parts = clean.split(/\s+/);
  const civilite = supervisorStr.match(/Mme/i) ? 'Mme' : 'M';
  const prenom = parts[0] || 'Jean';
  const nom = parts.slice(1).join(' ') || 'DUPONT';
  return { civilite, prenom, nom, function: 'Tuteur en entreprise' };
};

const parseSalary = (salaryStr) => {
  if (!salaryStr || salaryStr === '-') return 4.35;
  const match = salaryStr.match(/(\d+([.,]\d+)?)/);
  return match ? parseFloat(match[1].replace(',', '.')) : 4.35;
};

const parseDatesToStartEnd = (datesStr) => {
  if (!datesStr || datesStr === '-') {
    return { start: '2026-03-02', end: '2026-06-26' };
  }
  const parts = datesStr.split(/\s+au\s+/i);
  if (parts.length === 2) {
    const parseFR = (str) => {
      const p = str.trim().split('/');
      if (p.length === 3) {
        return `${p[2]}-${p[1]}-${p[0]}`; // YYYY-MM-DD
      }
      return str;
    };
    return { start: parseFR(parts[0]), end: parseFR(parts[1]) };
  }
  return { start: '2026-03-02', end: '2026-06-26' };
};

// local form state for proofreading & editing
const editForm = ref({
  studentPhone: '',
  studentEmail: '',
  insuranceCompany: '',
  insurancePolicyNumber: '',
  companyName: '',
  companySiret: '',
  companyPhone: '',
  companyAddress: {
    adresse: '',
    complement1: '',
    complement2: '',
    ville: '',
    codePostal: '',
    pays: 'France'
  },
  signatoryCivilite: 'M',
  signatoryPrenom: '',
  signatoryNom: '',
  signatoryTitle: '',
  signatoryEmail: '',
  signatoryPhone: '',
  tuteurSameAsSignatory: false,
  supervisorCivilite: 'M',
  supervisorPrenom: '',
  supervisorNom: '',
  supervisorFunction: '',
  supervisorEmail: '',
  supervisorPhone: '',
  startDate: '',
  endDate: '',
  weeklyHours: 35,
  salaryAmount: 4.35,
  subject: '',
  activities: '',
  amenagementStage: ''
});

const openReviewRequest = (student) => {
  selectedRequest.value = student;
  tutorSelect.value = student.tutor || (props.teachers[0]?.fullName || 'M. Jean Dupont');
  rejectReason.value = '';

  const supInfo = parseSupervisor(student.supervisor);
  const datesInfo = parseDatesToStartEnd(student.dates);
  const salaryVal = parseSalary(student.salary);

  let addrObj = {
    adresse: '15 Rue de la Paix',
    complement1: '',
    complement2: '',
    ville: 'Paris',
    codePostal: '75002',
    pays: 'France'
  };
  if (student.companyAddress) {
    if (typeof student.companyAddress === 'object') {
      addrObj = { ...addrObj, ...student.companyAddress };
    } else {
      addrObj.adresse = student.companyAddress;
    }
  } else if (student.company && student.company !== '-') {
    addrObj.adresse = '15 Rue de la Paix';
  }

  // Populate local form state for editing
  editForm.value = {
    studentPhone: student.studentPhone || '06 12 34 56 78',
    studentEmail: student.studentEmail || `${student.studentName.toLowerCase().replace(/\s+/g, '.')}@univ-troyes.fr`,
    insuranceCompany: student.insuranceCompany || 'MAIF',
    insurancePolicyNumber: student.insurancePolicyNumber || '9876543-A',
    companyName: (student.company !== '-' ? student.company : '') || '',
    companySiret: student.siret || '12345678900010',
    companyPhone: student.companyPhone || '01 44 55 66 77',
    companyAddress: addrObj,
    signatoryCivilite: student.signatoryCivilite || 'M',
    signatoryPrenom: student.signatoryPrenom || 'Sylvie',
    signatoryNom: student.signatoryNom || 'MARTIN',
    signatoryTitle: student.signatoryTitle || 'Directrice RH',
    signatoryEmail: student.signatoryEmail || 's.martin@entreprise.com',
    signatoryPhone: student.signatoryPhone || '01 44 55 66 88',
    tuteurSameAsSignatory: student.tuteurSameAsSignatory ?? false,
    supervisorCivilite: student.supervisorCivilite || supInfo.civilite,
    supervisorPrenom: student.supervisorPrenom || supInfo.prenom,
    supervisorNom: student.supervisorNom || supInfo.nom,
    supervisorFunction: student.supervisorFunction || supInfo.function,
    supervisorEmail: student.supervisorEmail || 'tuteur.entreprise@domain.com',
    supervisorPhone: student.supervisorPhone || '06 99 88 77 66',
    startDate: student.startDate || datesInfo.start,
    endDate: student.endDate || datesInfo.end,
    weeklyHours: student.weeklyHours || 35,
    salaryAmount: student.salaryAmount || salaryVal,
    subject: (student.subject !== '-' ? student.subject : '') || '',
    activities: student.activities || 'Développement d\'applications au sein de l\'équipe technique.',
    amenagementStage: student.amenagementStage || ''
  };

  showReviewDialog.value = true;
};

const saveEdits = (silent = false) => {
  if (editForm.value.tuteurSameAsSignatory) {
    editForm.value.supervisorCivilite = editForm.value.signatoryCivilite;
    editForm.value.supervisorPrenom = editForm.value.signatoryPrenom;
    editForm.value.supervisorNom = editForm.value.signatoryNom;
    editForm.value.supervisorFunction = editForm.value.signatoryTitle;
    editForm.value.supervisorEmail = editForm.value.signatoryEmail;
    editForm.value.supervisorPhone = editForm.value.signatoryPhone;
  }

  const formatDateFR = (isoStr) => {
    if (!isoStr) return '';
    const parts = isoStr.split('-');
    if (parts.length === 3) {
      return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }
    return isoStr;
  };
  const datesFormatted = `${formatDateFR(editForm.value.startDate)} au ${formatDateFR(editForm.value.endDate)}`;
  const supervisorFormatted = `${editForm.value.supervisorCivilite}. ${editForm.value.supervisorPrenom} ${editForm.value.supervisorNom}`;
  const salaryFormatted = `${editForm.value.salaryAmount} €/h`;

  const updatedChanges = {
    company: editForm.value.companyName,
    siret: editForm.value.companySiret,
    dates: datesFormatted,
    subject: editForm.value.subject,
    activities: editForm.value.activities,
    supervisor: supervisorFormatted,
    salary: salaryFormatted,
    tutor: tutorSelect.value,

    studentPhone: editForm.value.studentPhone,
    studentEmail: editForm.value.studentEmail,
    insuranceCompany: editForm.value.insuranceCompany,
    insurancePolicyNumber: editForm.value.insurancePolicyNumber,
    companyPhone: editForm.value.companyPhone,
    companyAddress: { ...editForm.value.companyAddress },
    signatoryCivilite: editForm.value.signatoryCivilite,
    signatoryPrenom: editForm.value.signatoryPrenom,
    signatoryNom: editForm.value.signatoryNom,
    signatoryTitle: editForm.value.signatoryTitle,
    signatoryEmail: editForm.value.signatoryEmail,
    signatoryPhone: editForm.value.signatoryPhone,
    tuteurSameAsSignatory: editForm.value.tuteurSameAsSignatory,
    supervisorCivilite: editForm.value.supervisorCivilite,
    supervisorPrenom: editForm.value.supervisorPrenom,
    supervisorNom: editForm.value.supervisorNom,
    supervisorFunction: editForm.value.supervisorFunction,
    supervisorEmail: editForm.value.supervisorEmail,
    supervisorPhone: editForm.value.supervisorPhone,
    startDate: editForm.value.startDate,
    endDate: editForm.value.endDate,
    weeklyHours: editForm.value.weeklyHours,
    salaryAmount: editForm.value.salaryAmount,
    amenagementStage: editForm.value.amenagementStage
  };

  emit('update-student', {
    id: selectedRequest.value.id,
    changes: updatedChanges
  });

  Object.assign(selectedRequest.value, updatedChanges);

  if (!silent) {
    toast.add({
      severity: 'success',
      summary: 'Modifications enregistrées',
      detail: 'Les détails de la convention ont été mis à jour avec succès.',
      life: 3000
    });
  }
};

const approveRequest = () => {
  // Save edits first
  saveEdits(true);

  emit('update-student', {
    id: selectedRequest.value.id,
    changes: {
      conventionStatus: 'Validée',
      tutor: tutorSelect.value
    }
  });

  selectedRequest.value.conventionStatus = 'Validée';

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

  selectedRequest.value.conventionStatus = 'Rejetée';

  toast.add({
    severity: 'error',
    summary: 'Convention Rejetée',
    detail: `La demande de ${selectedRequest.value.studentName} a été rejetée. Motif : ${rejectReason.value}`,
    life: 4000
  });
  showReviewDialog.value = false;
};

const resetStudentSubmission = () => {
  if (confirm(`Voulez-vous réinitialiser la saisie de ${selectedRequest.value.studentName} ? Toutes ses données saisies seront effacées, mais il pourra de nouveau saisir sa demande immédiatement.`)) {
    emit('update-student', {
      id: selectedRequest.value.id,
      changes: {
        hasStage: false,
        company: '-',
        siret: '',
        dates: '-',
        subject: '-',
        activities: '',
        supervisor: '-',
        salary: '-',
        conventionStatus: 'Aucune',
        tutor: 'Non affecté',
        inputAuthorized: true, // Keep authorized
        reportUploaded: false,
        reportName: '',
        studentPhone: '',
        studentEmail: '',
        insuranceCompany: '',
        insurancePolicyNumber: '',
        companyPhone: '',
        companyAddress: { adresse: '', complement1: '', complement2: '', ville: '', codePostal: '', pays: 'France' },
        signatoryCivilite: 'M',
        signatoryPrenom: '',
        signatoryNom: '',
        signatoryTitle: '',
        signatoryEmail: '',
        signatoryPhone: '',
        tuteurSameAsSignatory: false,
        supervisorCivilite: 'M',
        supervisorPrenom: '',
        supervisorNom: '',
        supervisorFunction: '',
        supervisorEmail: '',
        supervisorPhone: '',
        startDate: '',
        endDate: '',
        weeklyHours: 35,
        salaryAmount: 4.35,
        amenagementStage: ''
      }
    });

    toast.add({
      severity: 'info',
      summary: 'Saisie réinitialisée',
      detail: `La saisie de ${selectedRequest.value.studentName} a été vidée. La saisie reste autorisée.`,
      life: 3500
    });
    showReviewDialog.value = false;
  }
};

const deleteStudentSubmission = () => {
  if (confirm(`Voulez-vous supprimer définitivement la demande de convention de ${selectedRequest.value.studentName} ? Cela effacera ses données et bloquera son droit de saisie.`)) {
    emit('update-student', {
      id: selectedRequest.value.id,
      changes: {
        hasStage: false,
        company: '-',
        siret: '',
        dates: '-',
        subject: '-',
        activities: '',
        supervisor: '-',
        salary: '-',
        conventionStatus: 'Aucune',
        tutor: 'Non affecté',
        inputAuthorized: false, // Revoke authorization
        reportUploaded: false,
        reportName: '',
        studentPhone: '',
        studentEmail: '',
        insuranceCompany: '',
        insurancePolicyNumber: '',
        companyPhone: '',
        companyAddress: { adresse: '', complement1: '', complement2: '', ville: '', codePostal: '', pays: 'France' },
        signatoryCivilite: 'M',
        signatoryPrenom: '',
        signatoryNom: '',
        signatoryTitle: '',
        signatoryEmail: '',
        signatoryPhone: '',
        tuteurSameAsSignatory: false,
        supervisorCivilite: 'M',
        supervisorPrenom: '',
        supervisorNom: '',
        supervisorFunction: '',
        supervisorEmail: '',
        supervisorPhone: '',
        startDate: '',
        endDate: '',
        weeklyHours: 35,
        salaryAmount: 4.35,
        amenagementStage: ''
      }
    });

    toast.add({
      severity: 'warn',
      summary: 'Demande supprimée',
      detail: `La demande de ${selectedRequest.value.studentName} a été supprimée et la saisie a été bloquée.`,
      life: 3500
    });
    showReviewDialog.value = false;
  }
};

const generatePDF = () => {
  emit('update-student', {
    id: selectedRequest.value.id,
    changes: {
      conventionStatus: 'En cours de signature'
    }
  });
  selectedRequest.value.conventionStatus = 'En cours de signature';
  toast.add({
    severity: 'success',
    summary: 'PDF Généré',
    detail: `La convention PDF pour ${selectedRequest.value.studentName} a été générée et envoyée en signature.`,
    life: 3500
  });
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

const toggleInputAuthorization = (student) => {
  const isAuthNow = !student.inputAuthorized;
  emit('update-student', {
    id: student.id,
    changes: {
      inputAuthorized: isAuthNow
    }
  });

  toast.add({
    severity: isAuthNow ? 'success' : 'info',
    summary: isAuthNow ? 'Saisie autorisée' : 'Saisie bloquée',
    detail: `Le droit de saisie pour ${student.studentName} a été ${isAuthNow ? 'ouvert' : 'verrouillé'}.`,
    life: 3000
  });
};

const getInitials = (name) => {
  if (!name) return '';
  return name.split(' ').map(n => n[0]).join('');
};

// Pipeline selection filters
const studentsToValidate = computed(() => {
  return props.periodStudents.filter(s => s.hasStage && s.conventionStatus === 'En attente');
});

const studentsToPrint = computed(() => {
  return props.periodStudents.filter(s => s.hasStage && s.conventionStatus === 'Validée');
});

const studentsSigning = computed(() => {
  return props.periodStudents.filter(s => s.hasStage && s.conventionStatus === 'En cours de signature');
});

const studentsSigned = computed(() => {
  return props.periodStudents.filter(s => s.hasStage && s.conventionStatus === 'Signée');
});

const printConvention = (student) => {
  emit('update-student', {
    id: student.id,
    changes: {
      conventionStatus: 'En cours de signature'
    }
  });
  student.conventionStatus = 'En cours de signature';
  toast.add({
    severity: 'info',
    summary: 'PDF Imprimé',
    detail: `La convention pour ${student.studentName} a été générée/imprimée et envoyée en signature.`,
    life: 3500
  });
};

const finalizeSignature = (student) => {
  emit('update-student', {
    id: student.id,
    changes: {
      conventionStatus: 'Signée'
    }
  });
  student.conventionStatus = 'Signée';
  toast.add({
    severity: 'success',
    summary: 'Signature Finalisée',
    detail: `La convention signée pour ${student.studentName} a été récupérée avec succès.`,
    life: 3500
  });
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

    <!-- Pipeline de Suivi des Conventions -->
    <div class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700/60 rounded-3xl p-6 shadow-sm space-y-4">
      <div>
        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
          <i class="pi pi-directions text-violet-600"></i>
          <span>Suivi du flux des conventions</span>
        </h3>
        <p class="text-[11px] text-slate-400 mt-0.5">Cycle de vie de la validation à la signature finale. Traitez les dossiers en un clic.</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Etape 1: A Valider -->
        <div class="bg-slate-50/50 dark:bg-slate-900/40 p-4 rounded-2xl border-t-4 border-t-amber-500 border-x border-b border-slate-100 dark:border-slate-800 flex flex-col h-full min-h-[160px]">
          <div class="flex items-center justify-between pb-3 border-b border-slate-200/50 dark:border-slate-700/50">
            <span class="font-bold text-[11px] text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
              <i class="pi pi-clock text-amber-500"></i>
              <span>1. À Valider</span>
            </span>
            <span class="bg-amber-100 dark:bg-amber-950/40 text-amber-800 dark:text-amber-400 text-[10px] px-2 py-0.5 rounded-full font-bold">
              {{ studentsToValidate.length }}
            </span>
          </div>
          <div class="flex-1 mt-3 max-h-56 overflow-y-auto space-y-2 pr-1">
            <div v-for="s in studentsToValidate" :key="s.id" class="flex items-center justify-between p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-150 dark:border-slate-750 shadow-sm transition-all hover:border-violet-300">
              <div class="flex flex-col min-w-0">
                <span class="font-bold text-xs truncate">{{ s.studentName }}</span>
                <span class="text-[9px] text-slate-450 truncate">{{ s.company }}</span>
              </div>
              <button @click="openReviewRequest(s)" class="p-1.5 bg-amber-50 dark:bg-amber-950/20 text-amber-600 dark:text-amber-400 hover:bg-amber-100 hover:text-amber-700 rounded-lg transition-all cursor-pointer" v-tooltip.left="'Ouvrir la relecture'">
                <i class="pi pi-eye text-[10px]"></i>
              </button>
            </div>
            <div v-if="studentsToValidate.length === 0" class="text-center py-6 text-[10px] text-slate-450 italic">
              Aucun dossier à valider
            </div>
          </div>
        </div>

        <!-- Etape 2: A Imprimer -->
        <div class="bg-slate-50/50 dark:bg-slate-900/40 p-4 rounded-2xl border-t-4 border-t-blue-500 border-x border-b border-slate-100 dark:border-slate-800 flex flex-col h-full min-h-[160px]">
          <div class="flex items-center justify-between pb-3 border-b border-slate-200/50 dark:border-slate-700/50">
            <span class="font-bold text-[11px] text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
              <i class="pi pi-print text-blue-500"></i>
              <span>2. À Imprimer</span>
            </span>
            <span class="bg-blue-100 dark:bg-blue-950/40 text-blue-800 dark:text-blue-400 text-[10px] px-2 py-0.5 rounded-full font-bold">
              {{ studentsToPrint.length }}
            </span>
          </div>
          <div class="flex-1 mt-3 max-h-56 overflow-y-auto space-y-2 pr-1">
            <div v-for="s in studentsToPrint" :key="s.id" class="flex items-center justify-between p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-150 dark:border-slate-750 shadow-sm transition-all hover:border-violet-300">
              <div class="flex flex-col min-w-0">
                <span class="font-bold text-xs truncate">{{ s.studentName }}</span>
                <span class="text-[9px] text-slate-450 truncate">{{ s.company }}</span>
              </div>
              <button @click="printConvention(s)" class="p-1.5 bg-blue-50 dark:bg-blue-950/20 text-blue-600 dark:text-blue-400 hover:bg-blue-100 hover:text-blue-700 rounded-lg transition-all cursor-pointer" v-tooltip.left="'Générer PDF &amp; envoyer en signature'">
                <i class="pi pi-print text-[10px]"></i>
              </button>
            </div>
            <div v-if="studentsToPrint.length === 0" class="text-center py-6 text-[10px] text-slate-455 italic">
              Aucune convention à imprimer
            </div>
          </div>
        </div>

        <!-- Etape 3: En cours de signature -->
        <div class="bg-slate-50/50 dark:bg-slate-900/40 p-4 rounded-2xl border-t-4 border-t-indigo-500 border-x border-b border-slate-100 dark:border-slate-800 flex flex-col h-full min-h-[160px]">
          <div class="flex items-center justify-between pb-3 border-b border-slate-200/50 dark:border-slate-700/50">
            <span class="font-bold text-[11px] text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
              <i class="pi pi-pencil text-indigo-550"></i>
              <span>3. En Signature</span>
            </span>
            <span class="bg-indigo-100 dark:bg-indigo-950/40 text-indigo-800 dark:text-indigo-400 text-[10px] px-2 py-0.5 rounded-full font-bold">
              {{ studentsSigning.length }}
            </span>
          </div>
          <div class="flex-1 mt-3 max-h-56 overflow-y-auto space-y-2 pr-1">
            <div v-for="s in studentsSigning" :key="s.id" class="flex items-center justify-between p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-150 dark:border-slate-750 shadow-sm transition-all hover:border-violet-300">
              <div class="flex flex-col min-w-0">
                <span class="font-bold text-xs truncate">{{ s.studentName }}</span>
                <span class="text-[9px] text-slate-455 truncate">{{ s.company }}</span>
              </div>
              <button @click="finalizeSignature(s)" class="p-1.5 bg-indigo-50 dark:bg-indigo-950/20 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 hover:text-indigo-700 rounded-lg transition-all cursor-pointer" v-tooltip.left="'Marquer comme signée/récupérée'">
                <i class="pi pi-check-square text-[10px]"></i>
              </button>
            </div>
            <div v-if="studentsSigning.length === 0" class="text-center py-6 text-[10px] text-slate-450 italic">
              Aucune convention en cours
            </div>
          </div>
        </div>

        <!-- Etape 4: Récupérée signée -->
        <div class="bg-slate-50/50 dark:bg-slate-900/40 p-4 rounded-2xl border-t-4 border-t-emerald-500 border-x border-b border-slate-100 dark:border-slate-800 flex flex-col h-full min-h-[160px]">
          <div class="flex items-center justify-between pb-3 border-b border-slate-200/50 dark:border-slate-700/50">
            <span class="font-bold text-[11px] text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
              <i class="pi pi-check-circle text-emerald-555"></i>
              <span>4. Signée / Terminé</span>
            </span>
            <span class="bg-emerald-100 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-400 text-[10px] px-2 py-0.5 rounded-full font-bold">
              {{ studentsSigned.length }}
            </span>
          </div>
          <div class="flex-1 mt-3 max-h-56 overflow-y-auto space-y-2 pr-1">
            <div v-for="s in studentsSigned" :key="s.id" class="flex items-center justify-between p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-150 dark:border-slate-750 shadow-sm transition-all">
              <div class="flex flex-col min-w-0">
                <span class="font-bold text-xs truncate">{{ s.studentName }}</span>
                <span class="text-[9px] text-slate-455 truncate">{{ s.company }}</span>
              </div>
              <div class="p-1 text-emerald-600 dark:text-emerald-400">
                <i class="pi pi-check text-[10px]"></i>
              </div>
            </div>
            <div v-if="studentsSigned.length === 0" class="text-center py-6 text-[10px] text-slate-450 italic">
              Aucune convention finalisée
            </div>
          </div>
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
            <span v-if="slotProps.data.conventionStatus === 'En attente'"
              class="bg-amber-100 dark:bg-amber-950/30 text-amber-800 dark:text-amber-300 px-2 py-0.5 rounded font-bold text-[9px] flex items-center gap-1 w-max animate-pulse">
              <i class="pi pi-clock text-[8px]"></i>
              <span>À valider</span>
            </span>
            <span v-else-if="slotProps.data.conventionStatus === 'Validée'"
              class="bg-blue-100 dark:bg-blue-950/40 text-blue-800 dark:text-blue-300 px-2 py-0.5 rounded font-bold text-[9px] flex items-center gap-1 w-max">
              <i class="pi pi-print text-[8px]"></i>
              <span>À imprimer</span>
            </span>
            <span v-else-if="slotProps.data.conventionStatus === 'En cours de signature'"
              class="bg-indigo-100 dark:bg-indigo-950/40 text-indigo-800 dark:text-indigo-300 px-2 py-0.5 rounded font-bold text-[9px] flex items-center gap-1 w-max">
              <i class="pi pi-pencil text-[8px]"></i>
              <span>En signature</span>
            </span>
            <span v-else-if="slotProps.data.conventionStatus === 'Signée'"
              class="bg-emerald-100 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 px-2 py-0.5 rounded font-bold text-[9px] flex items-center gap-1 w-max">
              <i class="pi pi-check-circle text-[8px]"></i>
              <span>Signée</span>
            </span>
            <span v-else-if="slotProps.data.conventionStatus === 'Rejetée'"
              class="bg-rose-100 dark:bg-rose-950/30 text-rose-800 dark:text-rose-400 px-2 py-0.5 rounded font-bold text-[9px] flex items-center gap-1 w-max">
              <i class="pi pi-times-circle text-[8px]"></i>
              <span>Refusée</span>
            </span>
            <div v-else>
              <!-- Toggle input authorization for student without stage -->
              <button 
                @click="toggleInputAuthorization(slotProps.data)"
                :class="[
                  'px-2 py-0.5 rounded text-[9px] font-bold transition-all duration-200 flex items-center gap-1 cursor-pointer select-none border shadow-sm',
                  slotProps.data.inputAuthorized 
                    ? 'bg-emerald-50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-400 border-emerald-250 dark:border-emerald-900/40 hover:bg-emerald-100 dark:hover:bg-emerald-900/20' 
                    : 'bg-slate-50 dark:bg-slate-900/40 text-slate-550 dark:text-slate-450 border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800'
                ]"
              >
                <i :class="['pi text-[8px]', slotProps.data.inputAuthorized ? 'pi-lock-open text-emerald-500' : 'pi-lock text-slate-400']"></i>
                <span>{{ slotProps.data.inputAuthorized ? 'Saisie ouverte' : 'Saisie bloquée' }}</span>
              </button>
            </div>
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
        <Column header="Actions" class="text-right min-w-[125px]">
          <template #body="slotProps">
            <!-- Case A: Convention needs validation -->
            <button v-if="slotProps.data.conventionStatus === 'En attente'" @click="openReviewRequest(slotProps.data)"
              class="text-[10px] font-bold px-3 py-1.5 bg-violet-600 hover:bg-violet-700 text-white rounded-lg transition-all flex items-center gap-1.5 ml-auto cursor-pointer">
              <i class="pi pi-check text-[8px]"></i>
              <span>Valider</span>
            </button>

            <!-- Case B: Convention needs printing -->
            <button v-else-if="slotProps.data.conventionStatus === 'Validée'" @click="printConvention(slotProps.data)"
              class="text-[10px] font-bold px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all flex items-center gap-1.5 ml-auto cursor-pointer">
              <i class="pi pi-print text-[8px]"></i>
              <span>Imprimer</span>
            </button>

            <!-- Case C: Convention in signature -->
            <button v-else-if="slotProps.data.conventionStatus === 'En cours de signature'" @click="finalizeSignature(slotProps.data)"
              class="text-[10px] font-bold px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-all flex items-center gap-1.5 ml-auto cursor-pointer">
              <i class="pi pi-check-square text-[8px]"></i>
              <span>Finaliser</span>
            </button>

            <!-- Case D: Student has no placement, send reminder -->
            <button v-else-if="!slotProps.data.hasStage" @click="contactStudent(slotProps.data.studentName)"
              class="text-[10px] font-bold px-3 py-1.5 bg-slate-50 dark:bg-slate-700 hover:bg-slate-100 dark:hover:bg-slate-600/60 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-600 rounded-lg transition-all flex items-center gap-1.5 ml-auto cursor-pointer">
              <i class="pi pi-envelope text-[8px]"></i>
              <span>Contacter</span>
            </button>

            <!-- Case E: Convention already signed / rejected, show detailed sheet -->
            <button v-else @click="openReviewRequest(slotProps.data)"
              class="text-[10px] font-bold px-3 py-1.5 bg-slate-50 dark:bg-slate-700 hover:bg-slate-100 dark:hover:bg-slate-600/60 text-slate-700 dark:text-slate-300 rounded-lg transition-all flex items-center gap-1.5 ml-auto cursor-pointer">
              <i class="pi pi-info-circle text-[8px]"></i>
              <span>Détails</span>
            </button>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- DIALOG: EXAMINER / VALIDATION FLOW & DETAIL SHEET -->
    <Dialog v-model:visible="showReviewDialog" modal header="Instruction de la demande de convention"
      :style="{ width: '90vw', maxWidth: '850px' }" class="text-xs dark:bg-slate-800 dark:text-slate-200">
      <div v-if="selectedRequest" class="space-y-6 py-4">
        <!-- Relecture & Édition des informations de stage/convention -->
        <div v-if="selectedRequest.hasStage" class="space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
            <h4 class="text-xs font-black uppercase tracking-wider text-violet-600 dark:text-violet-400 flex items-center gap-1.5">
              <i class="pi pi-file-edit"></i>
              <span>Relecture et modification des données (Responsable)</span>
            </h4>
            <span :class="['px-2.5 py-0.5 text-[10px] font-bold rounded-full uppercase tracking-wider', 
              selectedRequest.conventionStatus === 'Validée' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300' :
              selectedRequest.conventionStatus === 'Rejetée' ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-300' :
              'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300'
            ]">
              Statut : {{ selectedRequest.conventionStatus }}
            </span>
          </div>

          <!-- Nom étudiant (Lecture seule) -->
          <div class="flex flex-col gap-1 bg-slate-50 dark:bg-slate-900/60 p-3 rounded-xl border border-slate-100 dark:border-slate-800">
            <span class="font-bold text-slate-400 text-[10px] uppercase">Étudiant déclarant</span>
            <span class="font-black text-sm text-slate-800 dark:text-slate-200 mt-0.5">{{ selectedRequest.studentName }}</span>
          </div>

          <div class="space-y-6">
            <!-- Section 1 : Profil Étudiant & Assurances -->
            <div class="bg-slate-50/50 dark:bg-slate-900/40 p-4 rounded-2xl border border-slate-100 dark:border-slate-800 space-y-4">
              <h5 class="text-xs font-bold text-violet-750 dark:text-violet-400 flex items-center gap-2 pb-2 border-b border-slate-200/60 dark:border-slate-700/60">
                <i class="pi pi-user text-xs"></i>
                <span>1. Profil Étudiant &amp; Assurances</span>
              </h5>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex flex-col gap-1">
                  <label class="font-bold text-slate-500 dark:text-slate-400">Téléphone personnel</label>
                  <input v-model="editForm.studentPhone" type="text" class="w-full p-2.5 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-violet-500" />
                </div>
                <div class="flex flex-col gap-1">
                  <label class="font-bold text-slate-500 dark:text-slate-400">E-mail personnel (de secours)</label>
                  <input v-model="editForm.studentEmail" type="email" class="w-full p-2.5 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-violet-500" />
                </div>
                <div class="flex flex-col gap-1">
                  <label class="font-bold text-slate-500 dark:text-slate-400">Compagnie d'assurance RC</label>
                  <input v-model="editForm.insuranceCompany" type="text" class="w-full p-2.5 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-violet-500" />
                </div>
                <div class="flex flex-col gap-1">
                  <label class="font-bold text-slate-500 dark:text-slate-400">Numéro de police d'assurance</label>
                  <input v-model="editForm.insurancePolicyNumber" type="text" class="w-full p-2.5 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-violet-500" />
                </div>
              </div>
            </div>

            <!-- Section 2 : Entreprise d'accueil -->
            <div class="bg-slate-50/50 dark:bg-slate-900/40 p-4 rounded-2xl border border-slate-100 dark:border-slate-800 space-y-4">
              <h5 class="text-xs font-bold text-violet-755 dark:text-violet-400 flex items-center gap-2 pb-2 border-b border-slate-200/60 dark:border-slate-700/60">
                <i class="pi pi-briefcase text-xs"></i>
                <span>2. Entreprise d'accueil</span>
              </h5>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex flex-col gap-1">
                  <label class="font-bold text-slate-500 dark:text-slate-400">Raison Sociale</label>
                  <input v-model="editForm.companyName" type="text" class="w-full p-2.5 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-violet-500" />
                </div>
                <div class="flex flex-col gap-1">
                  <label class="font-bold text-slate-500 dark:text-slate-400">Numéro SIRET</label>
                  <input v-model="editForm.companySiret" type="text" class="w-full p-2.5 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-violet-500" />
                </div>
                <div class="flex flex-col gap-1 md:col-span-2">
                  <label class="font-bold text-slate-500 dark:text-slate-400">Téléphone standard entreprise</label>
                  <input v-model="editForm.companyPhone" type="text" class="w-full p-2.5 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-violet-500" />
                </div>
                
                <!-- Adresse de l'entreprise -->
                <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-3 bg-white dark:bg-slate-900/60 p-3 rounded-xl border border-slate-200/60 dark:border-slate-700/60">
                  <div class="flex flex-col gap-1 md:col-span-3">
                    <label class="font-bold text-slate-500 dark:text-slate-400 font-semibold">Adresse (Rue, Avenue...)</label>
                    <input v-model="editForm.companyAddress.adresse" type="text" class="w-full p-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none" />
                  </div>
                  <div class="flex flex-col gap-1">
                    <label class="font-bold text-slate-500 dark:text-slate-400 font-semibold">Code Postal</label>
                    <input v-model="editForm.companyAddress.codePostal" type="text" class="w-full p-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none" />
                  </div>
                  <div class="flex flex-col gap-1">
                    <label class="font-bold text-slate-500 dark:text-slate-400 font-semibold">Ville</label>
                    <input v-model="editForm.companyAddress.ville" type="text" class="w-full p-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none" />
                  </div>
                  <div class="flex flex-col gap-1">
                    <label class="font-bold text-slate-500 dark:text-slate-400 font-semibold">Pays</label>
                    <input v-model="editForm.companyAddress.pays" type="text" class="w-full p-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none" />
                  </div>
                </div>
              </div>
            </div>

            <!-- Section 3 : Représentant Légal (Signataire) -->
            <div class="bg-slate-50/50 dark:bg-slate-900/40 p-4 rounded-2xl border border-slate-100 dark:border-slate-800 space-y-4">
              <h5 class="text-xs font-bold text-violet-755 dark:text-violet-400 flex items-center gap-2 pb-2 border-b border-slate-200/60 dark:border-slate-700/60">
                <i class="pi pi-id-card text-xs"></i>
                <span>3. Représentant Légal (Signataire de la convention)</span>
              </h5>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="flex flex-col gap-1">
                  <label class="font-bold text-slate-500 dark:text-slate-400">Civilité</label>
                  <select v-model="editForm.signatoryCivilite" class="w-full p-2.5 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-violet-500">
                    <option value="M">Monsieur (M.)</option>
                    <option value="Mme">Madame (Mme)</option>
                  </select>
                </div>
                <div class="flex flex-col gap-1">
                  <label class="font-bold text-slate-500 dark:text-slate-400">Prénom</label>
                  <input v-model="editForm.signatoryPrenom" type="text" class="w-full p-2.5 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-violet-500" />
                </div>
                <div class="flex flex-col gap-1">
                  <label class="font-bold text-slate-500 dark:text-slate-400">Nom</label>
                  <input v-model="editForm.signatoryNom" type="text" class="w-full p-2.5 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-violet-500" />
                </div>
                <div class="flex flex-col gap-1 md:col-span-3">
                  <label class="font-bold text-slate-500 dark:text-slate-400">Fonction / Titre</label>
                  <input v-model="editForm.signatoryTitle" type="text" class="w-full p-2.5 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-violet-500" />
                </div>
                <div class="flex flex-col gap-1 md:col-span-2">
                  <label class="font-bold text-slate-500 dark:text-slate-400">E-mail direct</label>
                  <input v-model="editForm.signatoryEmail" type="email" class="w-full p-2.5 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-violet-500" />
                </div>
                <div class="flex flex-col gap-1">
                  <label class="font-bold text-slate-500 dark:text-slate-400">Téléphone direct</label>
                  <input v-model="editForm.signatoryPhone" type="text" class="w-full p-2.5 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-violet-500" />
                </div>
              </div>
            </div>

            <!-- Section 4 : Maître de Stage (Tuteur entreprise) -->
            <div class="bg-slate-50/50 dark:bg-slate-900/40 p-4 rounded-2xl border border-slate-100 dark:border-slate-800 space-y-4">
              <h5 class="text-xs font-bold text-violet-755 dark:text-violet-400 flex items-center gap-2 pb-2 border-b border-slate-200/60 dark:border-slate-700/60">
                <i class="pi pi-user-edit text-xs"></i>
                <span>4. Maître de Stage (Tuteur entreprise)</span>
              </h5>
              
              <!-- Checkbox tuteurSameAsSignatory -->
              <label class="flex items-center gap-3 cursor-pointer select-none group w-fit">
                <div class="relative">
                  <input type="checkbox" v-model="editForm.tuteurSameAsSignatory" class="sr-only peer" />
                  <div class="w-10 h-5 bg-slate-200 dark:bg-slate-700 rounded-full peer-checked:bg-violet-600 transition-all"></div>
                  <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full transition-all peer-checked:translate-x-5 shadow-sm"></div>
                </div>
                <span class="text-xs font-semibold text-slate-700 dark:text-slate-300 group-hover:text-violet-600 transition-colors">
                  Le maître de stage est le même que le représentant légal
                </span>
              </label>

              <!-- Champs du tuteur, visibles si différent -->
              <div v-if="!editForm.tuteurSameAsSignatory" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="flex flex-col gap-1">
                  <label class="font-bold text-slate-500 dark:text-slate-400">Civilité</label>
                  <select v-model="editForm.supervisorCivilite" class="w-full p-2.5 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-violet-500">
                    <option value="M">Monsieur (M.)</option>
                    <option value="Mme">Madame (Mme)</option>
                  </select>
                </div>
                <div class="flex flex-col gap-1">
                  <label class="font-bold text-slate-500 dark:text-slate-400">Prénom</label>
                  <input v-model="editForm.supervisorPrenom" type="text" class="w-full p-2.5 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-violet-500" />
                </div>
                <div class="flex flex-col gap-1">
                  <label class="font-bold text-slate-500 dark:text-slate-400">Nom</label>
                  <input v-model="editForm.supervisorNom" type="text" class="w-full p-2.5 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-violet-500" />
                </div>
                <div class="flex flex-col gap-1 md:col-span-3">
                  <label class="font-bold text-slate-500 dark:text-slate-400">Fonction</label>
                  <input v-model="editForm.supervisorFunction" type="text" class="w-full p-2.5 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-violet-500" />
                </div>
                <div class="flex flex-col gap-1 md:col-span-2">
                  <label class="font-bold text-slate-500 dark:text-slate-400">E-mail</label>
                  <input v-model="editForm.supervisorEmail" type="email" class="w-full p-2.5 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-violet-500" />
                </div>
                <div class="flex flex-col gap-1">
                  <label class="font-bold text-slate-500 dark:text-slate-400">Téléphone</label>
                  <input v-model="editForm.supervisorPhone" type="text" class="w-full p-2.5 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-violet-500" />
                </div>
              </div>
              <div v-else class="p-3 bg-violet-50 dark:bg-violet-950/20 border border-violet-200 dark:border-violet-800/40 rounded-xl text-violet-800 dark:text-violet-300">
                <i class="pi pi-info-circle text-xs mr-1"></i>
                Le maître de stage est identique au signataire de la convention.
              </div>
            </div>

            <!-- Section 5 : Modalités & Mission -->
            <div class="bg-slate-50/50 dark:bg-slate-900/40 p-4 rounded-2xl border border-slate-100 dark:border-slate-800 space-y-4">
              <h5 class="text-xs font-bold text-violet-755 dark:text-violet-400 flex items-center gap-2 pb-2 border-b border-slate-200/60 dark:border-slate-700/60">
                <i class="pi pi-file-edit text-xs"></i>
                <span>5. Modalités &amp; Mission</span>
              </h5>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex flex-col gap-1">
                  <label class="font-bold text-slate-500 dark:text-slate-400">Date de début</label>
                  <input v-model="editForm.startDate" type="date" class="w-full p-2.5 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-violet-500" />
                </div>
                <div class="flex flex-col gap-1">
                  <label class="font-bold text-slate-500 dark:text-slate-400">Date de fin</label>
                  <input v-model="editForm.endDate" type="date" class="w-full p-2.5 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-violet-500" />
                </div>
                <div class="flex flex-col gap-1">
                  <label class="font-bold text-slate-500 dark:text-slate-400">Volume horaire hebdomadaire</label>
                  <input v-model="editForm.weeklyHours" type="number" class="w-full p-2.5 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-violet-500" />
                </div>
                <div class="flex flex-col gap-1">
                  <label class="font-bold text-slate-500 dark:text-slate-400">Gratification horaire nette (€/h)</label>
                  <input v-model="editForm.salaryAmount" type="number" step="0.01" class="w-full p-2.5 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-violet-500" />
                </div>
                <div class="flex flex-col gap-1 md:col-span-2">
                  <label class="font-bold text-slate-500 dark:text-slate-400">Tuteur Universitaire (IUT)</label>
                  <select v-model="tutorSelect" class="w-full p-2.5 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-violet-500">
                    <option v-for="t in teachers" :key="t.iri || t" :value="t.fullName || t">{{ t.fullName || t }}</option>
                  </select>
                </div>
                <div class="flex flex-col gap-1 md:col-span-2">
                  <label class="font-bold text-slate-500 dark:text-slate-400">Sujet du stage / Mission principale</label>
                  <input v-model="editForm.subject" type="text" class="w-full p-2.5 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-violet-500" />
                </div>
                <div class="flex flex-col gap-1 md:col-span-2">
                  <label class="font-bold text-slate-500 dark:text-slate-400">Détail des activités confiées</label>
                  <textarea v-model="editForm.activities" rows="3" class="w-full p-2.5 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-violet-500"></textarea>
                </div>
                <div class="flex flex-col gap-1 md:col-span-2">
                  <label class="font-bold text-slate-500 dark:text-slate-400">Aménagements éventuels (ex: Télétravail...)</label>
                  <input v-model="editForm.amenagementStage" type="text" class="w-full p-2.5 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-violet-500" />
                </div>
              </div>
            </div>
          </div>

          <div class="flex justify-end gap-2 pt-2">
            <button @click="saveEdits(false)" class="px-4 py-2 bg-slate-900 dark:bg-slate-950 hover:bg-slate-800 text-white font-bold rounded-xl text-xs flex items-center gap-1.5 transition-all">
              <i class="pi pi-save"></i>
              <span>Enregistrer les corrections</span>
            </button>
          </div>
        </div>

        <!-- If student doesn't have a stage yet (Case B / active search) -->
        <div v-else class="space-y-4">
          <div class="bg-slate-50 dark:bg-slate-900/40 border border-slate-100 dark:border-slate-800 rounded-2xl p-6 text-center">
            <div class="w-12 h-12 bg-violet-50 dark:bg-violet-500/10 text-violet-600 rounded-full flex items-center justify-center mx-auto mb-3">
              <i class="pi pi-search text-xl"></i>
            </div>
            <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200">Étudiant en recherche active de stage</h4>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 max-w-md mx-auto leading-relaxed">
              Cet étudiant ({{ selectedRequest.studentName }}) n'a pas encore déclaré d'entreprise ni saisi ses informations de convention.
            </p>
            
            <div class="mt-6 flex flex-col sm:flex-row justify-center items-center gap-4">
              <div class="flex items-center gap-3">
                <span class="text-xs font-bold text-slate-600 dark:text-slate-400">Droit de saisie étudiant :</span>
                <button 
                  @click="toggleInputAuthorization(selectedRequest)"
                  :class="[
                    'px-3 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 border shadow-sm cursor-pointer',
                    selectedRequest.inputAuthorized 
                      ? 'bg-emerald-50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-900/40 hover:bg-emerald-100' 
                      : 'bg-slate-100 text-slate-650 dark:bg-slate-700/50 dark:text-slate-400 border-slate-200 dark:border-slate-600 hover:bg-slate-200'
                  ]"
                >
                  <i :class="['pi text-xs', selectedRequest.inputAuthorized ? 'pi-lock-open' : 'pi-lock']"></i>
                  <span>{{ selectedRequest.inputAuthorized ? 'Autorisée' : 'Bloquée' }}</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Footing buttons -->
        <div class="border-t border-slate-100 dark:border-slate-700/50 pt-5 flex flex-wrap items-center justify-between gap-4">
          <!-- Left side: Reset / Delete (Danger Zone) -->
          <div v-if="selectedRequest.hasStage" class="flex gap-2">
            <button @click="resetStudentSubmission" class="px-3.5 py-2 border border-amber-200 dark:border-amber-900/40 hover:bg-amber-50 dark:hover:bg-amber-950/20 text-amber-700 dark:text-amber-400 font-bold rounded-xl text-[11px] transition-all flex items-center gap-1.5 cursor-pointer">
              <i class="pi pi-refresh"></i>
              <span>Réinitialiser la saisie</span>
            </button>
            <button @click="deleteStudentSubmission" class="px-3.5 py-2 border border-red-200 dark:border-red-900/40 hover:bg-red-50 dark:hover:bg-red-950/20 text-red-600 dark:text-red-400 font-bold rounded-xl text-[11px] transition-all flex items-center gap-1.5 cursor-pointer">
              <i class="pi pi-trash"></i>
              <span>Supprimer la demande</span>
            </button>
          </div>
          <div v-else></div>

          <!-- Right side: Validate / Reject / Generate PDF / Close -->
          <div class="flex gap-2 ml-auto">
            <!-- If validated, show Generate PDF button -->
            <button v-if="selectedRequest.hasStage && selectedRequest.conventionStatus === 'Validée'" @click="generatePDF" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs flex items-center gap-1.5 transition-all cursor-pointer">
              <i class="pi pi-file-pdf"></i>
              <span>Générer la convention PDF</span>
            </button>

            <!-- If in signature, show Finalize signature button -->
            <button v-if="selectedRequest.hasStage && selectedRequest.conventionStatus === 'En cours de signature'" @click="finalizeSignature(selectedRequest); showReviewDialog = false" class="px-4 py-2 bg-indigo-650 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs flex items-center gap-1.5 transition-all cursor-pointer">
              <i class="pi pi-check-square"></i>
              <span>Finaliser la signature</span>
            </button>

            <!-- Validate / Reject (Only if status is En attente) -->
            <div v-if="selectedRequest.hasStage && selectedRequest.conventionStatus === 'En attente'" class="flex gap-2">
              <button @click="showReviewDialog = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 font-bold rounded-xl text-xs transition-all cursor-pointer">
                Annuler
              </button>
              
              <!-- Rejection input context -->
              <div class="flex items-center gap-2 border-l border-slate-200 dark:border-slate-700 pl-3">
                <input v-model="rejectReason" type="text" placeholder="Motif de rejet..." class="p-2 text-xs border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:outline-none w-[140px]" />
                <button @click="rejectRequest" class="px-3.5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs transition-all flex items-center gap-1 cursor-pointer">
                  <i class="pi pi-times"></i>
                  <span>Rejeter</span>
                </button>
              </div>

              <button @click="approveRequest" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs flex items-center gap-1.5 transition-all cursor-pointer">
                <i class="pi pi-check"></i>
                <span>Valider la convention</span>
              </button>
            </div>

            <!-- Standard Close Button -->
            <button v-else @click="showReviewDialog = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 font-bold rounded-xl text-xs transition-all cursor-pointer">
              Fermer
            </button>
          </div>
        </div>
      </div>
    </Dialog>
  </div>
</template>
