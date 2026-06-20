<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';

const router = useRouter();
const toast = useToast();

const selectedPeriod = ref('BUT3');
const fileInput = ref(null);
const isUploading = ref(false);
const uploadProgress = ref(0);

// Sample periods and internship data
const periodsData = ref({
  BUT1: {
    periodName: 'BUT 1 - Stage d\'initiation (4 semaines)',
    hasConvention: true,
    status: 'Terminé',
    statusClass: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300',
    company: 'WebDesign Agency',
    subject: 'Intégration d\'interfaces web et initiation au référencement SEO.',
    dates: '15/05/2024 au 15/06/2024',
    academicTutor: 'M. Jean Dupont',
    companySupervisor: 'Mme. Sarah Lemoine',
    reportUploaded: true,
    reportName: 'Rapport_Stage_Init_BUT1_Martin.pdf',
    grade: '16.5 / 20'
  },
  BUT2: {
    periodName: 'BUT 2 - Stage technique (8 semaines)',
    hasConvention: true,
    status: 'Terminé',
    statusClass: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300',
    company: 'TechSolutions SAS',
    subject: 'Développement d\'une API REST sous Symfony et refonte du panel administrateur.',
    dates: '02/05/2025 au 27/06/2025',
    academicTutor: 'Mme. Marie Lestrade',
    companySupervisor: 'M. Marc Vasseur',
    reportUploaded: true,
    reportName: 'Rapport_BUT2_Symfony_Martin.pdf',
    grade: '17.0 / 20'
  },
  BUT3: {
    periodName: 'BUT 3 - Stage de fin d\'études (16 semaines)',
    hasConvention: true,
    status: 'En cours - Convention signée',
    statusClass: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-300',
    company: 'Avenir Digital',
    subject: 'Migration micro-frontend et mise en place d\'un dashboard analytique sous Vue.js 3.',
    dates: '02/03/2026 au 26/06/2026',
    academicTutor: 'Mme. Sophie Gomez',
    companySupervisor: 'M. Antoine Robert',
    reportUploaded: false,
    reportName: '',
    grade: null,
    // Step indicator workflow
    workflowSteps: [
      { id: 1, label: 'Demande saisie', completed: true, date: '10/01/2026' },
      { id: 2, label: 'Validation responsable', completed: true, date: '15/01/2026' },
      { id: 3, label: 'Convention générée', completed: true, date: '16/01/2026' },
      { id: 4, label: 'Signatures collectées', completed: true, date: '22/01/2026' },
      { id: 5, label: 'Dépôt du rapport', completed: false, date: 'Avant le 30/06/2026' }
    ]
  }
});

const currentData = computed(() => periodsData.value[selectedPeriod.value]);

const triggerFileUpload = () => {
  fileInput.value.click();
};

const handleFileChange = (e) => {
  const file = e.target.files[0];
  if (!file) return;

  isUploading.value = true;
  uploadProgress.value = 10;
  
  // Simulate progressive upload
  const interval = setInterval(() => {
    uploadProgress.value += 30;
    if (uploadProgress.value >= 100) {
      clearInterval(interval);
      isUploading.value = false;
      
      // Update local status
      periodsData.value[selectedPeriod.value].reportUploaded = true;
      periodsData.value[selectedPeriod.value].reportName = file.name;
      
      // Update workflow step if it's BUT3
      if (selectedPeriod.value === 'BUT3') {
        periodsData.value.BUT3.workflowSteps[4].completed = true;
        periodsData.value.BUT3.workflowSteps[4].date = new Date().toLocaleDateString('fr-FR');
      }

      toast.add({
        severity: 'success',
        summary: 'Fichier envoyé',
        detail: 'Votre rapport de stage a été déposé avec succès.',
        life: 4000
      });
    }
  }, 400);
};

const deleteReport = () => {
  periodsData.value[selectedPeriod.value].reportUploaded = false;
  periodsData.value[selectedPeriod.value].reportName = '';
  
  if (selectedPeriod.value === 'BUT3') {
    periodsData.value.BUT3.workflowSteps[4].completed = false;
    periodsData.value.BUT3.workflowSteps[4].date = 'Avant le 30/06/2026';
  }

  toast.add({
    severity: 'info',
    summary: 'Rapport supprimé',
    detail: 'Le dépôt du rapport a été annulé.',
    life: 3000
  });
};

const navigateToRequest = () => {
  router.push({ name: 'ConventionRequest' });
};
</script>

