<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';

// Import Shared Components from @components library
import { HeaderComponent, Card, Kpi, SimpleSkeleton, EmptyState } from '@components';
import BlocHelp from '@components/components/BlocHelp.vue';

// Import Services for real API platform requests
import { getStagePeriodesService, getStageEtudiantsService } from '@/requests/stage_service';
import { useUsersStore } from '@stores';

// Import Heroicons for KPIs and UI icons
import {
  AcademicCapIcon,
  BriefcaseIcon,
  CheckCircleIcon,
  ClockIcon,
  DocumentTextIcon,
  FolderIcon,
  BuildingOfficeIcon,
  ArrowDownTrayIcon,
  SparklesIcon,
  ExclamationTriangleIcon,
  PencilSquareIcon,
  CloudArrowUpIcon,
  TrashIcon,
  EyeIcon,
  CheckBadgeIcon,
  LockClosedIcon,
  LockOpenIcon,
  MapPinIcon,
  CurrencyEuroIcon,
  UserGroupIcon
} from '@heroicons/vue/24/outline';

const router = useRouter();
const toast = useToast();
const userStore = useUsersStore();

// Loading & Data State
const isLoading = ref(true);

// Current student state & tabs
const currentStudentYear = ref('BUT3'); // 'BUT1' | 'BUT2' | 'BUT3'
const activePeriodTab = ref('BUT3');   // Active academic year tab selected
const activeSubTabs = ref({});          // Map: periodId -> subTab ('overview' | 'documents' | 'offers' | 'report')

// Upload state simulation
const fileInput = ref(null);
const isUploading = ref(false);
const uploadProgress = ref(0);
const activeUploadPeriodId = ref(null);

// Modal state for offer details
const selectedOffer = ref(null);
const isOfferModalOpen = ref(false);

// Collapsible instructions state
const openedPeriodDetails = ref({});
const togglePeriodDetails = (id) => {
  openedPeriodDetails.value[id] = !openedPeriodDetails.value[id];
};

const isDetailsOpen = (id, hasStage) => {
  return openedPeriodDetails.value[id] !== undefined
    ? openedPeriodDetails.value[id]
    : !hasStage; // Open by default if no stage declared yet
};

// Get current active sub-tab for a period (default: 'overview')
const getSubTab = (periodId) => {
  return activeSubTabs.value[periodId] || 'overview';
};
const setSubTab = (periodId, tabName) => {
  activeSubTabs.value[periodId] = tabName;
};

// Pure API Academic History (No Mock Data)
const studentAcademicHistory = ref({
  BUT1: { yearLabel: 'BUT 1 - Année Universitaire 2023-2024', periods: [] },
  BUT2: { yearLabel: 'BUT 2 - Année Universitaire 2024-2025', periods: [] },
  BUT3: { yearLabel: 'BUT 3 - Année Universitaire 2025-2026', periods: [] }
});

// REAL API FETCHING & DATA MAPPING
const fetchDashboardData = async () => {
  isLoading.value = true;
  try {
    const apiPeriods = await getStagePeriodesService({}, false);
    const apiStages = await getStageEtudiantsService({}, false);

    const history = {
      BUT1: { yearLabel: 'BUT 1 - Année Universitaire 2023-2024', periods: [] },
      BUT2: { yearLabel: 'BUT 2 - Année Universitaire 2024-2025', periods: [] },
      BUT3: { yearLabel: 'BUT 3 - Année Universitaire 2025-2026', periods: [] }
    };

    if (Array.isArray(apiPeriods)) {
      apiPeriods.forEach(p => {
        // Determine Academic Year Tab
        let yearKey = 'BUT3';
        const lib = (p.libelle || '').toUpperCase();
        const semLib = (p.semestreProgramme?.libelle || '').toUpperCase();
        const anneeUnivLib = p.anneeUniversitaire?.libelle || '';

        if (lib.includes('BUT 1') || lib.includes('BUT1') || semLib === 'S1' || semLib === 'S2' || anneeUnivLib.includes('2023-2024')) {
          yearKey = 'BUT1';
        } else if (lib.includes('BUT 2') || lib.includes('BUT2') || semLib === 'S3' || semLib === 'S4' || anneeUnivLib.includes('2024-2025')) {
          yearKey = 'BUT2';
        }

        // Match student stage for this period
        const matchedStage = Array.isArray(apiStages) 
          ? apiStages.find(s => s.stagePeriode?.id === p.id || s.stagePeriode === `/api/stage_periodes/${p.id}`) 
          : null;

        // Responsable & Co-responsables names from ORM entities
        const respName = p.responsablePrincipal 
          ? `${p.responsablePrincipal.prenom || ''} ${p.responsablePrincipal.nom || ''}`.trim()
          : 'Non attribué';

        const coResps = Array.isArray(p.coResponsables)
          ? p.coResponsables.map(c => `${c.prenom || ''} ${c.nom || ''}`.trim())
          : [];

        // Check if student's current semester is in semestresSaisie
        const inputAuth = p.semestresSaisie && Array.isArray(p.semestresSaisie) ? p.semestresSaisie.length > 0 : true;

        const periodObj = {
          id: p.id,
          periodName: p.libelle,
          dates: p.dateDebut && p.dateFin 
            ? `${new Date(p.dateDebut).toLocaleDateString('fr-FR')} au ${new Date(p.dateFin).toLocaleDateString('fr-FR')}` 
            : 'Dates à venir',
          duration: p.nbSemaines ? `${p.nbSemaines} semaines` : 'Non précisée',
          responsible: respName,
          responsablePrincipal: respName,
          coResponsables: coResps,
          description: p.description,
          instructions: p.commentaireLibre || p.description || 'Consignes de stage rattachées à la période.',
          competencesVisees: p.competencesVisees,
          modalitesEvaluationPedagogique: p.modalitesEvaluationPedagogique,
          modalitesEvaluationEntreprise: p.modalitesEvaluationEntreprise,
          modalitesEncadrement: p.modalitesEncadrement,
          documentsRendre: p.documentsRendre,
          inputAuthorized: inputAuth,
          documents: p.consignesFichiers || [],
          offers: [], // L'entité API Offres de stage n'est pas encore créée en backend
          hasStage: !!matchedStage,
          stage: matchedStage ? {
            company: matchedStage.entreprise?.raisonSociale || matchedStage.entrepriseNom || 'Entreprise d\'accueil',
            address: matchedStage.entreprise?.adresse?.adresse || 'Adresse entreprise',
            subject: matchedStage.sujetStage || 'Sujet du stage',
            dates: matchedStage.dateDebutStage && matchedStage.dateFinStage 
              ? `${new Date(matchedStage.dateDebutStage).toLocaleDateString('fr-FR')} au ${new Date(matchedStage.dateFinStage).toLocaleDateString('fr-FR')}`
              : 'Dates du stage',
            academicTutor: matchedStage.tuteurUniversitaire ? `${matchedStage.tuteurUniversitaire.prenom || ''} ${matchedStage.tuteurUniversitaire.nom || ''}` : respName,
            companySupervisor: matchedStage.tuteur ? `${matchedStage.tuteur.prenom || ''} ${matchedStage.tuteur.nom || ''}` : 'Maître de stage',
            status: matchedStage.evaluationNote ? 'Terminé' : (matchedStage.reportUploaded ? 'Rapport Déposé' : 'En cours - Convention signée'),
            statusClass: matchedStage.evaluationNote ? 'bg-emerald-100 text-emerald-800' : 'bg-indigo-100 text-indigo-800',
            reportUploaded: !!matchedStage.reportUploaded,
            reportName: matchedStage.reportName || (matchedStage.reportUploaded ? 'Rapport_Stage_Final.pdf' : ''),
            reportDate: matchedStage.reportDate || '',
            grade: matchedStage.evaluationNote ? `${matchedStage.evaluationNote} / 20` : null,
            workflowSteps: [
              { id: 1, label: 'Demande saisie', completed: true, date: 'Validé' },
              { id: 2, label: 'Validation responsable', completed: true, date: 'Validé' },
              { id: 3, label: 'Convention générée', completed: true, date: 'Validé' },
              { id: 4, label: 'Signatures collectées', completed: true, date: 'Validé' },
              { id: 5, label: 'Dépôt du rapport', completed: !!matchedStage.reportUploaded, date: matchedStage.reportDate || 'En attente' }
            ]
          } : null
        };

        history[yearKey].periods.push(periodObj);
      });
    }

    studentAcademicHistory.value = history;
  } catch (err) {
    console.error('Erreur lors du chargement des données API Stage:', err);
    toast.add({
      severity: 'error',
      summary: 'Erreur d\'API',
      detail: 'Impossible de récupérer les périodes de stage depuis le serveur.',
      life: 4000
    });
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  fetchDashboardData();
});

