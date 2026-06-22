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

// Dialog state: review request
const selectedRequest = ref(null);
const showReviewDialog = ref(false);
const tutorSelect = ref('');
const rejectReason = ref('');

// Dialog state: create/edit period
const showCreatePeriodDialog = ref(false);
const showCreateTab = ref('general'); // 'general' | 'interruptions' | 'convention' | 'files'
const isEditing = ref(false);
const editingPeriodId = ref(null);

const newPeriod = ref({
  name: '',
  type: 'Stage',
  level: 'BUT 3',
  semestreProgrammeIri: '',
  dates: '',
  minWeeks: 16,
  anneeUniversitaireIri: '',
  responsablePrincipalIri: '',
  coResponsablesIris: [],
  datesFlexibles: false,
  commentaireLibre: '',
  competencesVisees: '',
  evalEntreprise: '',
  evalPedagogique: '',
  encadrement: '',
  documentsRendre: '',
  consignesFichiers: [],
  interruptions: [],
  soutenances: []
});

const addInterruptionRow = () => {
  newPeriod.value.interruptions.push({ dateDebut: '', dateFin: '', motif: '' });
};

const removeInterruptionRow = (idx) => {
  newPeriod.value.interruptions.splice(idx, 1);
};

const addSoutenanceRow = () => {
  newPeriod.value.soutenances.push({ dateDebut: '', dateFin: '', dateRenduRapport: '', modalites: '' });
};

const removeSoutenanceRow = (idx) => {
  newPeriod.value.soutenances.splice(idx, 1);
};

const triggerConsigneUpload = () => {
  newPeriod.value.consignesFichiers.push({
    name: 'Consignes_Période_' + Date.now().toString().slice(-4) + '.pdf',
    size: '720 Ko'
  });
  toast.add({ severity: 'success', summary: 'Fichier ajouté', detail: 'Le document de consignes a été téléversé.', life: 2000 });
};

const removeConsigneFile = (idx) => {
  newPeriod.value.consignesFichiers.splice(idx, 1);
};

const openCreatePeriodDialog = () => {
  isEditing.value = false;
  editingPeriodId.value = null;
  showCreateTab.value = 'general';
  
  const activeYearObj = dbAnneeUnivs.value.find(y => y.actif);
  const activeYearIri = activeYearObj ? activeYearObj['@id'] : '';
  
  newPeriod.value = {
    name: '',
    type: 'Stage',
    level: 'BUT 3',
    semestreProgrammeIri: '',
    dates: '',
    minWeeks: 16,
    anneeUniversitaireIri: activeYearIri,
    responsablePrincipalIri: '',
    coResponsablesIris: [],
    datesFlexibles: false,
    commentaireLibre: '',
    competencesVisees: '',
    evalEntreprise: '',
    evalPedagogique: '',
    encadrement: '',
    documentsRendre: '',
    consignesFichiers: [],
    interruptions: [],
    soutenances: []
  };
  showCreatePeriodDialog.value = true;
};

const openEditPeriodDialog = (p) => {
  isEditing.value = true;
  editingPeriodId.value = p.id;
  showCreateTab.value = 'general';
  
  newPeriod.value = {
    name: p.name,
    type: p.type,
    level: p.level,
    semestreProgrammeIri: p.semestreProgrammeIri || '',
    dates: p.dates,
    minWeeks: p.minWeeks,
    anneeUniversitaireIri: p.anneeUniversitaireIri,
    responsablePrincipalIri: p.responsablePrincipalIri,
    coResponsablesIris: [...(p.coResponsablesIris || [])],
    datesFlexibles: p.datesFlexibles,
    commentaireLibre: p.commentaireLibre || '',
    competencesVisees: p.competencesVisees || '',
    evalEntreprise: p.evalEntreprise || '',
    evalPedagogique: p.evalPedagogique || '',
    encadrement: p.encadrement || '',
    documentsRendre: p.documentsRendre || '',
    consignesFichiers: p.consignesFichiers ? JSON.parse(JSON.stringify(p.consignesFichiers)) : [],
    interruptions: p.interruptions ? JSON.parse(JSON.stringify(p.interruptions)) : [],
    soutenances: p.soutenances ? JSON.parse(JSON.stringify(p.soutenances)) : []
  };
  showCreatePeriodDialog.value = true;
};

const openReviewRequest = (student) => {
  selectedRequest.value = student;
  tutorSelect.value = student.tutor || (teachers.value[0]?.fullName || 'M. Jean Dupont');
  rejectReason.value = '';
  showReviewDialog.value = true;
};

