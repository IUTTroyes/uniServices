<script setup>
import { ref, computed, onMounted } from 'vue';
import { useToast } from 'primevue/usetoast';
import {
  getStagePeriodesService,
  createStagePeriodeService,
  updateStagePeriodeService,
  deleteStagePeriodeService
} from '@/requests/stage_service';
import { getPersonnelsService } from '@requests/user_services/personnelService';
import { getAllAnneesUniversitairesService } from '@requests/structure_services/anneeUnivService';
import { getSemestresService } from '@requests/structure_services/semestreService';

import StudentsTab from './components/StudentsTab.vue';
import PeriodsTab from './components/PeriodsTab.vue';
import StatsTab from './components/StatsTab.vue';
import PeriodFormDialog from './components/PeriodFormDialog.vue';

const toast = useToast();

const activeTab = ref('students'); // 'students' | 'periods' | 'stats'
const selectedPeriodId = ref(1); // Default to BUT3

const periods = ref([]);

// Mock database: students list across all periods (placed or searching)
const students = ref([
  // Period 1: BUT 3 Informatique
  {
    id: 1,
    studentName: 'Sophie Bernard',
    periodId: 1,
    hasStage: true,
    company: 'Capgemini France',
    dates: '02/03/2026 au 26/06/2026',
    subject: 'Développement Full-Stack Angular & NestJS pour le secteur bancaire.',
    supervisor: 'M. Jean-Marc Lecerf',
    salary: '4.80 €/h',
    conventionStatus: 'En attente',
    tutor: '',
    reportUploaded: false,
    reportName: ''
  },
  {
    id: 2,
    studentName: 'Julien Durand',
    periodId: 1,
    hasStage: true,
    company: 'Sopra Steria',
    dates: '02/03/2026 au 19/06/2026',
    subject: 'Mise en place de pipelines CI/CD sous GitLab CI et administration Docker.',
    supervisor: 'Mme. Claire Mercier',
    salary: '5.20 €/h',
    conventionStatus: 'Validée',
    tutor: 'Mme. Sophie Gomez',
    reportUploaded: true,
    reportName: 'Rapport_Sopra_Durand.pdf'
  },
  {
    id: 3,
    studentName: 'Lucas Martin',
    periodId: 1,
    hasStage: true,
    company: 'Avenir Digital',
    dates: '02/03/2026 au 26/06/2026',
    subject: 'Migration micro-frontend et mise en place d\'un dashboard analytique sous Vue.js 3.',
    supervisor: 'M. Antoine Robert',
    salary: '4.95 €/h',
    conventionStatus: 'Validée',
    tutor: 'Mme. Sophie Gomez',
    reportUploaded: true,
    reportName: 'Rapport_Final_BUT3_Martin.pdf'
  },
  {
    id: 4,
    studentName: 'Emma Bernard',
    periodId: 1,
    hasStage: true,
    company: 'Innovatech Corp',
    dates: '02/03/2026 au 26/06/2026',
    subject: 'Intégration d\'API tiers et développement de modules back-end.',
    supervisor: 'Mme. Julie Simon',
    salary: '4.50 €/h',
    conventionStatus: 'Validée',
    tutor: 'M. Marc Vasseur',
    reportUploaded: false,
    reportName: ''
  },
  {
    id: 5,
    studentName: 'Thomas Petit',
    periodId: 1,
    hasStage: false,
    company: '-',
    dates: '-',
    subject: '-',
    supervisor: '-',
    salary: '-',
    conventionStatus: 'Aucune',
    tutor: 'Non affecté',
    reportUploaded: false,
    reportName: ''
  },
  {
    id: 6,
    studentName: 'Nicolas Vincent',
    periodId: 1,
    hasStage: false,
    company: '-',
    dates: '-',
    subject: '-',
    supervisor: '-',
    salary: '-',
    conventionStatus: 'Aucune',
    tutor: 'Non affecté',
    reportUploaded: false,
    reportName: ''
  },

  // Period 2: BUT 2 Informatique
  {
    id: 7,
    studentName: 'David Lambert',
    periodId: 2,
    hasStage: true,
    company: 'EDF France',
    dates: '04/05/2026 au 26/06/2026',
    subject: 'Création d\'un portail d\'administration de serveurs de fichiers.',
    supervisor: 'M. Daniel Roux',
    salary: '4.35 €/h',
    conventionStatus: 'En attente',
    tutor: '',
    reportUploaded: false,
    reportName: ''
  },
  {
    id: 8,
    studentName: 'Mélanie Leroy',
    periodId: 2,
    hasStage: true,
    company: 'StartUp Web',
    dates: '04/05/2026 au 26/06/2026',
    subject: 'Création de maquettes et développement front-end React.',
    supervisor: 'M. Arnaud Bertrand',
    salary: '4.35 €/h',
    conventionStatus: 'Validée',
    tutor: 'M. Jean Dupont',
    reportUploaded: false,
    reportName: ''
  },
  {
    id: 9,
    studentName: 'Alice Roux',
    periodId: 2,
    hasStage: false,
    company: '-',
    dates: '-',
    subject: '-',
    supervisor: '-',
    salary: '-',
    conventionStatus: 'Aucune',
    tutor: 'Non affecté',
    reportUploaded: false,
    reportName: ''
  },
  {
    id: 10,
    studentName: 'Sarah Morel',
    periodId: 2,
    hasStage: false,
    company: '-',
    dates: '-',
    subject: '-',
    supervisor: '-',
    salary: '-',
    conventionStatus: 'Aucune',
    tutor: 'Non affecté',
    reportUploaded: false,
    reportName: ''
  },

  // Period 3: BUT 3 Alternance
  {
    id: 11,
    studentName: 'Mathieu Fournier',
    periodId: 3,
    hasStage: true,
    company: 'IBM France',
    dates: '01/09/2025 au 31/08/2026',
    subject: 'Ingénierie DevOps, conteneurisation Kubernetes et automatisation Ansible.',
    supervisor: 'M. Frank Martin',
    salary: '1150 €/mois',
    conventionStatus: 'Validée',
    tutor: 'Mme. Marie Lestrade',
    reportUploaded: true,
    reportName: 'Apprentissage_Fournier_IBM.pdf'
  },
  {
    id: 12,
    studentName: 'Julie Girard',
    periodId: 3,
    hasStage: true,
    company: 'Michelin SAS',
    dates: '01/09/2025 au 31/08/2026',
    subject: 'Refonte d\'un intranet industriel et migration AngularJS vers Vue 3.',
    supervisor: 'Mme. Carole Brunet',
    salary: '1250 €/mois',
    conventionStatus: 'Validée',
    tutor: 'Mme. Sophie Gomez',
    reportUploaded: true,
    reportName: 'Apprentissage_Michelin_Girard.pdf'
  },
  {
    id: 13,
    studentName: 'Romain Bonnet',
    periodId: 3,
    hasStage: true,
    company: 'Renault Group',
    dates: '01/09/2025 au 31/08/2026',
    subject: 'Modélisation de données et conception d\'applications de logistique.',
    supervisor: 'M. Bruno Dupont',
    salary: '1100 €/mois',
    conventionStatus: 'Validée',
    tutor: 'M. Eric Martin',
    reportUploaded: false,
    reportName: ''
  }
]);

