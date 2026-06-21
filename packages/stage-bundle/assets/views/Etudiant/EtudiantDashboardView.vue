<script setup>
import { ref, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';

const router = useRouter();
const toast = useToast();

const currentStudentYear = ref('BUT3'); // 'BUT1' | 'BUT2' | 'BUT3'
const activePeriodTab = ref('BUT3');   // active selected year in UI tabs
const fileInput = ref(null);
const isUploading = ref(false);
const uploadProgress = ref(0);
const activeUploadPeriodId = ref(null);

// Interactive Simulation states
const forceZeroPeriods = ref(false);
const forceNoStages = ref(false);

// Toggles for collapsible instructions (map: periodId -> boolean)
const openedPeriodDetails = ref({});
const togglePeriodDetails = (id) => {
  openedPeriodDetails.value[id] = !openedPeriodDetails.value[id];
};

const isDetailsOpen = (id, hasStage) => {
  return openedPeriodDetails.value[id] !== undefined 
    ? openedPeriodDetails.value[id] 
    : !hasStage; // Open by default if no stage found
};

// Rich Academic History & Periods Database
const studentAcademicHistory = ref({
  BUT1: {
    yearLabel: 'BUT 1 - Année Universitaire 2023-2024',
    periods: [
      {
        id: 101,
        periodName: 'BUT 1 - Stage d\'initiation professionnelle (4 semaines)',
        dates: '15/05/2024 au 15/06/2024',
        duration: '4 semaines',
        responsible: 'M. Jean Dupont',
        instructions: 'Ce premier stage d\'observation vous permet de découvrir le monde de l\'entreprise et d\'observer le rôle d\'un informaticien au quotidien. L\'objectif est d\'appréhender les contraintes professionnelles et de s\'initier au travail en équipe.',
        hasStage: true,
        stage: {
          company: 'WebDesign Agency',
          subject: 'Intégration d\'interfaces web et initiation au référencement SEO.',
          dates: '15/05/2024 au 15/06/2024',
          academicTutor: 'M. Jean Dupont',
          companySupervisor: 'Mme. Sarah Lemoine',
          status: 'Terminé',
          statusClass: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300',
          reportUploaded: true,
          reportName: 'Rapport_Stage_Init_BUT1_Martin.pdf',
          grade: '16.5 / 20'
        }
      }
    ]
  },
  BUT2: {
    yearLabel: 'BUT 2 - Année Universitaire 2024-2025',
    periods: [
      {
        id: 201,
        periodName: 'BUT 2 - Stage technique & applicatif (8 semaines)',
        dates: '02/05/2025 au 27/06/2025',
        duration: '8 semaines',
        responsible: 'Mme. Marie Lestrade',
        instructions: 'Ce stage technique vise à valider des compétences de développement logiciel, d\'intégration ou d\'administration systèmes dans un contexte professionnel. Il donne lieu à un rapport écrit technique approfondi.',
        hasStage: true,
        stage: {
          company: 'TechSolutions SAS',
          subject: 'Développement d\'une API REST sous Symfony et refonte du panel administrateur.',
          dates: '02/05/2025 au 27/06/2025',
          academicTutor: 'Mme. Marie Lestrade',
          companySupervisor: 'M. Marc Vasseur',
          status: 'Terminé',
          statusClass: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300',
          reportUploaded: true,
          reportName: 'Rapport_BUT2_Symfony_Martin.pdf',
          grade: '17.0 / 20'
        }
      }
    ]
  },
  BUT3: {
    yearLabel: 'BUT 3 - Année Universitaire 2025-2026',
    periods: [
      {
        id: 301,
        periodName: 'BUT 3 - Stage de fin d\'études principal (16 semaines)',
        dates: '02/03/2026 au 26/06/2026',
        duration: '16 semaines',
        responsible: 'Mme. Sophie Gomez',
        instructions: 'Stage de fin d\'études (BUT3). Ce stage doit vous permettre de mettre en œuvre l\'ensemble de vos compétences en situation professionnelle complexe et d\'assurer votre transition vers le marché de l\'emploi ou les études supérieures.',
        hasStage: true,
        stage: {
          company: 'Avenir Digital',
          subject: 'Migration micro-frontend et mise en place d\'un dashboard analytique sous Vue.js 3.',
          dates: '02/03/2026 au 26/06/2026',
          academicTutor: 'Mme. Sophie Gomez',
          companySupervisor: 'M. Antoine Robert',
          status: 'En cours - Convention signée',
          statusClass: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-300',
          reportUploaded: false,
          reportName: '',
          grade: null,
          workflowSteps: [
            { id: 1, label: 'Demande saisie', completed: true, date: '10/01/2026' },
            { id: 2, label: 'Validation responsable', completed: true, date: '15/01/2026' },
            { id: 3, label: 'Convention générée', completed: true, date: '16/01/2026' },
            { id: 4, label: 'Signatures collectées', completed: true, date: '22/01/2026' },
            { id: 5, label: 'Dépôt du rapport', completed: false, date: 'Avant le 30/06/2026' }
          ]
        }
      },
      {
        id: 302,
        periodName: 'BUT 3 - Stage optionnel complémentaire (été)',
        dates: '01/07/2026 au 31/08/2026',
        duration: '4 à 8 semaines',
        responsible: 'Mme. Sophie Gomez',
        instructions: 'Période complémentaire optionnelle soumise à l\'approbation du responsable des stages. Permet d\'approfondir une thématique spécifique en entreprise.',
        hasStage: false,
        stage: null
      }
    ]
  }
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
  if (forceZeroPeriods.value) {
    return [];
  }
  const originalPeriods = currentYearData.value ? currentYearData.value.periods : [];
  if (forceNoStages.value) {
    return originalPeriods.map(p => ({
      ...p,
      hasStage: false,
      stage: null
    }));
  }
  return originalPeriods;
});

// Functions
const triggerFileUpload = (periodId) => {
  activeUploadPeriodId.value = periodId;
  fileInput.value.click();
};

const handleFileChange = (e) => {
  const file = e.target.files[0];
  if (!file || activeUploadPeriodId.value === null) return;

  const targetId = activeUploadPeriodId.value;
  isUploading.value = true;
  uploadProgress.value = 10;
  
  // Simulate progressive upload
  const interval = setInterval(() => {
    uploadProgress.value += 30;
    if (uploadProgress.value >= 100) {
      clearInterval(interval);
      isUploading.value = false;
      
      // Update locally
      for (const year of Object.keys(studentAcademicHistory.value)) {
        const period = studentAcademicHistory.value[year].periods.find(p => p.id === targetId);
        if (period && period.stage) {
          period.stage.reportUploaded = true;
          period.stage.reportName = file.name;
          
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
        summary: 'Fichier envoyé',
        detail: 'Votre rapport de stage a été déposé avec succès.',
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
      
      if (period.stage.workflowSteps) {
        const reportStep = period.stage.workflowSteps.find(s => s.id === 5);
        if (reportStep) {
          reportStep.completed = false;
          reportStep.date = 'Avant le 30/06/2026';
        }
      }
      break;
    }
  }

  toast.add({
    severity: 'info',
    summary: 'Rapport supprimé',
    detail: 'Le dépôt du rapport a été annulé.',
    life: 3000
  });
};

const navigateToRequest = (periodId) => {
  router.push({ name: 'ConventionRequest', query: { periodId } });
};
</script>

<template>
  <div class="mx-auto space-y-6">
    <Toast />

    <!-- Simulation Controls Box (Premium Slate Banner for demonstration) -->
    <div class="bg-slate-900 dark:bg-slate-950 text-white rounded-3xl p-5 border border-slate-800 shadow-xl space-y-4">
      <div class="flex items-center justify-between border-b border-slate-800 pb-3">
        <h3 class="text-xs font-black uppercase tracking-wider text-violet-400 flex items-center gap-2">
          <i class="pi pi-cog"></i>
          <span>Panneau de simulation (Profil Étudiant)</span>
        </h3>
        <span class="text-[9px] bg-violet-500/20 text-violet-300 px-2.5 py-0.5 rounded-full font-bold">MODE DEMO</span>
      </div>
      
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 text-xs">
        <div class="flex flex-col gap-1.5">
          <label class="font-bold text-slate-400">Année en cours de l'étudiant :</label>
          <select 
            v-model="currentStudentYear" 
            class="bg-slate-800 border border-slate-700 rounded-xl p-2 font-bold text-white focus:outline-none focus:ring-2 focus:ring-violet-500 transition-all cursor-pointer"
          >
            <option value="BUT1">BUT 1 (Historique uniquement)</option>
            <option value="BUT2">BUT 2 (Intermédiaire)</option>
            <option value="BUT3">BUT 3 (Année terminale active)</option>
          </select>
        </div>

        <div class="flex items-center">
          <label class="flex items-center gap-3 cursor-pointer select-none group">
            <input type="checkbox" v-model="forceZeroPeriods" class="w-4 h-4 rounded text-violet-600 focus:ring-violet-500 bg-slate-800 border-slate-700 cursor-pointer" />
            <span class="font-bold text-slate-300 group-hover:text-white transition-colors">Simuler 0 période de stage</span>
          </label>
        </div>

        <div class="flex items-center">
          <label class="flex items-center gap-3 cursor-pointer select-none group">
            <input type="checkbox" v-model="forceNoStages" class="w-4 h-4 rounded text-violet-600 focus:ring-violet-500 bg-slate-800 border-slate-700 cursor-pointer" />
            <span class="font-bold text-slate-300 group-hover:text-white transition-colors">Simuler recherche de stage (0 stage)</span>
          </label>
        </div>
      </div>
    </div>

    <!-- Main Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-100 dark:border-slate-800 pb-5">
      <div>
        <h1 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2">
          <i class="pi pi-user text-violet-600"></i>
          <span>Mon Parcours de Stage</span>
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
          Visualisez l'état de vos conventions et déposez vos rapports pour chaque année d'études.
        </p>
      </div>

      <!-- Stepper / Period Switcher -->
      <div class="bg-slate-100 dark:bg-slate-800 p-1.5 rounded-xl flex gap-1 self-stretch sm:self-auto shadow-inner">
        <button
          v-for="p in visibleYears"
          :key="p"
          @click="activePeriodTab = p"
          :class="[
            'flex-1 sm:flex-initial px-4 py-2 rounded-lg text-xs font-bold transition-all duration-200',
            activePeriodTab === p
              ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm'
              : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'
          ]"
        >
          {{ p }}
        </button>
      </div>
    </div>

    <!-- Subtitle dynamic details -->
    <div class="flex justify-between items-center text-xs font-semibold text-slate-400 dark:text-slate-500 px-1">
      <span>{{ currentYearData?.yearLabel }}</span>
      <span v-if="activePeriodTab === currentStudentYear" class="bg-violet-100 text-violet-800 dark:bg-violet-950/40 dark:text-violet-300 px-2 py-0.5 rounded text-[10px] font-bold">
        Année en cours
      </span>
      <span v-else class="bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 px-2 py-0.5 rounded text-[10px] font-bold">
        Historique
      </span>
    </div>

    <!-- List of Programmed Periods in the Selected Year -->
    <div v-if="currentPeriods.length === 0" class="bg-white dark:bg-slate-800 rounded-3xl p-10 border border-slate-100 dark:border-slate-700/60 shadow-sm text-center">
      <div class="w-16 h-16 bg-slate-50 dark:bg-slate-700/40 rounded-full flex items-center justify-center text-slate-400 dark:text-slate-500 mx-auto mb-4">
        <i class="pi pi-info-circle text-3xl"></i>
      </div>
      <h3 class="text-base font-bold text-slate-800 dark:text-white">Aucune période de stage programmée</h3>
      <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 max-w-md mx-auto leading-relaxed">
        Il n'y a aucune période de stage définie pour votre promotion sur cette année universitaire. Si cela vous semble anormal, veuillez contacter le secrétariat.
      </p>
    </div>

    <div v-else class="space-y-6">
      <div 
        v-for="period in currentPeriods" 
        :key="period.id" 
        class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-100 dark:border-slate-700/60 shadow-sm overflow-hidden"
      >
        <!-- Period Header Card (Academic details) -->
        <div class="p-6 bg-slate-50/50 dark:bg-slate-800/40 border-b border-slate-100 dark:border-slate-700/40 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
          <div>
            <div class="flex items-center gap-2">
              <span class="text-[9px] uppercase font-extrabold text-violet-600 dark:text-violet-400 tracking-wider">
                Période de stage académique
              </span>
              <span v-if="period.hasStage" class="px-2.5 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                Stage affecté
              </span>
              <span v-else class="px-2.5 py-0.5 text-[10px] font-bold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300">
                Recherche de stage
              </span>
            </div>
            <h2 class="text-base font-extrabold text-slate-900 dark:text-white mt-1">{{ period.periodName }}</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              Durée requise : <span class="font-bold">{{ period.duration }}</span> &bull; Dates universitaires de la période : <span class="font-bold">{{ period.dates }}</span>
            </p>
          </div>
          
          <button 
            @click="togglePeriodDetails(period.id)"
            class="text-xs font-bold px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-all flex items-center gap-2"
          >
            <i class="pi pi-info-circle"></i>
            <span>{{ isDetailsOpen(period.id, period.hasStage) ? 'Masquer les directives' : 'Voir les directives' }}</span>
            <i :class="['pi text-[8px] transition-transform duration-200', isDetailsOpen(period.id, period.hasStage) ? 'pi-chevron-up' : 'pi-chevron-down']"></i>
          </button>
        </div>

        <!-- Collapsible Period Instructions -->
        <div v-show="isDetailsOpen(period.id, period.hasStage)" class="p-6 bg-slate-50/20 dark:bg-slate-800/20 border-b border-slate-100 dark:border-slate-700/40 animate-slide-in">
          <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-3">Informations &amp; Modalités IUT</h4>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-xs text-slate-600 dark:text-slate-300">
            <div>
              <span class="text-slate-400 block font-semibold">Responsable pédagogique :</span>
              <span class="font-bold text-slate-800 dark:text-slate-200 block mt-0.5">{{ period.responsible }}</span>
            </div>
            <div>
              <span class="text-slate-400 block font-semibold">Modalités d'évaluation :</span>
              <span class="font-bold text-slate-800 dark:text-slate-200 block mt-0.5">Rapport de stage écrit &amp; Soutenance devant jury</span>
            </div>
            <div>
              <span class="text-slate-400 block font-semibold">Documents attendus :</span>
              <span class="font-bold text-slate-800 dark:text-slate-200 block mt-0.5">Dépôt PDF via ce dashboard (Max. 15 Mo)</span>
            </div>
            <div class="md:col-span-3 pt-3 border-t border-slate-100 dark:border-slate-700/30">
              <span class="text-slate-400 block font-semibold">Consignes et objectifs :</span>
              <p class="mt-1 leading-relaxed whitespace-pre-line text-slate-500 dark:text-slate-400">{{ period.instructions }}</p>
            </div>
          </div>
        </div>

        <!-- Stage details or Empty state for the current period -->
        <div class="p-6">
          
          <!-- Case A: Student has a stage for this period -->
          <div v-if="period.hasStage" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left & Center: Stage Details & Report Upload -->
            <div class="lg:col-span-2 space-y-6">
              
              <!-- Stage Summary -->
              <div class="bg-slate-50/30 dark:bg-slate-800/10 rounded-2xl p-5 border border-slate-100 dark:border-slate-700/30 relative overflow-hidden">
                <div class="flex justify-between items-center border-b border-slate-100 dark:border-slate-700/40 pb-3">
                  <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                    <i class="pi pi-briefcase text-violet-500"></i>
                    <span>Détails de mon Stage</span>
                  </h3>
                  <span :class="['px-2.5 py-0.5 text-xs font-bold rounded-full', period.stage.statusClass]">
                    {{ period.stage.status }}
                  </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 text-xs">
                  <div>
                    <span class="text-slate-400 block">Entreprise d'accueil</span>
                    <span class="font-bold text-slate-800 dark:text-slate-200 block mt-0.5">{{ period.stage.company }}</span>
                  </div>
                  <div>
                    <span class="text-slate-400 block">Dates de stage effectives</span>
                    <span class="font-bold text-slate-800 dark:text-slate-200 block mt-0.5">{{ period.stage.dates }}</span>
                  </div>
                  <div>
                    <span class="text-slate-400 block">Tuteur Universitaire</span>
                    <span class="font-bold text-slate-800 dark:text-slate-200 block mt-0.5">{{ period.stage.academicTutor }}</span>
                  </div>
                  <div>
                    <span class="text-slate-400 block">Maître de Stage (Entreprise)</span>
                    <span class="font-bold text-slate-800 dark:text-slate-200 block mt-0.5">{{ period.stage.companySupervisor }}</span>
                  </div>
                  <div class="md:col-span-2 pt-3 border-t border-slate-100 dark:border-slate-700/20">
                    <span class="text-slate-400 block">Sujet du stage</span>
                    <p class="font-medium text-slate-700 dark:text-slate-300 mt-1 leading-relaxed">{{ period.stage.subject }}</p>
                  </div>
                </div>
              </div>

              <!-- Report Upload Area -->
              <div class="bg-slate-50/30 dark:bg-slate-800/10 rounded-2xl p-5 border border-slate-100 dark:border-slate-700/30">
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Rapport de stage & Livrables</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Déposez votre document final au format PDF (max. 15Mo).</p>
                
                <div class="mt-4">
                  <!-- Case 1: Already uploaded -->
                  <div 
                    v-if="period.stage.reportUploaded" 
                    class="bg-emerald-50/50 dark:bg-emerald-950/10 border border-emerald-200/60 dark:border-emerald-900/40 rounded-xl p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
                  >
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                        <i class="pi pi-file-pdf text-xl"></i>
                      </div>
                      <div>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200 block truncate max-w-[200px] sm:max-w-[300px]">
                          {{ period.stage.reportName }}
                        </span>
                        <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold block mt-0.5">
                          Déposé avec succès
                        </span>
                      </div>
                    </div>

                    <div class="flex items-center gap-2 self-end sm:self-auto">
                      <span v-if="period.stage.grade" class="text-xs font-bold bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 px-3 py-1.5 rounded-lg text-slate-700 dark:text-slate-200">
                        Note : {{ period.stage.grade }}
                      </span>
                      <button 
                        v-if="!period.stage.grade"
                        @click="deleteReport(period.id)"
                        class="text-xs font-bold text-rose-600 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/20 p-2 rounded-lg transition-all animate-pulse"
                        v-tooltip="'Retirer le document'"
                      >
                        <i class="pi pi-trash"></i>
                      </button>
                    </div>
                  </div>

                  <!-- Case 2: Not uploaded & not loading -->
                  <div 
                    v-else-if="!isUploading || activeUploadPeriodId !== period.id"
                    @click="triggerFileUpload(period.id)"
                    class="border-2 border-dashed border-slate-200 dark:border-slate-700 hover:border-violet-400 dark:hover:border-violet-500 rounded-xl p-6 text-center cursor-pointer hover:bg-slate-50/50 dark:hover:bg-slate-800/10 transition-all duration-300"
                  >
                    <div class="w-10 h-10 bg-violet-50 dark:bg-violet-500/10 rounded-full flex items-center justify-center text-violet-600 mx-auto">
                      <i class="pi pi-cloud-upload text-xl"></i>
                    </div>
                    <h4 class="text-xs font-bold text-slate-800 dark:text-slate-100 mt-3">Déposer mon rapport de stage</h4>
                    <p class="text-[10px] text-slate-400 mt-1">Glissez votre PDF ou cliquez ici pour parcourir vos fichiers</p>
                  </div>

                  <!-- Case 3: Uploading active on this period -->
                  <div 
                    v-else
                    class="border border-slate-100 dark:border-slate-700/60 rounded-xl p-5 text-center bg-slate-50 dark:bg-slate-800/30"
                  >
                    <div class="w-10 h-10 flex items-center justify-center mx-auto text-violet-600">
                      <i class="pi pi-spin pi-spinner text-2xl"></i>
                    </div>
                    <h4 class="text-xs font-bold text-slate-800 dark:text-slate-100 mt-2">Dépôt du rapport en cours...</h4>
                    <div class="w-full bg-slate-200 dark:bg-slate-700 h-1 rounded-full mt-3 max-w-xs mx-auto overflow-hidden">
                      <div class="bg-violet-600 h-full transition-all duration-300" :style="{ width: uploadProgress + '%' }"></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Right Column: Timeline Workflow -->
            <div class="space-y-4">
              <div class="bg-slate-50/30 dark:bg-slate-800/10 rounded-2xl p-5 border border-slate-100 dark:border-slate-700/30">
                <h3 class="text-xs font-bold text-slate-800 dark:text-slate-100 mb-4 flex items-center gap-2">
                  <i class="pi pi-sliders-h text-violet-500"></i>
                  <span>Suivi des validations</span>
                </h3>
                
                <div v-if="period.stage.workflowSteps" class="relative pl-6 border-l-2 border-slate-100 dark:border-slate-700 space-y-4">
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
                  <div class="w-10 h-10 bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-2">
                    <i class="pi pi-check-circle text-xl"></i>
                  </div>
                  <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200">Stage Clôturé</h4>
                  <p class="text-[10px] text-slate-400 mt-1">Toutes les étapes de ce stage sont closes.</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Case B: Empty State / Student has no stage declared yet for this period -->
          <div v-else class="bg-slate-50/50 dark:bg-slate-900/10 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-2xl p-6 text-center max-w-2xl mx-auto my-4 animate-fade-in">
            <div class="w-12 h-12 bg-violet-50 dark:bg-violet-500/10 text-violet-600 rounded-full flex items-center justify-center mx-auto mb-3">
              <i class="pi pi-file-edit text-xl"></i>
            </div>
            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Aucun stage déclaré pour cette période</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
              Pour initier la convention de stage, vous devez d'abord déclarer votre entreprise d'accueil et les modalités de votre mission. Veuillez compléter le formulaire de demande de convention de stage.
            </p>
            <div class="mt-5">
              <button 
                @click="navigateToRequest(period.id)"
                class="px-5 py-2.5 bg-violet-600 hover:bg-violet-700 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center justify-center gap-2 mx-auto"
              >
                <i class="pi pi-file-edit"></i>
                <span>Créer une demande de convention</span>
              </button>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- Hidden file input for reports -->
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

.animate-fade-in {
  animation: fadeIn 0.4s ease-out;
}

@keyframes slideIn {
  from { opacity: 0; transform: translateY(-5px); }
  to   { opacity: 1; transform: translateY(0); }
}

@keyframes fadeIn {
  from { opacity: 0; }
  to   { opacity: 1; }
}

.animate-spin-slow {
  animation: spin 8s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to   { transform: rotate(360deg); }
}
</style>