<template>
  <div class="mx-auto space-y-6">
    <Toast />

    <!-- Top Header -->
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
      <div class="bg-slate-100 dark:bg-slate-800 p-1.5 rounded-xl flex gap-1 self-stretch sm:self-auto">
        <button
          v-for="p in ['BUT1', 'BUT2', 'BUT3']"
          :key="p"
          @click="selectedPeriod = p"
          :class="[
            'flex-1 sm:flex-initial px-4 py-2 rounded-lg text-xs font-bold transition-all duration-200',
            selectedPeriod === p
              ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm'
              : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'
          ]"
        >
          {{ p }}
        </button>
      </div>
    </div>

    <!-- Active Information Dashboard -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      
      <!-- Left & Center: Stage Details & Report Upload -->
      <div class="lg:col-span-2 space-y-6">
        
        <!-- Stage Summary Card -->
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 border border-slate-100 dark:border-slate-700/60 shadow-sm relative overflow-hidden">
          <div class="flex flex-wrap justify-between items-center gap-4 border-b border-slate-100 dark:border-slate-700/50 pb-4">
            <div>
              <span class="text-[10px] uppercase font-bold text-violet-600 dark:text-violet-400 tracking-wider">Période active</span>
              <h2 class="text-lg font-bold text-slate-900 dark:text-white mt-0.5">{{ currentData.periodName }}</h2>
            </div>
            <span :class="['px-3 py-1 text-xs font-bold rounded-full', currentData.statusClass]">
              {{ currentData.status }}
            </span>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <div class="space-y-4">
              <div>
                <span class="text-xs text-slate-400 block">Entreprise d'accueil</span>
                <span class="text-sm font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2 mt-1">
                  <i class="pi pi-briefcase text-slate-400"></i>
                  {{ currentData.company }}
                </span>
              </div>
              
              <div>
                <span class="text-xs text-slate-400 block">Dates de stage</span>
                <span class="text-sm font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2 mt-1">
                  <i class="pi pi-calendar text-slate-400"></i>
                  {{ currentData.dates }}
                </span>
              </div>
            </div>

            <div class="space-y-4">
              <div>
                <span class="text-xs text-slate-400 block">Tuteur Universitaire</span>
                <span class="text-sm font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2 mt-1">
                  <i class="pi pi-user-edit text-slate-400"></i>
                  {{ currentData.academicTutor }}
                </span>
              </div>

              <div>
                <span class="text-xs text-slate-400 block">Maître de Stage (Entreprise)</span>
                <span class="text-sm font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2 mt-1">
                  <i class="pi pi-id-card text-slate-400"></i>
                  {{ currentData.companySupervisor }}
                </span>
              </div>
            </div>

            <div class="md:col-span-2 pt-2 border-t border-slate-50 dark:border-slate-700/30">
              <span class="text-xs text-slate-400 block">Sujet du stage / de la mission</span>
              <p class="text-xs text-slate-600 dark:text-slate-300 mt-1 leading-relaxed">
                {{ currentData.subject }}
              </p>
            </div>
          </div>
        </div>

        <!-- Report Upload Section -->
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 border border-slate-100 dark:border-slate-700/60 shadow-sm">
          <h3 class="text-base font-bold text-slate-900 dark:text-white mb-1">Rapport & Documents</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400">
            Déposez vos livrables de stage en format PDF (Taille max : 15 Mo).
          </p>

          <div class="mt-6">
            <!-- Case 1: Report already uploaded -->
            <div 
              v-if="currentData.reportUploaded" 
              class="bg-emerald-50/50 dark:bg-emerald-950/10 border border-emerald-200/60 dark:border-emerald-900/40 rounded-2xl p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
            >
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                  <i class="pi pi-file-pdf text-xl"></i>
                </div>
                <div>
                  <span class="text-xs font-bold text-slate-800 dark:text-slate-200 block truncate max-w-[250px] sm:max-w-[400px]">
                    {{ currentData.reportName }}
                  </span>
                  <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold block mt-0.5">
                    Téléversé avec succès
                  </span>
                </div>
              </div>

              <div class="flex items-center gap-2 self-end sm:self-auto">
                <span v-if="currentData.grade" class="text-xs font-bold bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 px-3 py-1.5 rounded-lg text-slate-700 dark:text-slate-200 mr-2">
                  Note : {{ currentData.grade }}
                </span>
                <button 
                  v-if="!currentData.grade"
                  @click="deleteReport"
                  class="text-xs font-bold text-rose-600 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/20 p-2 rounded-lg transition-all"
                  v-tooltip="'Retirer le document'"
                >
                  <i class="pi pi-trash"></i>
                </button>
              </div>
            </div>

            <!-- Case 2: No report uploaded and not loading -->
            <div 
              v-else-if="!isUploading"
              @click="triggerFileUpload"
              class="border-2 border-dashed border-slate-200 dark:border-slate-700 hover:border-violet-400 dark:hover:border-violet-500 rounded-2xl p-8 text-center cursor-pointer hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-all duration-300"
            >
              <input 
                type="file" 
                ref="fileInput" 
                @change="handleFileChange" 
                accept=".pdf" 
                class="hidden" 
              />
              <div class="w-12 h-12 bg-violet-50 dark:bg-violet-500/10 rounded-full flex items-center justify-center text-violet-600 mx-auto">
                <i class="pi pi-cloud-upload text-2xl"></i>
              </div>
              <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100 mt-4">Glissez votre rapport de stage ici</h4>
              <p class="text-xs text-slate-400 mt-1">ou cliquez pour parcourir vos fichiers</p>
            </div>

            <!-- Case 3: Upload in progress -->
            <div 
              v-else 
              class="border border-slate-100 dark:border-slate-700/60 rounded-2xl p-6 text-center bg-slate-50 dark:bg-slate-800/30"
            >
              <div class="w-12 h-12 flex items-center justify-center mx-auto text-violet-600">
                <i class="pi pi-spin pi-spinner text-3xl"></i>
              </div>
              <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100 mt-2">Dépôt du rapport en cours...</h4>
              <div class="w-full bg-slate-200 dark:bg-slate-700 h-1.5 rounded-full mt-4 max-w-md mx-auto overflow-hidden">
                <div class="bg-violet-600 h-full transition-all duration-300" :style="{ width: uploadProgress + '%' }"></div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- Right Column: Validation Status Workflow -->
      <div class="space-y-6">
        
        <!-- Workflow Timeline -->
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 border border-slate-100 dark:border-slate-700/60 shadow-sm">
          <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="pi pi-sliders-h text-violet-600"></i>
            <span>Suivi des Étapes</span>
          </h3>

          <div v-if="currentData.workflowSteps" class="relative pl-6 border-l-2 border-slate-100 dark:border-slate-800 space-y-6">
            <div 
              v-for="step in currentData.workflowSteps" 
              :key="step.id"
              class="relative"
            >
              <!-- Indicator Node -->
              <span 
                :class="[
                  'absolute -left-[31px] top-0 w-4 h-4 rounded-full border-2 border-white dark:border-slate-800 flex items-center justify-center transition-all',
                  step.completed 
                    ? 'bg-emerald-500 scale-110' 
                    : 'bg-slate-200 dark:bg-slate-700'
                ]"
              >
                <i v-if="step.completed" class="pi pi-check text-[8px] text-white"></i>
              </span>

              <!-- Step Info -->
              <div>
                <h4 :class="['text-xs font-bold', step.completed ? 'text-slate-800 dark:text-slate-100' : 'text-slate-400 dark:text-slate-500']">
                  {{ step.label }}
                </h4>
                <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">{{ step.date }}</p>
              </div>
            </div>
          </div>

          <!-- Finished view for past stages -->
          <div v-else class="text-center py-6">
            <div class="w-12 h-12 bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-3">
              <i class="pi pi-check-circle text-2xl"></i>
            </div>
            <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200">Stage Clôturé</h4>
            <p class="text-xs text-slate-400 mt-1">Toutes les validations et dépôts sont complétés pour ce stage.</p>
          </div>
        </div>

        <!-- Start New Agreement Panel -->
        <div class="bg-gradient-to-br from-indigo-900 to-violet-950 text-white rounded-3xl p-6 shadow-md">
          <h4 class="text-sm font-extrabold tracking-tight">Nouvelle Demande ?</h4>
          <p class="text-xs text-indigo-200/90 mt-2 leading-relaxed">
            Vous débutez un nouveau stage ou contrat d'alternance ? Remplissez dès maintenant votre formulaire de demande de convention de stage.
          </p>
          <button 
            @click="navigateToRequest"
            class="mt-5 w-full py-3 bg-white text-indigo-950 hover:bg-indigo-50 font-black text-xs rounded-xl shadow-md transition-all flex items-center justify-center gap-2"
          >
            <i class="pi pi-file-edit"></i>
            <span>Créer une demande</span>
          </button>
        </div>

      </div>
    </div>
  </div>
</template>