// Reactive states from backend
const dbPersonnels = ref([]);
const dbAnneeUnivs = ref([]);
const dbSemestres = ref([]);
const isLoading = ref(true);

const teachers = computed(() => {
  if (dbPersonnels.value.length === 0) {
    return [
      { iri: '/api/personnels/1', fullName: 'M. Jean Dupont', shortName: 'Jean Dupont' },
      { iri: '/api/personnels/2', fullName: 'Mme. Marie Lestrade', shortName: 'Marie Lestrade' },
      { iri: '/api/personnels/3', fullName: 'Mme. Sophie Gomez', shortName: 'Sophie Gomez' },
      { iri: '/api/personnels/4', fullName: 'M. Marc Vasseur', shortName: 'Marc Vasseur' },
      { iri: '/api/personnels/5', fullName: 'M. Eric Martin', shortName: 'Eric Martin' }
    ];
  }
  return dbPersonnels.value.map(p => {
    const civ = p.civilite || 'M.';
    return {
      iri: p['@id'],
      fullName: `${civ} ${p.prenom} ${p.nom}`,
      shortName: `${p.prenom} ${p.nom}`
    };
  });
});

// Helper: Dates string parsing & formatting
const parseDates = (datesStr) => {
  const parts = datesStr.split(/\s+au\s+/i);
  let dateDebut = null;
  let dateFin = null;
  
  if (parts.length === 2) {
    const parseFR = (str) => {
      const p = str.trim().split('/');
      if (p.length === 3) {
        return `${p[2]}-${p[1]}-${p[0]}`; // YYYY-MM-DD
      }
      return str;
    };
    dateDebut = parseFR(parts[0]);
    dateFin = parseFR(parts[1]);
  }
  return { dateDebut, dateFin };
};