// Computed properties for adaptive layout and filters
const availableYears = ['BUT1', 'BUT2', 'BUT3'];

const visibleYears = computed(() => {
  return availableYears.filter(year => {
    const getIndex = (y) => parseInt(y.replace('BUT', ''));
    return getIndex(year) <= getIndex(currentStudentYear.value);
  });
});

// Watch visible years to reset selection if selected year becomes invisible
watch(visibleYears, (newYears) => {
  if (!newYears.includes(activePeriodTab.value)) {
    activePeriodTab.value = newYears[newYears.length - 1] || 'BUT1';
  }
});

const currentYearData = computed(() => {
  return studentAcademicHistory.value[activePeriodTab.value];
});

const currentPeriods = computed(() => {
  return currentYearData.value ? currentYearData.value.periods : [];
});

// Computed KPIs for top view summary
const summaryKpis = computed(() => {
  const periods = currentPeriods.value;
  const total = periods.length;
  const withStage = periods.filter(p => p.hasStage).length;
  const reportsUploaded = periods.filter(p => p.hasStage && p.stage?.reportUploaded).length;
  
  return {
    totalPeriods: total,
    stagesCount: withStage,
    searchCount: total - withStage,
    reportsCount: reportsUploaded
  };
});

// Actions & Handlers
const triggerFileUpload = (periodId) => {
  activeUploadPeriodId.value = periodId;
  if (fileInput.value) {
    fileInput.value.click();
  }
};

const handleFileChange = (e) => {
  const file = e.target.files[0];
  if (!file || activeUploadPeriodId.value === null) return;

  const targetId = activeUploadPeriodId.value;
  isUploading.value = true;
  uploadProgress.value = 10;
  
  const interval = setInterval(() => {
    uploadProgress.value += 30;
    if (uploadProgress.value >= 100) {
      clearInterval(interval);
      isUploading.value = false;
      
      for (const year of Object.keys(studentAcademicHistory.value)) {
        const period = studentAcademicHistory.value[year].periods.find(p => p.id === targetId);
        if (period && period.stage) {
          period.stage.reportUploaded = true;
          period.stage.reportName = file.name;
          period.stage.reportDate = new Date().toLocaleDateString('fr-FR');
          
          if (period.stage.workflowSteps) {
            const reportStep = period.stage.workflowSteps.find(s => s.id === 5);
            if (reportStep) {
              reportStep.completed = true;
              reportStep.date = new Date().toLocaleDateString('fr-FR');
            }
          }
          break;
        }
      }

      toast.add({
        severity: 'success',
        summary: 'Fichier déposé',
        detail: `Le document "${file.name}" a été transmis avec succès.`,
        life: 4000
      });
      activeUploadPeriodId.value = null;
    }
  }, 400);
};

const deleteReport = (periodId) => {
  for (const year of Object.keys(studentAcademicHistory.value)) {
    const period = studentAcademicHistory.value[year].periods.find(p => p.id === periodId);
    if (period && period.stage) {
      period.stage.reportUploaded = false;
      period.stage.reportName = '';
      period.stage.reportDate = '';
      
      if (period.stage.workflowSteps) {
        const reportStep = period.stage.workflowSteps.find(s => s.id === 5);
        if (reportStep) {
          reportStep.completed = false;
          reportStep.date = 'En attente';
        }
      }
      break;
    }
  }

  toast.add({
    severity: 'info',
    summary: 'Dépôt annulé',
    detail: 'Votre rapport de stage a été retiré.',
    life: 3000
  });
};