const approveRequest = () => {
  const studIndex = students.value.findIndex(s => s.id === selectedRequest.value.id);
  if (studIndex !== -1) {
    students.value[studIndex].conventionStatus = 'Validée';
    students.value[studIndex].tutor = tutorSelect.value;
    showReviewDialog.value = false;

    toast.add({
      severity: 'success',
      summary: 'Convention Validée',
      detail: `La demande de ${selectedRequest.value.studentName} a été approuvée. Tuteur : ${tutorSelect.value}.`,
      life: 4000
    });
  }
};

const rejectRequest = () => {
  if (!rejectReason.value.trim()) {
    toast.add({ severity: 'warn', summary: 'Erreur', detail: 'Veuillez saisir un motif de rejet.', life: 3000 });
    return;
  }

  const studIndex = students.value.findIndex(s => s.id === selectedRequest.value.id);
  if (studIndex !== -1) {
    students.value[studIndex].conventionStatus = 'Rejetée';
    showReviewDialog.value = false;

    toast.add({
      severity: 'error',
      summary: 'Convention Rejetée',
      detail: `La demande de ${selectedRequest.value.studentName} a été rejetée. Motif : ${rejectReason.value}`,
      life: 4000
    });
  }
};

const assignTutorDirectly = (student, newTutor) => {
  const studIndex = students.value.findIndex(s => s.id === student.id);
  if (studIndex !== -1) {
    students.value[studIndex].tutor = newTutor;
    toast.add({
      severity: 'success',
      summary: 'Tuteur affecté',
      detail: `Tuteur de ${student.studentName} mis à jour : ${newTutor}.`,
      life: 3000
    });
  }
};

const contactStudent = (studentName) => {
  toast.add({
    severity: 'info',
    summary: 'Rappel envoyé',
    detail: `Un e-mail de rappel de recherche de stage a été envoyé à ${studentName}.`,
    life: 3500
  });
};