const mapPeriodToVue = (backendPeriod) => {
  let datesStr = '';
  if (backendPeriod.dateDebut && backendPeriod.dateFin) {
    const debut = new Date(backendPeriod.dateDebut).toLocaleDateString('fr-FR');
    const fin = new Date(backendPeriod.dateFin).toLocaleDateString('fr-FR');
    datesStr = `${debut} au ${fin}`;
  }
  
  const mainResp = backendPeriod.responsablePrincipal;
  const mainRespName = mainResp ? `${mainResp.civilite || 'M.'} ${mainResp.prenom} ${mainResp.nom}` : 'Non renseigné';
  
  const coResps = backendPeriod.coResponsables || [];
  const coRespsNames = coResps.map(c => `${c.civilite || 'M.'} ${c.prenom} ${c.nom}`);
  const coRespsIris = coResps.map(c => c['@id']);
  
  return {
    id: backendPeriod.id,
    name: backendPeriod.libelle,
    type: backendPeriod.nbSemaines > 20 ? 'Alternance' : 'Stage',
    level: backendPeriod.semestreProgramme ? backendPeriod.semestreProgramme.libelle : 'BUT 3',
    semestreProgrammeIri: backendPeriod.semestreProgramme ? backendPeriod.semestreProgramme['@id'] : '',
    dates: datesStr,
    minWeeks: backendPeriod.nbSemaines || 16,
    studentCount: backendPeriod.studentCount || 0,
    active: backendPeriod.actif ?? true,
    anneeUniv: backendPeriod.anneeUniversitaire ? backendPeriod.anneeUniversitaire.libelle : '2025-2026',
    anneeUniversitaireIri: backendPeriod.anneeUniversitaire ? backendPeriod.anneeUniversitaire['@id'] : '',
    responsablePrincipal: mainRespName,
    responsablePrincipalIri: mainResp ? mainResp['@id'] : '',
    coResponsables: coRespsNames,
    coResponsablesIris: coRespsIris,
    datesFlexibles: backendPeriod.datesFlexibles || false,
    commentaireLibre: backendPeriod.commentaireLibre || '',
    competencesVisees: backendPeriod.competencesVisees || '',
    evalEntreprise: backendPeriod.modalitesEvaluationEntreprise || '',
    evalPedagogique: backendPeriod.modalitesEvaluationPedagogique || '',
    encadrement: backendPeriod.modalitesEncadrement || '',
    documentsRendre: backendPeriod.documentsRendre || '',
    consignesFichiers: backendPeriod.consignesFichiers || [],
    interruptions: backendPeriod.periodesInterruption ? backendPeriod.periodesInterruption.map(i => ({
      dateDebut: i.dateDebut ? i.dateDebut.substring(0, 10) : '',
      dateFin: i.dateFin ? i.dateFin.substring(0, 10) : '',
      motif: i.motif
    })) : [],
    soutenances: backendPeriod.periodesSoutenance ? backendPeriod.periodesSoutenance.map(s => ({
      dateDebut: s.dateDebut ? s.dateDebut.substring(0, 10) : '',
      dateFin: s.dateFin ? s.dateFin.substring(0, 10) : '',
      dateRenduRapport: s.dateRenduRapport ? s.dateRenduRapport.substring(0, 10) : '',
      modalites: s.modalites
    })) : []
  };
};