const navigateToRequest = (periodId) => {
  router.push({ name: 'ConventionRequest', query: { periodId } });
};

const downloadDoc = (docName) => {
  toast.add({
    severity: 'success',
    summary: 'Téléchargement lancé',
    detail: `Le document type "${docName}" est en cours de téléchargement.`,
    life: 3000
  });
};

const openOfferModal = (offer) => {
  selectedOffer.value = offer;
  isOfferModalOpen.value = true;
};
</script>

<template>
  <div class="mx-auto space-y-6 pb-12">
    <Toast />

    <!-- Main Header Component from Shared Library -->
    <HeaderComponent
      icon="pi pi-user"
      titre="Mon Parcours de Stage"
      description="Consultez les informations de vos périodes, téléchargez les documents types et suivez l'avancement de vos demandes."
      color="violet"
      :showBack="false"
    >
      <template #actions>
        <!-- Period / Academic Year Navigation Bar -->
        <div class="bg-slate-100 dark:bg-slate-800 p-1 rounded-xl flex gap-1 shadow-inner border border-slate-200 dark:border-slate-700">
          <button
            v-for="year in visibleYears"
            :key="year"
            @click="activePeriodTab = year"
            :class="[
              'px-4 py-1.5 rounded-lg text-xs font-extrabold transition-all duration-200 flex items-center gap-1.5',
              activePeriodTab === year
                ? 'bg-white dark:bg-slate-700 text-violet-600 dark:text-violet-300 shadow-sm'
                : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'
            ]"
          >
            <AcademicCapIcon class="w-4 h-4" />
            <span>{{ year }}</span>
            <span v-if="year === currentStudentYear" class="w-2 h-2 rounded-full bg-violet-500 animate-pulse"></span>
          </button>
        </div>
      </template>
    </HeaderComponent>

    <!-- Loading Skeleton State -->
    <div v-if="isLoading" class="py-6 space-y-4">
      <SimpleSkeleton height="100px" />
      <SimpleSkeleton height="250px" />
    </div>

    <template v-else>
      <!-- Top KPI Widgets Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <Kpi
          label="Année Sélectionnée"
          :value="activePeriodTab"
          :icon="AcademicCapIcon"
          color="violet"
          :description="activePeriodTab === currentStudentYear ? 'Année universitaire active' : 'Historique de formation'"
        />
        <Kpi
          label="Périodes Programmées"
          :value="summaryKpis.totalPeriods"
          :icon="FolderIcon"
          color="blue"
          description="Périodes de stage de la formation"
        />
        <Kpi
          label="Conventions Signées"
          :value="summaryKpis.stagesCount"
          :icon="BriefcaseIcon"
          color="emerald"
          :description="summaryKpis.searchCount > 0 ? `${summaryKpis.searchCount} demande(s) en attente` : 'Toutes les périodes sont validées'"
        />
        <Kpi
          label="Rapports Déposés"
          :value="summaryKpis.reportsCount"
          :icon="DocumentTextIcon"
          color="indigo"
          description="Livrables validés pour l'année"
        />
      </div>

      <!-- Active Year Subtitle Info -->
      <div class="flex items-center justify-between bg-slate-50 dark:bg-slate-800/60 p-3 rounded-2xl border border-slate-100 dark:border-slate-700/50">
        <div class="flex items-center gap-2 text-xs font-bold text-slate-700 dark:text-slate-300">
          <i class="pi pi-calendar text-violet-500"></i>
          <span>{{ currentYearData?.yearLabel }}</span>
        </div>
        <span v-if="activePeriodTab === currentStudentYear" class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-violet-100 text-violet-800 dark:bg-violet-950/50 dark:text-violet-300">
          Année Active
        </span>
        <span v-else class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300">
          Historique Archivé
        </span>
      </div>

      <!-- CAS 1 : L'ANNÉE SÉLECTIONNÉE N'A PAS DE STAGE (Composant EmptyState Harmonisé) -->
      <div v-if="currentPeriods.length === 0">
        <EmptyState
          :title="`Pas de stage au programme pour ${activePeriodTab}`"
          description="Aucune période de stage obligatoire ou optionnelle n'est programmée dans la maquette pédagogique pour cette année d'études. Vous n'avez aucune démarche administrative à effectuer."
          icon="pi pi-calendar-times"
          color="gray"
        />
      </div>

      <!-- CAS 2 & 3 : LISTE DES PÉRIODES DE STAGE DE L'ANNÉE -->
      <div v-else class="space-y-6">
        <div
          v-for="period in currentPeriods"
          :key="period.id"
          class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-100 dark:border-slate-700/60 shadow-sm overflow-hidden"
        >
          <!-- En-tête de la Période -->
          <div class="p-6 bg-slate-50/70 dark:bg-slate-800/40 border-b border-slate-100 dark:border-slate-700/40 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
              <div class="flex flex-wrap items-center gap-2 mb-1">
                <span class="text-[10px] font-black uppercase tracking-wider text-violet-600 dark:text-violet-400">
                  Période de Stage Académique
                </span>

                <!-- Statut global du stage ou de la demande -->
                <span v-if="period.hasStage" class="px-2.5 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 flex items-center gap-1">
                  <CheckCircleIcon class="w-3.5 h-3.5 text-emerald-600" />
                  <span>Convention Validée &amp; Signée</span>
                </span>
                <span v-else-if="period.inputAuthorized" class="px-2.5 py-0.5 text-[10px] font-bold rounded-full bg-indigo-100 text-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-300 flex items-center gap-1">
                  <LockOpenIcon class="w-3.5 h-3.5 text-indigo-600" />
                  <span>Demande non complétée (Saisie Ouverte)</span>
                </span>
                <span v-else class="px-2.5 py-0.5 text-[10px] font-bold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300 flex items-center gap-1">
                  <LockClosedIcon class="w-3.5 h-3.5 text-amber-600" />
                  <span>En attente de l'ouverture des saisies</span>
                </span>
              </div>

              <h2 class="text-lg font-black text-slate-900 dark:text-white">{{ period.periodName }}</h2>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 flex flex-wrap items-center gap-x-4 gap-y-1">
                <span>Durée requise : <strong class="text-slate-700 dark:text-slate-200">{{ period.duration }}</strong></span>
                <span>Dates universitaires : <strong class="text-slate-700 dark:text-slate-200">{{ period.dates }}</strong></span>
                <span>Responsable : <strong class="text-slate-700 dark:text-slate-200">{{ period.responsible || period.responsablePrincipal }}</strong></span>
              </p>
            </div>

            <!-- Bouton Consignes & Directives -->
            <button 
              @click="togglePeriodDetails(period.id)"
              class="text-xs font-bold px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-all flex items-center gap-2 shrink-0"
            >
              <i class="pi pi-info-circle text-violet-500"></i>
              <span>{{ isDetailsOpen(period.id, period.hasStage) ? 'Masquer directives' : 'Consignes & Directives' }}</span>
              <i :class="['pi text-[9px] transition-transform duration-200', isDetailsOpen(period.id, period.hasStage) ? 'pi-chevron-up' : 'pi-chevron-down']"></i>
            </button>
          </div>

          <!-- Section Collapsible des Directives IUT (Mise à jour selon StagePeriode.php) -->
          <div v-show="isDetailsOpen(period.id, period.hasStage)" class="p-6 bg-slate-50/40 dark:bg-slate-800/20 border-b border-slate-100 dark:border-slate-700/40 space-y-4">
            <BlocHelp :message="period.description || period.instructions" />

            <!-- Grille des modalités détaillées de la StagePeriode -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-xs pt-3 border-t border-slate-100 dark:border-slate-700/30">
              <!-- 1. Enseignants responsables & Co-responsables -->
              <div class="bg-white dark:bg-slate-800/60 p-3 rounded-xl border border-slate-100 dark:border-slate-700/50">
                <span class="text-slate-400 font-semibold flex items-center gap-1.5 mb-1">
                  <i class="pi pi-user text-violet-500"></i>
                  <span>Responsable(s) de la période :</span>
                </span>
                <span class="font-extrabold text-slate-800 dark:text-slate-200 block">{{ period.responsablePrincipal || period.responsible }}</span>
                <div v-if="period.coResponsables && period.coResponsables.length > 0" class="mt-1 pt-1 border-t border-slate-100 dark:border-slate-700/40">
                  <span class="text-[10px] text-slate-400 font-semibold block">Co-responsable(s) :</span>
                  <span class="text-[11px] font-bold text-slate-600 dark:text-slate-300">{{ period.coResponsables.join(', ') }}</span>
                </div>
              </div>

              <!-- 2. Compétences visées -->
              <div v-if="period.competencesVisees" class="bg-white dark:bg-slate-800/60 p-3 rounded-xl border border-slate-100 dark:border-slate-700/50">
                <span class="text-slate-400 font-semibold flex items-center gap-1.5 mb-1">
                  <i class="pi pi-compass text-emerald-500"></i>
                  <span>Compétences visées :</span>
                </span>
                <p class="font-medium text-slate-700 dark:text-slate-300 leading-relaxed">{{ period.competencesVisees }}</p>
              </div>

              <!-- 3. Évaluation pédagogique -->
              <div v-if="period.modalitesEvaluationPedagogique" class="bg-white dark:bg-slate-800/60 p-3 rounded-xl border border-slate-100 dark:border-slate-700/50">
                <span class="text-slate-400 font-semibold flex items-center gap-1.5 mb-1">
                  <i class="pi pi-graduation-cap text-indigo-500"></i>
                  <span>Évaluation pédagogique (IUT) :</span>
                </span>
                <p class="font-medium text-slate-700 dark:text-slate-300 leading-relaxed">{{ period.modalitesEvaluationPedagogique }}</p>
              </div>

              <!-- 4. Évaluation entreprise -->
              <div v-if="period.modalitesEvaluationEntreprise" class="bg-white dark:bg-slate-800/60 p-3 rounded-xl border border-slate-100 dark:border-slate-700/50">
                <span class="text-slate-400 font-semibold flex items-center gap-1.5 mb-1">
                  <i class="pi pi-building text-amber-500"></i>
                  <span>Évaluation entreprise :</span>
                </span>
                <p class="font-medium text-slate-700 dark:text-slate-300 leading-relaxed">{{ period.modalitesEvaluationEntreprise }}</p>
              </div>

              <!-- 5. Modalités d'encadrement -->
              <div v-if="period.modalitesEncadrement" class="bg-white dark:bg-slate-800/60 p-3 rounded-xl border border-slate-100 dark:border-slate-700/50">
                <span class="text-slate-400 font-semibold flex items-center gap-1.5 mb-1">
                  <i class="pi pi-users text-blue-500"></i>
                  <span>Encadrement &amp; Suivi :</span>
                </span>
                <p class="font-medium text-slate-700 dark:text-slate-300 leading-relaxed">{{ period.modalitesEncadrement }}</p>
              </div>

              <!-- 6. Livrables & Documents à rendre -->
              <div v-if="period.documentsRendre" class="bg-white dark:bg-slate-800/60 p-3 rounded-xl border border-slate-100 dark:border-slate-700/50">
                <span class="text-slate-400 font-semibold flex items-center gap-1.5 mb-1">
                  <i class="pi pi-file-pdf text-rose-500"></i>
                  <span>Livrables à rendre :</span>
                </span>
                <p class="font-medium text-slate-700 dark:text-slate-300 leading-relaxed">{{ period.documentsRendre }}</p>
              </div>
            </div>
          </div>

          <!-- Sous-Onglets d'accès de la période (Vue globale, Documents types, Offres, Rapport) -->
          <div class="px-6 pt-4 bg-white dark:bg-slate-800 border-b border-slate-100 dark:border-slate-700/40 flex flex-wrap gap-2 text-xs">
            <button
              @click="setSubTab(period.id, 'overview')"
              :class="[
                'px-3.5 py-2 rounded-t-xl font-bold transition-all border-b-2 flex items-center gap-2',
                getSubTab(period.id) === 'overview'
                  ? 'border-violet-600 text-violet-600 dark:text-violet-400 bg-violet-50/50 dark:bg-violet-950/20'
                  : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-300'
              ]"
            >
              <BriefcaseIcon class="w-4 h-4" />
              <span>{{ period.hasStage ? 'Mon Stage & Suivi' : 'Informations de la Période' }}</span>
            </button>

            <button
              @click="setSubTab(period.id, 'documents')"
              :class="[
                'px-3.5 py-2 rounded-t-xl font-bold transition-all border-b-2 flex items-center gap-2',
                getSubTab(period.id) === 'documents'
                  ? 'border-violet-600 text-violet-600 dark:text-violet-400 bg-violet-50/50 dark:bg-violet-950/20'
                  : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-300'
              ]"
            >
              <DocumentTextIcon class="w-4 h-4" />
              <span>Documents Types &amp; Consignes</span>
              <span class="bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-[10px] px-1.5 py-0.2 rounded-full font-bold">
                {{ period.documents?.length || 0 }}
              </span>
            </button>

            <button
              @click="setSubTab(period.id, 'offers')"
              :class="[
                'px-3.5 py-2 rounded-t-xl font-bold transition-all border-b-2 flex items-center gap-2',
                getSubTab(period.id) === 'offers'
                  ? 'border-violet-600 text-violet-600 dark:text-violet-400 bg-violet-50/50 dark:bg-violet-950/20'
                  : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-300'
              ]"
            >
              <SparklesIcon class="w-4 h-4 text-amber-500" />
              <span>Offres de Stage</span>
              <span class="bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300 text-[10px] px-1.5 py-0.2 rounded-full font-bold">
                {{ period.offers?.length || 0 }}
              </span>
            </button>

            <button
              v-if="period.hasStage"
              @click="setSubTab(period.id, 'report')"
              :class="[
                'px-3.5 py-2 rounded-t-xl font-bold transition-all border-b-2 flex items-center gap-2',
                getSubTab(period.id) === 'report'
                  ? 'border-violet-600 text-violet-600 dark:text-violet-400 bg-violet-50/50 dark:bg-violet-950/20'
                  : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-300'
              ]"
            >
              <CloudArrowUpIcon class="w-4 h-4" />
              <span>Dépôt du Rapport</span>
              <span v-if="period.stage?.reportUploaded" class="w-2 h-2 rounded-full bg-emerald-500"></span>
            </button>
          </div>

          <!-- CONTENU DU CORPS DE LA PÉRIODE SELON LE SOUS-ONGLET -->
          <div class="p-6">

            <!-- SOUS-ONGLET 1 : VUE GLOBALE (STAGE VALIDE vs DEMANDE NON COMPLÉTÉE) -->
            <div v-if="getSubTab(period.id) === 'overview'">
              
              <!-- CAS 3 : L'étudiant A UN STAGE VALIDE/RATTACHÉ -->
              <div v-if="period.hasStage" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <div class="lg:col-span-2 space-y-6">
                  <Card
                    title="Détails du Stage Enregistré"
                    :subtitle="period.stage.company"
                    icon="pi pi-briefcase"
                    color="violet"
                    :badge="period.stage.status"
                    badgeSeverity="info"
                  >
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                      <div class="bg-slate-50 dark:bg-slate-900/40 p-3 rounded-xl border border-slate-100 dark:border-slate-800">
                        <span class="text-slate-400 block font-semibold">Entreprise d'accueil :</span>
                        <span class="font-extrabold text-slate-800 dark:text-white block mt-1 text-sm">{{ period.stage.company }}</span>
                        <span class="text-[11px] text-slate-500 flex items-center gap-1 mt-1">
                          <MapPinIcon class="w-3.5 h-3.5 text-violet-500" />
                          {{ period.stage.address }}
                        </span>
                      </div>

                      <div class="bg-slate-50 dark:bg-slate-900/40 p-3 rounded-xl border border-slate-100 dark:border-slate-800">
                        <span class="text-slate-400 block font-semibold">Dates effectives de réalisation :</span>
                        <span class="font-extrabold text-slate-800 dark:text-white block mt-1 text-sm">{{ period.stage.dates }}</span>
                        <span class="text-[11px] text-slate-500 flex items-center gap-1 mt-1">
                          <ClockIcon class="w-3.5 h-3.5 text-violet-500" />
                          Durée : {{ period.duration }}
                        </span>
                      </div>

                      <div class="bg-slate-50 dark:bg-slate-900/40 p-3 rounded-xl border border-slate-100 dark:border-slate-800">
                        <span class="text-slate-400 block font-semibold">Tuteur Académique (IUT) :</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200 block mt-1">{{ period.stage.academicTutor }}</span>
                      </div>

                      <div class="bg-slate-50 dark:bg-slate-900/40 p-3 rounded-xl border border-slate-100 dark:border-slate-800">
                        <span class="text-slate-400 block font-semibold">Maître de Stage (Entreprise) :</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200 block mt-1">{{ period.stage.companySupervisor }}</span>
                      </div>

                      <div class="sm:col-span-2 bg-slate-50 dark:bg-slate-900/40 p-3.5 rounded-xl border border-slate-100 dark:border-slate-800">
                        <span class="text-slate-400 block font-semibold">Sujet &amp; Objectifs du stage :</span>
                        <p class="font-medium text-slate-700 dark:text-slate-300 mt-1 leading-relaxed">{{ period.stage.subject }}</p>
                      </div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700/50 flex flex-wrap justify-between items-center gap-3">
                      <span class="text-xs text-slate-400 font-medium">Vous pouvez consulter ou mettre à jour la fiche de votre convention.</span>
                      <button 
                        @click="navigateToRequest(period.id)"
                        class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white font-bold text-xs rounded-xl shadow transition-all flex items-center gap-2"
                      >
                        <PencilSquareIcon class="w-4 h-4" />
                        <span>Compléter / Modifier ma convention</span>
                      </button>
                    </div>
                  </Card>
                </div>

                <!-- Colonne Droite: Suivi du Workflow -->
                <div>
                  <Card
                    title="Suivi du Processus"
                    subtitle="Avancement administratif"
                    icon="pi pi-sliders-h"
                    color="emerald"
                  >
                    <div v-if="period.stage.workflowSteps" class="relative pl-6 border-l-2 border-slate-100 dark:border-slate-700 space-y-4 py-1">
                      <div 
                        v-for="step in period.stage.workflowSteps" 
                        :key="step.id"
                        class="relative"
                      >
                        <span 
                          :class="[
                            'absolute -left-[31px] top-0 w-4 h-4 rounded-full border-2 border-white dark:border-slate-800 flex items-center justify-center transition-all',
                            step.completed 
                              ? 'bg-emerald-500 scale-110 shadow-sm shadow-emerald-400' 
                              : 'bg-slate-200 dark:bg-slate-700'
                          ]"
                        >
                          <i v-if="step.completed" class="pi pi-check text-[8px] text-white"></i>
                        </span>
                        <div>
                          <h4 :class="['text-xs font-bold', step.completed ? 'text-slate-800 dark:text-slate-100' : 'text-slate-400 dark:text-slate-500']">
                            {{ step.label }}
                          </h4>
                          <p class="text-[9px] text-slate-400 dark:text-slate-500 mt-0.5">{{ step.date }}</p>
                        </div>
                      </div>
                    </div>

                    <div v-else class="text-center py-4">
                      <div class="w-10 h-10 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-2">
                        <CheckBadgeIcon class="w-6 h-6" />
                      </div>
                      <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200">Stage Clôturé</h4>
                      <p class="text-[10px] text-slate-400 mt-1">Toutes les étapes administratives ont été validées.</p>
                    </div>
                  </Card>
                </div>

              </div>

              <!-- CAS 2 : LA PÉRIODE EXISTE MAIS L'ÉTUDIANT N'A PAS ENCORE COMPLÉTÉ SA DEMANDE -->
              <div v-else class="space-y-6">
                <Card
                  title="Demande de convention non complétée"
                  subtitle="Vous n'avez pas encore saisi les informations de votre entreprise d'accueil"
                  icon="pi pi-exclamation-circle"
                  color="amber"
                  badge="Demande à compléter"
                  badgeSeverity="warn"
                >
                  <div class="space-y-6">
                    
                    <!-- Blocs d'informations et de téléchargement des documents types -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <!-- Informations de la période -->
                      <div class="bg-slate-50/70 dark:bg-slate-900/40 border border-slate-200/80 dark:border-slate-700/60 rounded-2xl p-4 space-y-2">
                        <h4 class="text-xs font-extrabold text-slate-800 dark:text-white uppercase tracking-wider flex items-center gap-2">
                          <i class="pi pi-info-circle text-violet-500"></i>
                          <span>Informations de la période</span>
                        </h4>
                        <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                          Cette période nécessite un stage de <strong class="text-slate-800 dark:text-white">{{ period.duration }}</strong> du <strong class="text-slate-800 dark:text-white">{{ period.dates }}</strong> sous la responsabilité de <strong class="text-slate-800 dark:text-white">{{ period.responsible || period.responsablePrincipal }}</strong>.
                        </p>
                      </div>

                      <!-- Téléchargement du Document Type & Consignes -->
                      <div class="bg-slate-50/70 dark:bg-slate-900/40 border border-slate-200/80 dark:border-slate-700/60 rounded-2xl p-4 space-y-3">
                        <h4 class="text-xs font-extrabold text-slate-800 dark:text-white uppercase tracking-wider flex items-center gap-2">
                          <i class="pi pi-file-pdf text-red-500"></i>
                          <span>Document type &amp; Fiche de liaison</span>
                        </h4>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">
                          Téléchargez le document type à transmettre à votre entreprise d'accueil pour la préparation de la convention :
                        </p>
                        <div v-if="period.documents && period.documents.length > 0" class="space-y-1.5">
                          <div
                            v-for="doc in period.documents"
                            :key="doc.name"
                            @click="downloadDoc(doc.name)"
                            class="flex items-center justify-between p-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:border-violet-500 transition-all cursor-pointer group"
                          >
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-200 group-hover:text-violet-600 dark:group-hover:text-violet-400 truncate max-w-[240px]">
                              {{ doc.name }}
                            </span>
                            <span class="text-[10px] text-slate-400 font-mono flex items-center gap-1">
                              {{ doc.size || 'PDF' }} <i class="pi pi-download text-[9px]"></i>
                            </span>
                          </div>
                        </div>
                        <p v-else class="text-xs text-slate-400 italic">Aucun document type joint à cette période.</p>
                      </div>
                    </div>

                    <!-- État d'autorisation de la saisie & Bouton d'action -->
                    <div class="pt-2">
                      <!-- Si la saisie est AUTORISÉE -->
                      <div v-if="period.inputAuthorized" class="bg-emerald-50/70 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900/50 rounded-2xl p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="flex items-start gap-3">
                          <LockOpenIcon class="w-6 h-6 text-emerald-600 shrink-0 mt-0.5" />
                          <div>
                            <h4 class="text-xs font-extrabold text-emerald-900 dark:text-emerald-300">Saisie des informations de convention autorisée</h4>
                            <p class="text-[11px] text-emerald-700 dark:text-emerald-400 mt-0.5 leading-relaxed">
                              Votre responsable pédagogique a ouvert la période de saisie. Vous pouvez dès maintenant créer votre demande de convention et renseigner les détails de votre entreprise d'accueil.
                            </p>
                          </div>
                        </div>

                        <button
                          @click="navigateToRequest(period.id)"
                          class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md transition-all flex items-center gap-2 shrink-0 self-end sm:self-auto"
                        >
                          <PencilSquareIcon class="w-4 h-4" />
                          <span>Compléter ma demande de convention</span>
                        </button>
                      </div>

                      <!-- Si la saisie N'EST PAS ENCORE AUTORISÉE -->
                      <div v-else class="bg-amber-50/70 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/50 rounded-2xl p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="flex items-start gap-3">
                          <LockClosedIcon class="w-6 h-6 text-amber-600 shrink-0 mt-0.5" />
                          <div>
                            <h4 class="text-xs font-extrabold text-amber-900 dark:text-amber-300">En attente de l'ouverture des saisies par le responsable</h4>
                            <p class="text-[11px] text-amber-700 dark:text-amber-400 mt-0.5 leading-relaxed">
                              La saisie des conventions n'a pas encore été déverrouillée par le responsable pédagogique pour cette période. Vous pouvez télécharger le document type ci-dessus pour préparer vos informations en attendant.
                            </p>
                          </div>
                        </div>

                        <button
                          disabled
                          class="px-4 py-2.5 bg-slate-200 dark:bg-slate-700 text-slate-400 dark:text-slate-500 font-bold text-xs rounded-xl cursor-not-allowed flex items-center gap-2 shrink-0 self-end sm:self-auto"
                        >
                          <LockClosedIcon class="w-4 h-4" />
                          <span>Saisie verrouillée par l'administration</span>
                        </button>
                      </div>
                    </div>

                    <!-- Actions complémentaires : Consulter les offres de stage -->
                    <div v-if="period.offers && period.offers.length > 0" class="pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-center">
                      <button
                        @click="setSubTab(period.id, 'offers')"
                        class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold shadow transition-all flex items-center gap-2"
                      >
                        <SparklesIcon class="w-4 h-4" />
                        <span>Consulter les offres de stage disponibles ({{ period.offers.length }})</span>
                      </button>
                    </div>

                  </div>
                </Card>
              </div>

            </div>

            <!-- SOUS-ONGLET 2 : DOCUMENTS TYPES & CONSIGNES -->
            <div v-else-if="getSubTab(period.id) === 'documents'">
              <Card
                title="Documents Types &amp; Consignes Pédagogiques"
                subtitle="Téléchargez les fiches de liaison et pré-formulaires requis"
                icon="pi pi-file-pdf"
                color="indigo"
              >
                <div v-if="period.documents && period.documents.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div
                    v-for="doc in period.documents"
                    :key="doc.name"
                    class="bg-slate-50/70 dark:bg-slate-900/40 border border-slate-200/80 dark:border-slate-700/60 rounded-2xl p-4 flex items-center justify-between gap-4 hover:border-violet-500 transition-all group"
                  >
                    <div class="flex items-center gap-3 min-w-0">
                      <div class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-950/40 text-red-600 dark:text-red-400 flex items-center justify-center shrink-0">
                        <i class="pi pi-file-pdf text-xl group-hover:scale-110 transition-transform"></i>
                      </div>
                      <div class="min-w-0">
                        <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">{{ doc.name }}</h4>
                        <p class="text-[10px] text-slate-400 mt-0.5">Format: {{ doc.format || 'PDF' }} {{ doc.size ? `&bull; Taille: ${doc.size}` : '' }}</p>
                      </div>
                    </div>

                    <button
                      @click="downloadDoc(doc.name)"
                      class="px-3 py-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:text-violet-600 dark:hover:text-violet-400 text-xs font-bold transition-all shrink-0 flex items-center gap-1.5 shadow-sm"
                    >
                      <ArrowDownTrayIcon class="w-4 h-4" />
                      <span>Télécharger</span>
                    </button>
                  </div>
                </div>
                <EmptyState
                  v-else
                  compact
                  title="Aucun document joint"
                  description="Aucun document type ou consigne administrative n'est rattaché à cette période."
                  icon="pi pi-file-pdf"
                  color="indigo"
                />
              </Card>
            </div>

            <!-- SOUS-ONGLET 3 : OFFRES DE STAGE -->
            <div v-else-if="getSubTab(period.id) === 'offers'">
              <Card
                title="Offres de Stage Diffusées par l'IUT"
                subtitle="Consultez et postulez aux propositions d'entreprises partenaires"
                icon="pi pi-briefcase"
                color="amber"
              >
                <div v-if="period.offers && period.offers.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                  <div
                    v-for="offer in period.offers"
                    :key="offer.id"
                    class="bg-slate-50/70 dark:bg-slate-900/40 border border-slate-200/80 dark:border-slate-700/60 rounded-2xl p-4 flex flex-col justify-between hover:border-amber-400 dark:hover:border-amber-500 transition-all space-y-3"
                  >
                    <div>
                      <div class="flex justify-between items-start gap-2">
                        <span class="px-2 py-0.5 text-[9px] font-black rounded-md bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300">
                          {{ offer.id }}
                        </span>
                        <span class="text-[10px] text-slate-400 font-bold flex items-center gap-1">
                          <MapPinIcon class="w-3 h-3 text-amber-500" />
                          {{ offer.location }}
                        </span>
                      </div>

                      <h4 class="text-xs font-extrabold text-slate-900 dark:text-white mt-2 leading-tight">{{ offer.title }}</h4>
                      <p class="text-[11px] font-bold text-violet-600 dark:text-violet-400 mt-1">{{ offer.company }}</p>
                      <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-2 line-clamp-2 leading-relaxed">
                        {{ offer.description }}
                      </p>
                    </div>

                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center">
                      <span class="text-[10px] text-slate-400 font-semibold">Gratification: {{ offer.gratification }}</span>
                      <button
                        @click="openOfferModal(offer)"
                        class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white font-bold text-[11px] rounded-lg shadow transition-all flex items-center gap-1"
                      >
                        <EyeIcon class="w-3.5 h-3.5" />
                        <span>Détails</span>
                      </button>
                    </div>
                  </div>
                </div>
                <EmptyState
                  v-else
                  compact
                  title="Aucune offre diffusée"
                  description="Aucune offre de stage n'a été publiée par l'IUT pour cette période pour le moment."
                  icon="pi pi-briefcase"
                  color="amber"
                />
              </Card>
            </div>

            <!-- SOUS-ONGLET 4 : RAPPORT DE STAGE -->
            <div v-else-if="getSubTab(period.id) === 'report' && period.hasStage">
              <Card
                title="Dépôt &amp; Évaluation du Rapport de Stage"
                subtitle="Transmettez votre mémoire final au format PDF"
                icon="pi pi-cloud-upload"
                color="emerald"
              >
                <div class="space-y-4">
                  <!-- Rapport déjà déposé -->
                  <div 
                    v-if="period.stage.reportUploaded" 
                    class="bg-emerald-50/80 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900/50 rounded-2xl p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-sm"
                  >
                    <div class="flex items-center gap-3">
                      <div class="w-12 h-12 rounded-2xl bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                        <i class="pi pi-file-pdf text-2xl"></i>
                      </div>
                      <div>
                        <span class="text-xs font-black text-slate-900 dark:text-white block truncate max-w-[250px] sm:max-w-[350px]">
                          {{ period.stage.reportName }}
                        </span>
                        <span class="text-[10px] text-emerald-700 dark:text-emerald-400 font-bold block mt-0.5">
                          Transmis le {{ period.stage.reportDate || 'Récemment' }} &bull; Statut : Validé pour correction
                        </span>
                      </div>
                    </div>

                    <div class="flex items-center gap-2 self-end sm:self-auto">
                      <span v-if="period.stage.grade" class="text-xs font-black bg-emerald-600 text-white px-3 py-1.5 rounded-xl shadow-sm">
                        Note attribuée : {{ period.stage.grade }}
                      </span>
                      <button 
                        v-if="!period.stage.grade"
                        @click="deleteReport(period.id)"
                        class="text-xs font-bold text-rose-600 hover:text-rose-700 hover:bg-rose-100/50 dark:hover:bg-rose-950/40 p-2 rounded-xl transition-all"
                        title="Retirer le rapport"
                      >
                        <TrashIcon class="w-4 h-4" />
                      </button>
                    </div>
                  </div>

                  <!-- Transfert en cours -->
                  <div 
                    v-else-if="isUploading && activeUploadPeriodId === period.id"
                    class="border border-slate-200 dark:border-slate-700 rounded-2xl p-6 text-center bg-slate-50 dark:bg-slate-800/50"
                  >
                    <div class="w-10 h-10 flex items-center justify-center mx-auto text-violet-600">
                      <i class="pi pi-spin pi-spinner text-2xl"></i>
                    </div>
                    <h4 class="text-xs font-bold text-slate-800 dark:text-slate-100 mt-2">Transfert du rapport en cours...</h4>
                    <div class="w-full bg-slate-200 dark:bg-slate-700 h-1.5 rounded-full mt-3 max-w-xs mx-auto overflow-hidden">
                      <div class="bg-violet-600 h-full transition-all duration-300" :style="{ width: uploadProgress + '%' }"></div>
                    </div>
                  </div>

                  <!-- Zone de dépôt PDF -->
                  <div 
                    v-else
                    @click="triggerFileUpload(period.id)"
                    class="border-2 border-dashed border-slate-300 dark:border-slate-700 hover:border-violet-500 rounded-2xl p-8 text-center cursor-pointer hover:bg-violet-50/20 dark:hover:bg-violet-950/10 transition-all duration-300 group"
                  >
                    <div class="w-12 h-12 bg-violet-100 dark:bg-violet-950/40 text-violet-600 dark:text-violet-400 rounded-2xl flex items-center justify-center mx-auto group-hover:scale-110 transition-transform">
                      <CloudArrowUpIcon class="w-7 h-7" />
                    </div>
                    <h4 class="text-xs font-extrabold text-slate-800 dark:white mt-3">Déposer mon rapport de stage (PDF)</h4>
                    <p class="text-[11px] text-slate-400 mt-1">Glissez votre fichier ici ou cliquez pour choisir sur votre ordinateur (max 15 Mo)</p>
                  </div>
                </div>
              </Card>
            </div>

          </div>

        </div>

      </div>
    </template>

    <!-- Modale des détails d'une offre -->
    <Dialog
      v-model:visible="isOfferModalOpen"
      modal
      header="Détails de l'offre de stage"
      :style="{ width: '90vw', maxWidth: '600px' }"
      class="p-dialog-custom"
    >
      <div v-if="selectedOffer" class="space-y-4 text-xs">
        <div class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/40 rounded-2xl p-4">
          <div class="flex justify-between items-start gap-2">
            <div>
              <span class="text-[9px] font-black uppercase text-amber-700 dark:text-amber-400">Réf. {{ selectedOffer.id }}</span>
              <h3 class="text-base font-black text-slate-900 dark:text-white mt-0.5">{{ selectedOffer.title }}</h3>
              <p class="text-xs font-bold text-violet-600 dark:text-violet-400 mt-0.5">{{ selectedOffer.company }}</p>
            </div>
            <span class="px-2.5 py-1 text-[10px] font-bold rounded-lg bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 shadow-sm">
              {{ selectedOffer.location }}
            </span>
          </div>
        </div>

        <div class="space-y-2">
          <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
            <ClockIcon class="w-4 h-4 text-amber-500" />
            <span>Durée : <strong>{{ selectedOffer.duration }}</strong></span>
          </div>
          <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
            <CurrencyEuroIcon class="w-4 h-4 text-amber-500" />
            <span>Gratification : <strong>{{ selectedOffer.gratification }}</strong></span>
          </div>
        </div>

        <div>
          <h4 class="font-extrabold text-slate-800 dark:text-white mb-1">Description du poste &amp; Missions :</h4>
          <p class="text-slate-600 dark:text-slate-300 leading-relaxed whitespace-pre-line bg-slate-50 dark:bg-slate-800 p-3 rounded-xl border border-slate-100 dark:border-slate-700">
            {{ selectedOffer.description }}
          </p>
        </div>

        <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
          <span class="text-slate-400 font-semibold block">Contact pour postuler :</span>
          <a :href="`mailto:${selectedOffer.contact}`" class="text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline flex items-center gap-1.5 mt-1">
            <i class="pi pi-envelope"></i>
            <span>{{ selectedOffer.contact }}</span>
          </a>
        </div>
      </div>
    </Dialog>

    <!-- Input fichier masqué pour les dépôts -->
    <input 
      type="file" 
      ref="fileInput" 
      @change="handleFileChange" 
      accept=".pdf" 
      class="hidden" 
    />
  </div>
</template>

<style scoped>
.animate-slide-in {
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from { opacity: 0; transform: translateY(-5px); }
  to   { opacity: 1; transform: translateY(0); }
}
</style>