const savePeriod = async () => {
  if (!newPeriod.value.name.trim() || !newPeriod.value.dates.trim()) {
    toast.add({ severity: 'warn', summary: 'Champs manquants', detail: 'Veuillez remplir le nom et les dates.', life: 3000 });
    return;
  }

  const payload = formatPeriodPayload(newPeriod.value);

  try {
    if (isEditing.value) {
      const updated = await updateStagePeriodeService(editingPeriodId.value, payload);
      const index = periods.value.findIndex(p => p.id === editingPeriodId.value);
      if (index !== -1 && updated) {
        periods.value[index] = mapPeriodToVue(updated);
        toast.add({
          severity: 'success',
          summary: 'Période mise à jour',
          detail: `La période "${newPeriod.value.name}" a été modifiée avec succès.`,
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

const getInitials = (name) => {
  return name.split(' ').map(n => n[0]).join('');
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



    <!-- TAB 1: STUDENTS OF THE ACTIVE PERIOD -->
    <div v-if="activeTab === 'students'" class="space-y-4 animate-fade-in">
          <!-- Active Period Dropdown Switcher (Always Visible for quick context switcher) -->
    <div
      class="bg-white dark:bg-slate-800 p-4 border border-slate-100 dark:border-slate-700/60 rounded-3xl shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div class="flex items-center gap-3 w-full sm:w-auto">
        <div
          class="w-8 h-8 rounded-lg bg-violet-50 dark:bg-violet-950 flex items-center justify-center text-violet-600 shrink-0">
          <i class="pi pi-filter text-xs"></i>
        </div>
        <div class="flex-1 sm:flex-initial">
          <label class="text-[10px] font-bold text-slate-400 block uppercase tracking-wider">Filtrer par Période
            Académique</label>
          <select v-model="selectedPeriodId"
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
          <span class="text-xl font-black text-slate-900 dark:text-white leading-none block mt-1">{{ kpis.total
            }}</span>
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
          <span class="text-xl font-black text-slate-900 dark:text-white leading-none block mt-1">{{ kpis.rate }}
            %</span>
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
          <span class="text-xl font-black text-slate-900 dark:text-white leading-none block mt-1">{{ kpis.pending
            }}</span>
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
      <div
        class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700/60 rounded-3xl shadow-sm overflow-hidden">
        <div
          class="p-6 border-b border-slate-50 dark:border-slate-700/50 flex flex-wrap justify-between items-center gap-4">
          <div>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Liste des étudiants de la période</h3>
            <p class="text-[11px] text-slate-400 mt-0.5">Suivi de placement, tuteur universitaire et livrables.</p>
          </div>
          <div class="text-xs font-semibold text-slate-400">
            Période : <span class="text-violet-600 dark:text-violet-400 font-bold">{{ activePeriodName }}</span>
          </div>
        </div>

        <DataTable :value="periodStudents" responsiveLayout="scroll" class="text-xs text-slate-700 dark:text-slate-300"
          stripedRows>

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
                  <span class="text-[9px] text-slate-400 block" v-if="slotProps.data.hasStage">Maître : {{
                    slotProps.data.supervisor }}</span>
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
                <option v-for="t in teachers" :key="t" :value="t">{{ t }}</option>
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
    </div>

    <!-- TAB 2: ACADEMIC PERIODS CONFIGURATION -->
    <div v-if="activeTab === 'periods'" class="space-y-6 animate-fade-in">
      <div class="flex justify-between items-center">
        <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Configuration des périodes universitaires
        </h2>
        <button @click="openCreatePeriodDialog"
          class="text-xs font-bold px-4 py-2.5 bg-violet-600 hover:bg-violet-700 text-white rounded-xl shadow-md transition-all flex items-center gap-2">
          <i class="pi pi-plus text-[9px]"></i>
          <span>Créer une période</span>
        </button>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div v-for="p in periods" :key="p.id"
          class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700/60 rounded-3xl p-6 shadow-sm flex flex-col justify-between space-y-4">
          <div>
            <div class="flex justify-between items-start gap-4">
              <div class="flex gap-2">
                <span
                  class="bg-violet-50 dark:bg-violet-950/40 text-violet-700 dark:text-violet-400 px-2.5 py-0.5 rounded font-bold text-[9px] uppercase tracking-wider">
                  {{ p.type }}
                </span>
                <span
                  class="bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 px-2.5 py-0.5 rounded font-bold text-[9px]">
                  {{ p.anneeUniv }}
                </span>
              </div>
              <span
                :class="['px-2 py-0.5 text-[9px] rounded font-bold uppercase', p.datesFlexibles ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-600']">
                {{ p.datesFlexibles ? 'Dates flexibles' : 'Dates strictes' }}
              </span>
            </div>

            <h3 class="text-base font-bold text-slate-900 dark:text-white mt-4">{{ p.name }}</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 text-[11px] text-slate-500 dark:text-slate-400">
              <div class="space-y-2">
                <div class="flex items-center gap-2">
                  <i class="pi pi-calendar text-[10px] text-slate-400"></i>
                  <span>{{ p.dates }} ({{ p.minWeeks }} sem. min)</span>
                </div>
                <div class="flex items-center gap-2">
                  <i class="pi pi-users text-[10px] text-slate-400"></i>
                  <span>Responsable : <strong>{{ p.responsablePrincipal }}</strong></span>
                </div>
                <div class="flex items-start gap-2">
                  <i class="pi pi-user-plus text-[10px] text-slate-400 mt-0.5"></i>
                  <span>Co-responsables : <strong>{{ p.coResponsables.join(', ') || 'Aucun' }}</strong></span>
                </div>
              </div>

              <div
                class="space-y-2 border-t md:border-t-0 md:border-l border-slate-100 dark:border-slate-700 md:pl-4 pt-2 md:pt-0">
                <div class="flex items-center gap-2">
                  <span class="font-bold text-slate-800 dark:text-slate-200">{{ p.interruptions?.length || 0 }}</span>
                  <span>Interruption(s)</span>
                </div>
                <div class="flex items-center gap-2" v-for="s in p.soutenances" :key="s.dateDebut">
                  <i class="pi pi-clock text-[10px] text-slate-400"></i>
                  <span>Soutenances : {{ new Date(s.dateDebut).toLocaleDateString('fr') }} au {{ new
                    Date(s.dateFin).toLocaleDateString('fr') }}</span>
                </div>
                <div class="flex items-center gap-2">
                  <i class="pi pi-file text-[10px] text-slate-400"></i>
                  <span>Fichiers : {{ p.consignesFichiers?.length || 0 }} consignes</span>
                </div>
              </div>
            </div>

            <!-- Display convention parameters brief summary -->
            <div
              class="bg-slate-50 dark:bg-slate-900/40 rounded-xl p-3 border border-slate-100 dark:border-slate-700/40 text-[10px] mt-4 space-y-1 text-slate-500">
              <span class="font-bold text-slate-700 dark:text-slate-300 block mb-1">Paramètres Convention :</span>
              <p class="truncate"><strong class="text-slate-600 dark:text-slate-400">Compétences :</strong> {{
                p.competencesVisees || 'Non définies' }}</p>
              <p class="truncate"><strong class="text-slate-600 dark:text-slate-400">Rendu :</strong> {{
                p.documentsRendre
                || 'Non définies' }}</p>
            </div>
          </div>

          <div class="flex gap-2 w-full">
            <button
              @click="openEditPeriodDialog(p)"
              class="flex-1 py-2 bg-slate-50 dark:bg-slate-700 hover:bg-slate-100 dark:hover:bg-slate-600/60 text-slate-700 dark:text-slate-300 font-bold rounded-xl text-xs transition-all flex items-center justify-center gap-2">
              <i class="pi pi-cog text-[10px]"></i>
              <span>Paramétrer la période</span>
            </button>
            <button
              @click="confirmDeletePeriod(p)"
              class="px-3 py-2 bg-rose-50 dark:bg-rose-950/20 hover:bg-rose-100 dark:hover:bg-rose-900/40 text-rose-600 dark:text-rose-400 font-bold rounded-xl text-xs transition-all flex items-center justify-center">
              <i class="pi pi-trash"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 3: STATS & ANALYTICS -->
    <div v-if="activeTab === 'stats'" class="space-y-6 animate-fade-in">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Placement rates SVG Chart -->
        <div
          class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700/60 rounded-3xl p-6 shadow-sm">
          <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-2">Taux d'insertion des étudiants ({{
            activePeriodName }})</h3>
          <p class="text-xs text-slate-400 mb-6">Proportion d'étudiants ayant validé leur convention à ce jour.</p>

          <div class="flex flex-col sm:flex-row items-center justify-around gap-6">
            <!-- SVG Donut Chart dynamically calculated -->
            <div class="relative w-44 h-44 shrink-0">
              <svg viewBox="0 0 36 36" class="w-full h-full transform -rotate-90">
                <path class="text-slate-100 dark:text-slate-700" stroke-width="3" stroke="currentColor" fill="none"
                  d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                <path class="text-violet-600" stroke-width="3" :stroke-dasharray="kpis.rate + ', 100'"
                  stroke-linecap="round" stroke="currentColor" fill="none"
                  d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
              </svg>
              <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                <span class="text-3xl font-black text-slate-900 dark:text-white leading-none">{{ kpis.rate }} %</span>
                <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider mt-1">Placés</span>
              </div>
            </div>

            <!-- Legend -->
            <div class="space-y-3 text-xs w-full">
              <div class="flex items-center justify-between">
                <span class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded bg-violet-600"></span>
                  <span class="text-slate-500">Conventions signées / Placées</span>
                </span>
                <span class="font-bold text-slate-800 dark:text-slate-100">{{ kpis.placed }} étudiants</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded bg-slate-200 dark:bg-slate-700"></span>
                  <span class="text-slate-500">Recherche active</span>
                </span>
                <span class="font-bold text-slate-800 dark:text-slate-100">{{ kpis.total - kpis.placed }}
                  étudiants</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Average compensation salary stats -->
        <div
          class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700/60 rounded-3xl p-6 shadow-sm flex flex-col justify-between">
          <div>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-2">Statistiques financières de la période
            </h3>
            <p class="text-xs text-slate-400 mb-6">Moyennes de gratification horaire négociées par les étudiants.</p>

            <div class="space-y-4">
              <div>
                <div class="flex justify-between text-xs mb-1 font-bold">
                  <span class="text-slate-600 dark:text-slate-300">Gratification maximale enregistrée</span>
                  <span class="text-violet-600">6.80 €/h</span>
                </div>
                <div class="w-full bg-slate-100 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                  <div class="bg-violet-600 h-full rounded-full" style="width: 100%"></div>
                </div>
              </div>
              <div>
                <div class="flex justify-between text-xs mb-1 font-bold">
                  <span class="text-slate-600 dark:text-slate-300">Gratification moyenne</span>
                  <span class="text-violet-500">4.95 €/h</span>
                </div>
                <div class="w-full bg-slate-100 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                  <div class="bg-violet-500 h-full rounded-full" style="width: 72.8%"></div>
                </div>
              </div>
              <div>
                <div class="flex justify-between text-xs mb-1 font-bold">
                  <span class="text-slate-600 dark:text-slate-300">Minimum légal obligatoire</span>
                  <span class="text-slate-400">4.35 €/h</span>
                </div>
                <div class="w-full bg-slate-100 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                  <div class="bg-slate-400 h-full rounded-full" style="width: 63.9%"></div>
                </div>
              </div>
            </div>
          </div>

          <div
            class="text-[10px] text-slate-400 dark:text-slate-500 pt-4 border-t border-slate-100 dark:border-slate-700/50 mt-4">
            * Indicateurs de gratification calculés sur la base des contrats saisis.
          </div>
        </div>

      </div>
    </div>

    <!-- DIALOG 1: EXAMINER / VALIDATION FLOW & DETAIL SHEET -->
    <Dialog v-model:visible="showReviewDialog" modal header="Instruction de la demande de convention"
      :style="{ width: '85vw', maxWidth: '750px' }" class="text-xs dark:bg-slate-800 dark:text-slate-200">
      <div v-if="selectedRequest" class="space-y-6 py-4">

        <!-- Recal of student inputs -->
        <div
          class="bg-slate-50 dark:bg-slate-900/40 border border-slate-100 dark:border-slate-800 rounded-2xl p-5 space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <span class="text-slate-400 block text-[10px]">Étudiant déclarant :</span>
              <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">{{ selectedRequest.studentName
                }}</span>
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
              <p class="font-semibold text-slate-700 dark:text-slate-300 mt-1 leading-relaxed">{{
                selectedRequest.subject }}
              </p>
            </div>
          </div>
        </div>

        <div v-if="selectedRequest.conventionStatus === 'En attente'"
          class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-slate-100 dark:border-slate-700/50">
          <!-- Validation controls -->
          <div class="space-y-4">
            <h4 class="text-xs font-bold text-slate-800 dark:text-slate-100 uppercase tracking-wider">Option A : Valider
              la
              demande</h4>

            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-bold text-slate-500">Affecter un Tuteur Universitaire</label>
              <select v-model="tutorSelect"
                class="w-full p-2.5 border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-900 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:outline-none">
                <option v-for="t in teachers" :key="t" :value="t">{{ t }}</option>
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
            <h4 class="text-xs font-bold text-slate-800 dark:text-slate-100 uppercase tracking-wider">Option B : Rejeter
              la
              demande</h4>

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

    <!-- DIALOG 2: CREATE/EDIT ACADEMIC PERIOD -->
    <Dialog v-model:visible="showCreatePeriodDialog" modal :header="isEditing ? 'Paramétrer / Éditer la période de stage / alternance' : 'Créer une nouvelle période de stage / alternance'"
      :style="{ width: '90vw', maxWidth: '800px' }" class="text-xs dark:bg-slate-800 dark:text-slate-200">
      <!-- Subtabs within the dialog to organize options -->
      <div class="flex gap-2 border-b border-slate-100 dark:border-slate-700 pb-3 mb-4">
        <button @click="showCreateTab = 'general'"
          :class="['px-3 py-1.5 rounded-lg text-xs font-bold transition-all', showCreateTab === 'general' ? 'bg-violet-100 text-violet-700 dark:bg-violet-950/40 dark:text-violet-400' : 'text-slate-500']">
          Général & Dates
        </button>
        <button @click="showCreateTab = 'interruptions'"
          :class="['px-3 py-1.5 rounded-lg text-xs font-bold transition-all', showCreateTab === 'interruptions' ? 'bg-violet-100 text-violet-700 dark:bg-violet-950/40 dark:text-violet-400' : 'text-slate-500']">
          Interruptions & Soutenances
        </button>
        <button @click="showCreateTab = 'convention'"
          :class="['px-3 py-1.5 rounded-lg text-xs font-bold transition-all', showCreateTab === 'convention' ? 'bg-violet-100 text-violet-700 dark:bg-violet-950/40 dark:text-violet-400' : 'text-slate-500']">
          Modèles & Convention
        </button>
        <button @click="showCreateTab = 'files'"
          :class="['px-3 py-1.5 rounded-lg text-xs font-bold transition-all', showCreateTab === 'files' ? 'bg-violet-100 text-violet-700 dark:bg-violet-950/40 dark:text-violet-400' : 'text-slate-500']">
          Documents Consignes
        </button>
      </div>

      <div class="py-2 space-y-4 max-h-[60vh] overflow-y-auto pr-2">

        <!-- SECTION 1: GENERAL & DATES -->
        <div v-if="showCreateTab === 'general'" class="space-y-4 animate-fade-in">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-bold text-slate-500">Nom de la période</label>
              <input type="text" v-model="newPeriod.name" placeholder="Ex: BUT 3 Informatique - Stage 2026"
                class="p-2.5 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200 focus:outline-none" />
            </div>

            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-bold text-slate-500">Année universitaire</label>
              <select v-model="newPeriod.anneeUniversitaireIri"
                class="p-2.5 border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-900 text-xs font-semibold text-slate-800 dark:text-slate-200">
                <option value="">-- Sélectionner l'année --</option>
                <option v-for="a in dbAnneeUnivs" :key="a['@id']" :value="a['@id']">
                  {{ a.libelle }}
                </option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-bold text-slate-500">Type de contrat</label>
              <select v-model="newPeriod.type"
                class="p-2.5 border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-900 text-xs font-semibold text-slate-800 dark:text-slate-200">
                <option value="Stage">Stage classique</option>
                <option value="Alternance">Alternance / Apprentissage</option>
              </select>
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-bold text-slate-500">Niveau d'études / Semestre</label>
              <select v-model="newPeriod.semestreProgrammeIri"
                class="p-2.5 border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-900 text-xs font-semibold text-slate-800 dark:text-slate-200">
                <option value="">-- Aucun semestre --</option>
                <option v-for="s in dbSemestres" :key="s['@id']" :value="s['@id']">
                  {{ s.libelle }}
                </option>
              </select>
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-bold text-slate-500">Durée minimale obligatoire (Semaines)</label>
              <input type="number" v-model="newPeriod.minWeeks"
                class="p-2.5 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200 focus:outline-none" />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-bold text-slate-500">Dates de la période</label>
              <input type="text" v-model="newPeriod.dates" placeholder="Ex: 02/03/2026 au 26/06/2026"
                class="p-2.5 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200 focus:outline-none" />
            </div>
            <div class="flex items-center gap-2 pt-6">
              <input type="checkbox" id="datesFlex" v-model="newPeriod.datesFlexibles"
                class="w-4 h-4 text-violet-600 rounded focus:ring-violet-500" />
              <label for="datesFlex"
                class="text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer">Autoriser
                des dates flexibles pour l'étudiant</label>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-bold text-slate-500">Responsable Principal</label>
              <select v-model="newPeriod.responsablePrincipalIri"
                class="p-2.5 border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-900 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:outline-none">
                <option value="">-- Sélectionner le créateur --</option>
                <option v-for="t in teachers" :key="t.iri || t" :value="t.iri || t">{{ t.fullName || t }}</option>
              </select>
            </div>

            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-bold text-slate-500">Co-responsables</label>
              <div
                class="flex flex-wrap gap-2 p-2 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 max-h-[120px] overflow-y-auto w-full">
                <div v-for="t in teachers" :key="t.iri || t" class="flex items-center gap-1.5">
                  <input type="checkbox" :id="'coresp_' + (t.iri || t)" :value="t.iri || t" v-model="newPeriod.coResponsablesIris"
                    class="w-3.5 h-3.5 text-violet-600 rounded" />
                  <label :for="'coresp_' + (t.iri || t)"
                    class="text-[10px] text-slate-700 dark:text-slate-300 mr-2 cursor-pointer">{{
                      (t.fullName || t).split(' ').slice(1).join(' ') }}</label>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- SECTION 2: INTERRUPTIONS & SOUTENANCES -->
        <div v-if="showCreateTab === 'interruptions'" class="space-y-6 animate-fade-in">

          <!-- Interruptions 0-n -->
          <div class="space-y-3">
            <div class="flex justify-between items-center">
              <h4 class="text-xs font-bold text-slate-800 dark:text-slate-100 uppercase tracking-wider">Périodes
                d'interruptions ({{ newPeriod.interruptions.length }})</h4>
              <button type="button" @click="addInterruptionRow"
                class="px-2.5 py-1.5 bg-slate-50 hover:bg-slate-100 dark:bg-slate-700 dark:hover:bg-slate-600 text-[10px] font-bold text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-600 rounded-lg flex items-center gap-1 transition-all">
                <i class="pi pi-plus text-[8px]"></i>
                <span>Ajouter interruption</span>
              </button>
            </div>

            <div class="space-y-3" v-if="newPeriod.interruptions.length > 0">
              <div v-for="(item, idx) in newPeriod.interruptions" :key="idx"
                class="flex flex-wrap items-center gap-3 bg-slate-50 dark:bg-slate-900/40 p-3 border border-slate-100 dark:border-slate-800 rounded-xl">
                <div class="flex flex-col gap-1 w-[120px]">
                  <label class="text-[9px] text-slate-400">Date début</label>
                  <input type="date" v-model="item.dateDebut"
                    class="p-1 border border-slate-200 dark:border-slate-700 rounded bg-white dark:bg-slate-900 text-[10px]" />
                </div>
                <div class="flex flex-col gap-1 w-[120px]">
                  <label class="text-[9px] text-slate-400">Date fin</label>
                  <input type="date" v-model="item.dateFin"
                    class="p-1 border border-slate-200 dark:border-slate-700 rounded bg-white dark:bg-slate-900 text-[10px]" />
                </div>
                <div class="flex flex-col gap-1 flex-1 min-w-[200px]">
                  <label class="text-[9px] text-slate-400">Motif</label>
                  <input type="text" v-model="item.motif" placeholder="Ex: Vacances de Noël, etc."
                    class="p-1.5 border border-slate-200 dark:border-slate-700 rounded bg-white dark:bg-slate-900 text-[10px]" />
                </div>
                <button type="button" @click="removeInterruptionRow(idx)"
                  class="text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/20 p-2 rounded-lg mt-4 self-end">
                  <i class="pi pi-trash text-[10px]"></i>
                </button>
              </div>
            </div>
            <div v-else
              class="text-center py-4 bg-slate-50 dark:bg-slate-900/20 border border-dashed border-slate-200 dark:border-slate-700 rounded-xl text-slate-400 text-[10px]">
              Aucune période d'interruption déclarée.
            </div>
          </div>

          <!-- Soutenances -->
          <div class="space-y-3 pt-4 border-t border-slate-100 dark:border-slate-700">
            <div class="flex justify-between items-center">
              <h4 class="text-xs font-bold text-slate-800 dark:text-slate-100 uppercase tracking-wider">Périodes de
                Soutenances ({{ newPeriod.soutenances.length }})</h4>
              <button type="button" @click="addSoutenanceRow"
                class="px-2.5 py-1.5 bg-slate-50 hover:bg-slate-100 dark:bg-slate-700 dark:hover:bg-slate-600 text-[10px] font-bold text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-600 rounded-lg flex items-center gap-1 transition-all">
                <i class="pi pi-plus text-[8px]"></i>
                <span>Ajouter soutenance</span>
              </button>
            </div>

            <div class="space-y-3" v-if="newPeriod.soutenances.length > 0">
              <div v-for="(item, idx) in newPeriod.soutenances" :key="idx"
                class="grid grid-cols-1 md:grid-cols-4 gap-3 bg-slate-50 dark:bg-slate-900/40 p-4 border border-slate-100 dark:border-slate-800 rounded-xl">
                <div class="flex flex-col gap-1">
                  <label class="text-[9px] text-slate-400">Début soutenances</label>
                  <input type="date" v-model="item.dateDebut"
                    class="p-1 border border-slate-200 dark:border-slate-700 rounded bg-white dark:bg-slate-900 text-[10px]" />
                </div>
                <div class="flex flex-col gap-1">
                  <label class="text-[9px] text-slate-400">Fin soutenances</label>
                  <input type="date" v-model="item.dateFin"
                    class="p-1 border border-slate-200 dark:border-slate-700 rounded bg-white dark:bg-slate-900 text-[10px]" />
                </div>
                <div class="flex flex-col gap-1">
                  <label class="text-[9px] text-slate-400">Rendu du rapport</label>
                  <input type="date" v-model="item.dateRenduRapport"
                    class="p-1 border border-slate-200 dark:border-slate-700 rounded bg-white dark:bg-slate-900 text-[10px]" />
                </div>
                <div class="flex flex-col gap-1 md:col-span-4">
                  <label class="text-[9px] text-slate-400">Modalités d'évaluation (Texte)</label>
                  <textarea rows="2" v-model="item.modalites" placeholder="Ex: Modalités de présentation, jury..."
                    class="p-2 border border-slate-200 dark:border-slate-700 rounded bg-white dark:bg-slate-900 text-[10px]"></textarea>
                </div>
                <button type="button" @click="removeSoutenanceRow(idx)"
                  class="text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/20 p-2 rounded-lg justify-self-end mt-2 md:col-span-4">
                  <i class="pi pi-trash text-[10px]"></i>
                  <span class="text-[10px] font-bold ml-1">Supprimer cette soutenance</span>
                </button>
              </div>
            </div>
            <div v-else
              class="text-center py-4 bg-slate-50 dark:bg-slate-900/20 border border-dashed border-slate-200 dark:border-slate-700 rounded-xl text-slate-400 text-[10px]">
              Aucune période de soutenance déclarée.
            </div>
          </div>

        </div>

        <!-- SECTION 3: CONVENTION TEXT FIELDS -->
        <div v-if="showCreateTab === 'convention'" class="space-y-4 animate-fade-in">
          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-bold text-slate-500">Commentaire libre</label>
            <textarea rows="2" v-model="newPeriod.commentaireLibre"
              placeholder="Saisissez des commentaires généraux pour alimenter la convention..."
              class="p-2.5 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200"></textarea>
          </div>

          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-bold text-slate-500">Compétences visées</label>
            <textarea rows="2" v-model="newPeriod.competencesVisees"
              placeholder="Compétences techniques et comportementales à acquérir..."
              class="p-2.5 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200"></textarea>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-bold text-slate-500">Modalités d'évaluation entreprise</label>
              <textarea rows="3" v-model="newPeriod.evalEntreprise"
                placeholder="Comment l'entreprise évalue l'étudiant (grille, rapport de tuteur)..."
                class="p-2.5 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200"></textarea>
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-bold text-slate-500">Modalités d'évaluations pédagogiques</label>
              <textarea rows="3" v-model="newPeriod.evalPedagogique"
                placeholder="Mode de calcul de la note finale (rapport, soutenance, coefficients)..."
                class="p-2.5 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200"></textarea>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-bold text-slate-500">Modalités d'encadrement</label>
              <textarea rows="3" v-model="newPeriod.encadrement"
                placeholder="Visites, bilans téléphoniques, livret d'apprentissage..."
                class="p-2.5 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200"></textarea>
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-bold text-slate-500">Documents à rendre</label>
              <textarea rows="3" v-model="newPeriod.documentsRendre"
                placeholder="Rapports, synthèses d'activité, certificats..."
                class="p-2.5 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-200"></textarea>
            </div>
          </div>
        </div>

        <!-- SECTION 4: INSTRUCTIONS FILES UPLOADS -->
        <div v-if="showCreateTab === 'files'" class="space-y-4 animate-fade-in">
          <div
            class="border-2 border-dashed border-slate-200 dark:border-slate-700 hover:border-violet-400 dark:hover:border-violet-500 rounded-2xl p-6 text-center cursor-pointer hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-all duration-300"
            @click="triggerConsigneUpload">
            <div
              class="w-10 h-10 bg-violet-50 dark:bg-violet-500/10 rounded-full flex items-center justify-center text-violet-600 mx-auto">
              <i class="pi pi-upload text-xl"></i>
            </div>
            <h4 class="text-xs font-bold text-slate-800 dark:text-slate-100 mt-2">Cliquez pour ajouter une consigne</h4>
            <p class="text-[10px] text-slate-400 mt-0.5">Documents pdf d'aide, guides, chartes de stage...</p>
          </div>

          <div class="space-y-2 mt-4" v-if="newPeriod.consignesFichiers.length > 0">
            <h5 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Fichiers téléversés ({{
              newPeriod.consignesFichiers.length }})</h5>

            <div v-for="(f, idx) in newPeriod.consignesFichiers" :key="idx"
              class="flex items-center justify-between p-3 border border-slate-100 dark:border-slate-800 rounded-xl bg-slate-50/50 dark:bg-slate-900/20">
              <span class="font-bold text-slate-700 dark:text-slate-300 flex items-center gap-2">
                <i class="pi pi-file-pdf text-rose-500"></i>
                {{ f.name }} <span class="text-[9px] text-slate-400">({{ f.size }})</span>
              </span>
              <button type="button" @click="removeConsigneFile(idx)"
                class="text-xs text-rose-600 hover:text-rose-700 font-bold">
                Supprimer
              </button>
            </div>
          </div>
        </div>

      </div>

      <!-- Footer Buttons -->
      <div class="flex items-center justify-between border-t border-slate-100 dark:border-slate-700 pt-4 mt-6">
        <div></div> <!-- Spacer -->
        <button @click="savePeriod"
          class="py-3 px-6 bg-violet-600 hover:bg-violet-700 text-white font-bold rounded-xl text-xs transition-all flex items-center justify-center gap-2">
          <i :class="isEditing ? 'pi pi-check' : 'pi pi-plus'"></i>
          <span>{{ isEditing ? 'Enregistrer les modifications' : 'Créer la période' }}</span>
        </button>
      </div>
    </Dialog>

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