const formatPeriodPayload = (formValue) => {
  const { dateDebut, dateFin } = parseDates(formValue.dates);
  
  const payload = {
    libelle: formValue.name,
    nbSemaines: parseInt(formValue.minWeeks) || 16,
    nbJours: (parseInt(formValue.minWeeks) || 16) * 5,
    dateDebut: dateDebut ? new Date(dateDebut).toISOString() : new Date().toISOString(),
    dateFin: dateFin ? new Date(dateFin).toISOString() : new Date().toISOString(),
    datesFlexibles: formValue.datesFlexibles || false,
    commentaireLibre: formValue.commentaireLibre || '',
    competencesVisees: formValue.competencesVisees || '',
    modalitesEvaluationEntreprise: formValue.evalEntreprise || '',
    modalitesEvaluationPedagogique: formValue.evalPedagogique || '',
    modalitesEncadrement: formValue.encadrement || '',
    documentsRendre: formValue.documentsRendre || '',
    consignesFichiers: formValue.consignesFichiers || [],
    actif: true
  };

  if (formValue.anneeUniversitaireIri) {
    payload.anneeUniversitaire = formValue.anneeUniversitaireIri;
  }
  if (formValue.semestreProgrammeIri) {
    payload.semestreProgramme = formValue.semestreProgrammeIri;
  }
  if (formValue.responsablePrincipalIri) {
    payload.responsablePrincipal = formValue.responsablePrincipalIri;
  }
  if (formValue.coResponsablesIris && formValue.coResponsablesIris.length > 0) {
    payload.coResponsables = formValue.coResponsablesIris;
  }

  if (formValue.interruptions) {
    payload.periodesInterruption = formValue.interruptions.map(i => ({
      dateDebut: i.dateDebut ? new Date(i.dateDebut).toISOString() : null,
      dateFin: i.dateFin ? new Date(i.dateFin).toISOString() : null,
      motif: i.motif
    }));
  }

  if (formValue.soutenances) {
    payload.periodesSoutenance = formValue.soutenances.map(s => ({
      dateDebut: s.dateDebut ? new Date(s.dateDebut).toISOString() : null,
      dateFin: s.dateFin ? new Date(s.dateFin).toISOString() : null,
      dateRenduRapport: s.dateRenduRapport ? new Date(s.dateRenduRapport).toISOString() : null,
      modalites: s.modalites
    }));
  }

  return payload;
};

// Lifecycle Data Loading
const loadAllData = async () => {
  isLoading.value = true;
  try {
    const years = await getAllAnneesUniversitairesService();
    dbAnneeUnivs.value = years || [];
    
    const personnels = await getPersonnelsService({ limit: 200 });
    dbPersonnels.value = personnels || [];
    
    const semestres = await getSemestresService({ limit: 100 });
    dbSemestres.value = semestres || [];
    
    const backendPeriods = await getStagePeriodesService();
    if (backendPeriods && backendPeriods.length > 0) {
      periods.value = backendPeriods.map(mapPeriodToVue);
      if (periods.value.length > 0) {
        selectedPeriodId.value = periods.value[0].id;
      }
    } else {
      periods.value = [];
    }
  } catch (error) {
    console.error('Erreur au chargement des données:', error);
    toast.add({ severity: 'error', summary: 'Erreur de chargement', detail: 'Erreur de communication avec le serveur.', life: 4000 });
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  loadAllData();
});

// Reactive selected period configuration
const activePeriodName = computed(() => {
  const p = periods.value.find(p => p.id === selectedPeriodId.value);
  return p ? p.name : 'Période Sélectionnée';
});

// Reactively filter students based on active selected period
const periodStudents = computed(() => {
  return students.value.filter(s => s.periodId === selectedPeriodId.value);
});

// Dynamic KPI computations for the selected Period
const kpis = computed(() => {
  const list = periodStudents.value;
  const total = list.length;
  const placed = list.filter(s => s.hasStage).length;
  const pending = list.filter(s => s.conventionStatus === 'En attente').length;
  const reports = list.filter(s => s.reportUploaded).length;
  const rate = total > 0 ? Math.round((placed / total) * 100) : 0;

  return {
    total,
    placed,
    pending,
    reports,
    rate
  };
});

// Dialog state: create/edit period
const showCreatePeriodDialog = ref(false);
const isEditing = ref(false);
const editingPeriodId = ref(null);

const editingPeriod = computed(() => {
  if (!isEditing.value || !editingPeriodId.value) return null;
  return periods.value.find(p => p.id === editingPeriodId.value) || null;
});

const openCreatePeriodDialog = () => {
  isEditing.value = false;
  editingPeriodId.value = null;
  showCreatePeriodDialog.value = true;
};

const openEditPeriodDialog = (p) => {
  isEditing.value = true;
  editingPeriodId.value = p.id;
  showCreatePeriodDialog.value = true;
};

const handleUpdateStudent = ({ id, changes }) => {
  const studIndex = students.value.findIndex(s => s.id === id);
  if (studIndex !== -1) {
    students.value[studIndex] = {
      ...students.value[studIndex],
      ...changes
    };
  }
};

const savePeriod = async (formValue) => {
  const payload = formatPeriodPayload(formValue);

  try {
    if (isEditing.value) {
      const updated = await updateStagePeriodeService(editingPeriodId.value, payload);
      const index = periods.value.findIndex(p => p.id === editingPeriodId.value);
      if (index !== -1 && updated) {
        periods.value[index] = mapPeriodToVue(updated);
        toast.add({
          severity: 'success',
          summary: 'Période mise à jour',
          detail: `La période "${formValue.name}" a été modifiée avec succès.`,
          life: 3000
        });
      }
    } else {
      const created = await createStagePeriodeService(payload);
      if (created) {
        periods.value.push(mapPeriodToVue(created));
        if (periods.value.length === 1) {
          selectedPeriodId.value = periods.value[0].id;
        }
        toast.add({
          severity: 'success',
          summary: 'Période Créée',
          detail: 'La nouvelle période universitaire a été ajoutée.',
          life: 3000
        });
      }
    }
    showCreatePeriodDialog.value = false;
  } catch (error) {
    console.error('Error saving period:', error);
    toast.add({ severity: 'error', summary: 'Erreur d\'enregistrement', detail: 'Une erreur s\'est produite lors de la sauvegarde.', life: 4000 });
  }
};

const confirmDeletePeriod = async (p) => {
  if (confirm(`Êtes-vous sûr de vouloir supprimer la période "${p.name}" ?`)) {
    try {
      await deleteStagePeriodeService(p.id);
      periods.value = periods.value.filter(item => item.id !== p.id);
      if (selectedPeriodId.value === p.id && periods.value.length > 0) {
        selectedPeriodId.value = periods.value[0].id;
      }
      toast.add({ severity: 'success', summary: 'Supprimé', detail: 'La période a été supprimée.', life: 3000 });
    } catch (error) {
      console.error('Error deleting period:', error);
      toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de supprimer la période.', life: 4000 });
    }
  }
};
</script>

<template>
  <div class="space-y-6">
    <Toast />

    <!-- Top Navigation Header -->
    <div
      class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-slate-100 dark:border-slate-800 pb-5">
      <div>
        <h1 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2">
          <i class="pi pi-shield text-violet-600"></i>
          <span>Espace Responsable des Stages</span>
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
          Gérez les conventions de stage, supervisez le parcours des étudiants et paramétrez les périodes.
        </p>
      </div>

      <!-- General View Tabs -->
      <div class="bg-slate-100 dark:bg-slate-800 p-1.5 rounded-2xl flex gap-1 w-full md:w-auto">
        <button @click="activeTab = 'students'"
          :class="['px-3 py-2 rounded-xl text-xs font-bold transition-all duration-200 flex items-center justify-center gap-2 flex-1 md:flex-initial', activeTab === 'students' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200']">
          <i class="pi pi-users text-[10px]"></i>
          <span>Étudiants & Suivis</span>
        </button>
        <button @click="activeTab = 'periods'"
          :class="['px-3 py-2 rounded-xl text-xs font-bold transition-all duration-200 flex items-center justify-center gap-2 flex-1 md:flex-initial', activeTab === 'periods' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200']">
          <i class="pi pi-calendar text-[10px]"></i>
          <span>Périodes</span>
        </button>
        <button @click="activeTab = 'stats'"
          :class="['px-3 py-2 rounded-xl text-xs font-bold transition-all duration-200 flex items-center justify-center gap-2 flex-1 md:flex-initial', activeTab === 'stats' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200']">
          <i class="pi pi-chart-bar text-[10px]"></i>
          <span>Analyses</span>
        </button>
      </div>
    </div>

    <!-- Active Tab rendering -->
    <div v-if="isLoading" class="flex items-center justify-center py-12">
      <i class="pi pi-spin pi-spinner text-violet-600 text-2xl"></i>
    </div>
    
    <div v-else>
      <StudentsTab
        v-if="activeTab === 'students'"
        class="animate-fade-in"
        :periods="periods"
        v-model:selectedPeriodId="selectedPeriodId"
        :period-students="periodStudents"
        :kpis="kpis"
        :teachers="teachers"
        :active-period-name="activePeriodName"
        @update-student="handleUpdateStudent"
      />

      <PeriodsTab
        v-if="activeTab === 'periods'"
        class="animate-fade-in"
        :periods="periods"
        @create="openCreatePeriodDialog"
        @edit="openEditPeriodDialog"
        @delete="confirmDeletePeriod"
      />

      <StatsTab
        v-if="activeTab === 'stats'"
        class="animate-fade-in"
        :kpis="kpis"
        :active-period-name="activePeriodName"
      />
    </div>

    <!-- Period Dialog -->
    <PeriodFormDialog
      v-model:visible="showCreatePeriodDialog"
      :period="editingPeriod"
      :db-annee-univs="dbAnneeUnivs"
      :db-semestres="dbSemestres"
      :teachers="teachers"
      @save="savePeriod"
    />
  </div>
</template>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(6px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
