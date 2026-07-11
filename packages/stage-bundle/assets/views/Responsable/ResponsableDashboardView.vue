<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import {
  getStagePeriodesService,
  createStagePeriodeService,
  updateStagePeriodeService,
  deleteStagePeriodeService,
  getStageEtudiantsService,
  updateStageEtudiantService,
  deleteStageEtudiantService
} from '@/requests/stage_service';
import { getPersonnelsService } from '@requests/user_services/personnelService';
import { getAllAnneesUniversitairesService } from '@requests/structure_services/anneeUnivService';
import { getSemestresService } from '@requests/structure_services/semestreService';
import { updateEtudiantService } from '@requests/user_services/etudiantService';

import StudentsTab from './components/StudentsTab.vue';
import PeriodsTab from './components/PeriodsTab.vue';
import StatsTab from './components/StatsTab.vue';
import PeriodFormDialog from './components/PeriodFormDialog.vue';
import PeriodInfoTab from './components/PeriodInfoTab.vue';
import Select from 'primevue/select';
import Button from 'primevue/button';


import {
  ChartBarIcon,
  UserPlusIcon,
  ArrowDownTrayIcon,
  UserGroupIcon,
  CalendarIcon,
  ChatBubbleLeftRightIcon,
  CheckCircleIcon,
  ClockIcon,
  MagnifyingGlassIcon,
  TableCellsIcon,
  Squares2X2Icon,
  BellIcon,
  XMarkIcon,
  EnvelopeIcon,
  UsersIcon,
  PlusIcon,
  ArrowLeftIcon,
  ArrowRightIcon,
  DocumentIcon,
  Cog6ToothIcon,
} from '@heroicons/vue/24/outline';

import { HeaderComponent, ActionButtonVertical, Kpi, SimpleSkeleton, Card } from '@components';

const toast = useToast();
const route = useRoute();
const router = useRouter();

const activeTab = ref('students'); // 'students' | 'info' | 'stats'
const selectedPeriodId = ref(null); // Default null, will set after load
const viewMode = ref('dashboard'); // 'dashboard' | 'period'

const periods = ref([]);
const students = ref([]);

const mapVueStatusToBackend = (status, inputAuthorized) => {
  switch (status) {
    case 'En attente': return 'ETAT_STAGE_DEPOSE';
    case 'Validée': return 'ETAT_STAGE_VALIDE';
    case 'En cours de signature': return 'ETAT_STAGE_CONVENTION_ENVOYEE';
    case 'Signée': return 'ETAT_STAGE_CONVENTION_RECUE';
    case 'Rejetée': return 'ETAT_STAGE_REFUS';
    case 'Aucune':
    default:
      return inputAuthorized ? 'ETAT_STAGE_AUTORISE' : 'ETAT_STAGE_INCOMPLET';
  }
};

const mapStageEtudiantToVue = (se) => {
  const etu = se.etudiant || {};
  const ent = se.entreprise || {};
  const resp = ent.responsable || {};
  const tut = se.tuteur || {};
  const tutUniv = se.tuteurUniversitaire || {};

  const hasStage = se.etatStage !== 'ETAT_STAGE_AUTORISE' && se.etatStage !== 'ETAT_STAGE_INCOMPLET';
  let conventionStatus = 'Aucune';
  if (se.etatStage === 'ETAT_STAGE_DEPOSE') {
    conventionStatus = 'En attente';
  } else if (se.etatStage === 'ETAT_STAGE_VALIDE') {
    conventionStatus = 'Validée';
  } else if (se.etatStage === 'ETAT_STAGE_CONVENTION_IMPRIMEE' || se.etatStage === 'ETAT_STAGE_CONVENTION_ENVOYEE') {
    conventionStatus = 'En cours de signature';
  } else if (se.etatStage === 'ETAT_STAGE_CONVENTION_RECUE') {
    conventionStatus = 'Signée';
  } else if (se.etatStage === 'ETAT_STAGE_REFUS') {
    conventionStatus = 'Rejetée';
  }

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

  const salaryStr = se.gratificationMontant ? `${se.gratificationMontant.toFixed(2)} €/h` : '-';
  const supervisorName = tut.prenom ? `${tut.civilite || 'M.'} ${tut.prenom} ${tut.nom}` : '-';
  const academicTutorName = tutUniv.prenom ? `${tutUniv.civilite || 'M.'} ${tutUniv.prenom} ${tutUniv.nom}` : 'Non affecté';
  const isSame = !!(tut.nom && resp.nom && tut.nom === resp.nom && tut.prenom === resp.prenom);

  return {
    id: se.id,
    studentId: etu.id || null,
    studentName: `${etu.prenom || ''} ${etu.nom || ''}`.trim() || 'Étudiant inconnu',
    periodId: typeof se.stagePeriode === 'object' && se.stagePeriode !== null
      ? se.stagePeriode.id
      : (typeof se.stagePeriode === 'string'
        ? parseInt(se.stagePeriode.split('/').pop(), 10)
        : null),
    hasStage: hasStage,
    company: ent.raisonSociale || '-',
    siret: ent.siret || '',
    dates: datesStr,
    subject: se.sujetStage || '-',
    activities: se.activites || '',
    supervisor: supervisorName,
    salary: salaryStr,
    conventionStatus: conventionStatus,
    tutor: academicTutorName,
    tutorIri: tutUniv['@id'] || '',
    inputAuthorized: se.etatStage !== 'ETAT_STAGE_INCOMPLET',
    reportUploaded: false,
    reportName: '',

    studentPhone: etu.tel1 || '',
    studentEmail: etu.mailPerso || etu.mailUniv || '',
    insuranceCompany: se.assuranceCompagnie || '',
    insurancePolicyNumber: se.assuranceNumero || '',
    companyPhone: resp.telephone || '',
    companyAddress: ent.adresse || { adresse: '', complement1: '', complement2: '', ville: '', codePostal: '', pays: 'France' },
    signatoryCivilite: resp.civilite || 'M',
    signatoryPrenom: resp.prenom || '',
    signatoryNom: resp.nom || '',
    signatoryTitle: resp.fonction || '',
    signatoryEmail: resp.email || '',
    signatoryPhone: resp.telephone || '',
    tuteurSameAsSignatory: isSame,
    supervisorCivilite: tut.civilite || 'M',
    supervisorPrenom: tut.prenom || '',
    supervisorNom: tut.nom || '',
    supervisorFunction: tut.fonction || '',
    supervisorEmail: tut.email || '',
    supervisorPhone: tut.telephone || '',
    startDate: se.dateDebutStage ? se.dateDebutStage.substring(0, 10) : '',
    endDate: se.dateFinStage ? se.dateFinStage.substring(0, 10) : '',
    weeklyHours: se.dureeHebdomadaire || 35,
    salaryAmount: se.gratificationMontant || 0,
    amenagementStage: se.amenagementStage || ''
  };
};

const fetchAllStudents = async () => {
  try {
    const data = await getStageEtudiantsService();
    students.value = (data || []).map(mapStageEtudiantToVue);
  } catch (error) {
    console.error('Erreur lors du chargement des étudiants:', error);
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de charger la liste des étudiants.', life: 4000 });
  }
};

const syncRouteState = () => {
  const periodIdParam = route.params.id;
  if (periodIdParam) {
    selectedPeriodId.value = parseInt(periodIdParam, 10);
    viewMode.value = 'period';
  } else {
    viewMode.value = 'dashboard';
    selectedPeriodId.value = null;
  }
};

watch(() => route.params.id, () => {
  syncRouteState();
});

watch(selectedPeriodId, (newId) => {
  if (newId && viewMode.value === 'period' && parseInt(route.params.id, 10) !== newId) {
    router.push({ name: 'ResponsablePeriodDashboard', params: { id: newId } });
  }
});

const enterPeriod = (period) => {
  router.push({ name: 'ResponsablePeriodDashboard', params: { id: period.id } });
};

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

    await fetchAllStudents();
    syncRouteState();
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

const globalKpis = computed(() => {
  const list = students.value;
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

const pendingConventions = computed(() => {
  return students.value.filter(s => s.conventionStatus === 'En attente');
});

const activePeriodDatesAndInfo = computed(() => {
  const p = periods.value.find(p => p.id === selectedPeriodId.value);
  if (!p) return '';
  return `${p.dates} — Niveau : ${p.level} — Responsable : ${p.responsablePrincipal}`;
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

const handleUpdateStudent = async ({ id, changes }) => {
  const student = students.value.find(s => s.id === id);
  if (!student) return;

  try {
    // 1. If student personal details are changed, update Etudiant entity first
    if (student.studentId && (changes.studentPhone !== undefined || changes.studentEmail !== undefined)) {
      await updateEtudiantService(student.studentId, {
        tel1: changes.studentPhone,
        mailPerso: changes.studentEmail
      });
    }

    // 2. Prepare payload for StageEtudiant patch
    const patchPayload = {};

    // Map status change
    if (changes.conventionStatus !== undefined) {
      const isAuth = changes.inputAuthorized !== undefined ? changes.inputAuthorized : student.inputAuthorized;
      const backendStatus = mapVueStatusToBackend(changes.conventionStatus, isAuth);
      patchPayload.etatStage = backendStatus;

      // Set timestamp dates based on status transit
      if (backendStatus === 'ETAT_STAGE_VALIDE') {
        patchPayload.dateValidation = new Date().toISOString();
      } else if (backendStatus === 'ETAT_STAGE_CONVENTION_ENVOYEE') {
        patchPayload.dateConventionEnvoyee = new Date().toISOString();
        patchPayload.dateImprime = new Date().toISOString();
      } else if (backendStatus === 'ETAT_STAGE_CONVENTION_RECUE') {
        patchPayload.dateConventionRecu = new Date().toISOString();
      }
    }

    // Map input authorization toggling
    if (changes.inputAuthorized !== undefined && changes.conventionStatus === undefined) {
      patchPayload.etatStage = changes.inputAuthorized ? 'ETAT_STAGE_AUTORISE' : 'ETAT_STAGE_INCOMPLET';
    }

    // Map academic tutor assignment
    if (changes.tutor !== undefined) {
      const teacher = dbPersonnels.value.map(p => {
        const civ = p.civilite || 'M.';
        return {
          iri: p['@id'],
          fullName: `${civ} ${p.prenom} ${p.nom}`
        };
      }).find(t => t.fullName === changes.tutor);

      patchPayload.tuteurUniversitaire = teacher ? teacher.iri : null;
    }

    // Map reset or delete submission (clearing internship details)
    if (changes.hasStage === false) {
      patchPayload.sujetStage = null;
      patchPayload.activites = null;
      patchPayload.amenagementStage = null;
      patchPayload.dateDebutStage = null;
      patchPayload.dateFinStage = null;
      patchPayload.entreprise = null;
      patchPayload.tuteur = null;
      patchPayload.tuteurUniversitaire = null;
      patchPayload.assuranceCompagnie = null;
      patchPayload.assuranceNumero = null;
      patchPayload.etatStage = changes.inputAuthorized === false ? 'ETAT_STAGE_INCOMPLET' : 'ETAT_STAGE_AUTORISE';
    } else {
      // Standard edit form save
      if (changes.subject !== undefined) patchPayload.sujetStage = changes.subject;
      if (changes.activities !== undefined) patchPayload.activites = changes.activities;
      if (changes.amenagementStage !== undefined) patchPayload.amenagementStage = changes.amenagementStage;

      if (changes.startDate !== undefined) {
        patchPayload.dateDebutStage = changes.startDate ? new Date(changes.startDate).toISOString() : null;
      }
      if (changes.endDate !== undefined) {
        patchPayload.dateFinStage = changes.endDate ? new Date(changes.endDate).toISOString() : null;
      }

      if (changes.weeklyHours !== undefined) patchPayload.dureeHebdomadaire = parseFloat(changes.weeklyHours);
      if (changes.salaryAmount !== undefined) {
        patchPayload.gratification = parseFloat(changes.salaryAmount) > 0;
        patchPayload.gratificationMontant = parseFloat(changes.salaryAmount);
      }
      if (changes.insuranceCompany !== undefined) patchPayload.assuranceCompagnie = changes.insuranceCompany;
      if (changes.insurancePolicyNumber !== undefined) patchPayload.assuranceNumero = changes.insurancePolicyNumber;

      // Nested Entreprise write
      if (changes.companyName !== undefined || changes.companySiret !== undefined || changes.companyAddress !== undefined || changes.signatoryNom !== undefined) {
        patchPayload.entreprise = {
          raisonSociale: changes.companyName || '',
          siret: changes.companySiret || '',
          adresse: changes.companyAddress ? {
            adresse: changes.companyAddress.adresse || '',
            complement1: changes.companyAddress.complement1 || '',
            complement2: changes.companyAddress.complement2 || '',
            ville: changes.companyAddress.ville || '',
            codePostal: changes.companyAddress.codePostal || '',
            pays: changes.companyAddress.pays || 'France'
          } : null,
          responsable: changes.signatoryNom ? {
            civilite: changes.signatoryCivilite || 'M',
            prenom: changes.signatoryPrenom || '',
            nom: changes.signatoryNom || '',
            fonction: changes.signatoryTitle || '',
            email: changes.signatoryEmail || '',
            telephone: changes.signatoryPhone || ''
          } : null
        };
      }

      // Nested Contact (tuteur/supervisor) write
      if (changes.supervisorNom !== undefined) {
        patchPayload.tuteur = {
          civilite: changes.supervisorCivilite || 'M',
          prenom: changes.supervisorPrenom || '',
          nom: changes.supervisorNom || '',
          fonction: changes.supervisorFunction || '',
          email: changes.supervisorEmail || '',
          telephone: changes.supervisorPhone || ''
        };
      }
    }

    // 3. Make patch call to API Platform
    if (Object.keys(patchPayload).length > 0) {
      await updateStageEtudiantService(id, patchPayload);
    }

    // 4. Reload all students to get updated status and database structure
    await fetchPeriodStudents(selectedPeriodId.value);

  } catch (error) {
    console.error('Erreur lors de la mise à jour de l\'étudiant:', error);
    toast.add({ severity: 'error', summary: 'Erreur de sauvegarde', detail: 'Impossible de synchroniser les modifications avec le serveur.', life: 4000 });
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

    <!-- 1. Header component for General Dashboard -->
    <HeaderComponent
      v-if="viewMode === 'dashboard'"
      :icon="ChartBarIcon"
      color="indigo"
      titre="Espace Responsable des Stages"
      description="Gérez les conventions de stage, supervisez le parcours des étudiants et paramétrez les périodes."
    >
      <template #actions>
        <button
          class="text-xs font-bold px-4 py-2.5 bg-violet-600 hover:bg-violet-700 text-white rounded-xl shadow-md transition-all flex items-center gap-2 cursor-pointer border-0"
          @click="openCreatePeriodDialog"
        >
          <PlusIcon class="w-3.5 h-3.5" />
          <span>Nouvelle Période</span>
        </button>
      </template>
    </HeaderComponent>

    <!-- 2. Header component for Specific Period View -->
    <HeaderComponent
      v-else
      :icon="CalendarIcon"
      color="violet"
      :titre="activePeriodName"
      :description="activePeriodDatesAndInfo"
    >
      <template #actions>
        <div class="flex items-center gap-3">
          <!-- Quick switch dropdown -->
          <Select
            v-slot:default
            v-model="selectedPeriodId"
            :options="periods"
            optionLabel="name"
            optionValue="id"
            placeholder="Changer de période"
            class="w-64 text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl"
          />
        </div>
      </template>
    </HeaderComponent>

    <!-- Content loading state -->
    <SimpleSkeleton v-if="isLoading" class="!w-full !h-96" />

    <div v-else>
      <!-- TABLEAU DE BORD GÉNÉRAL (viewMode === 'dashboard') -->
      <div v-if="viewMode === 'dashboard'" class="space-y-6 animate-fade-in">
        <!-- KPIs Globaux -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <Kpi
            :value="globalKpis.total"
            label="Total Étudiants"
            :icon="UsersIcon"
            color="indigo"
          />
          <Kpi
            :value="globalKpis.placed"
            label="Étudiants Placés"
            :icon="CheckCircleIcon"
            color="green"
          />
          <Kpi
            :value="globalKpis.rate + '%'"
            label="Taux de Placement"
            :icon="ChartBarIcon"
            color="purple"
          />
          <Kpi
            :value="globalKpis.pending"
            label="Conventions à Valider"
            :icon="ClockIcon"
            color="yellow"
          />
        </div>

        <!-- Section: Dernières actualités sur les conventions -->
        <Card
          title="Demandes de conventions en attente de traitement"
          subtitle="Dossiers récemment déposés nécessitant votre approbation ou relecture."
          :icon="BellIcon"
          color="amber"
        >
          <div class="space-y-3">
            <div v-if="pendingConventions.length > 0" class="divide-y divide-slate-100 dark:divide-slate-700/50">
              <div
                v-for="c in pendingConventions"
                :key="c.id"
                class="flex flex-wrap justify-between items-center py-3 gap-3 first:pt-0 last:pb-0"
              >
                <div class="flex items-start gap-3 min-w-0">
                  <div class="w-8 h-8 rounded-full bg-amber-50 dark:bg-amber-950/30 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                    <DocumentIcon class="w-4 h-4" />
                  </div>
                  <div class="min-w-0">
                    <span class="font-bold text-slate-800 dark:text-slate-200 text-xs block">
                      {{ c.studentName }}
                    </span>
                    <span class="text-[10px] text-slate-400 block mt-0.5">
                      {{ c.company }} — Période : <strong class="text-slate-600 dark:text-slate-300">{{ periods.find(p => p.id === c.periodId)?.name || 'Inconnue' }}</strong>
                    </span>
                  </div>
                </div>
                <button
                  @click="enterPeriod({ id: c.periodId })"
                  class="py-1.5 px-3 bg-violet-600 hover:bg-violet-700 text-white font-bold rounded-xl text-[10px] transition-all flex items-center gap-1 cursor-pointer border-0 shadow-sm"
                >
                  <span>Instruire la demande</span>
                  <ArrowRightIcon class="w-2.5 h-2.5" />
                </button>
              </div>
            </div>
            <div v-else class="text-center py-8 text-xs text-slate-400 italic">
              Aucune demande de convention en attente d'instruction. Tout est à jour !
            </div>
          </div>
        </Card>

        <!-- Liste des Périodes -->
        <PeriodsTab
          :periods="periods"
          @create="openCreatePeriodDialog"
          @edit="openEditPeriodDialog"
          @delete="confirmDeletePeriod"
          @select="enterPeriod"
        />
      </div>

      <!-- VUE DÉTAILLÉE PAR PÉRIODE (viewMode === 'period') -->
      <div v-else class="space-y-6">
        <!-- Tabs for Period Features -->
        <div class="bg-slate-100 dark:bg-slate-800 p-1 rounded-2xl flex gap-1 w-full md:w-auto self-start border border-slate-200/20 dark:border-slate-700/50">
          <button
            @click="activeTab = 'students'"
            :class="['px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 flex items-center justify-center gap-2 border-0 cursor-pointer flex-1 md:flex-initial', activeTab === 'students' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 bg-transparent']"
          >
            <UsersIcon class="w-3.5 h-3.5" />
            <span>Étudiants & Suivis</span>
          </button>
          <button
            @click="activeTab = 'info'"
            :class="['px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 flex items-center justify-center gap-2 border-0 cursor-pointer flex-1 md:flex-initial', activeTab === 'info' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 bg-transparent']"
          >
            <Cog6ToothIcon class="w-3.5 h-3.5" />
            <span>Informations & Config</span>
          </button>
          <button
            @click="activeTab = 'stats'"
            :class="['px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 flex items-center justify-center gap-2 border-0 cursor-pointer flex-1 md:flex-initial', activeTab === 'stats' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 bg-transparent']"
          >
            <ChartBarIcon class="w-3.5 h-3.5" />
            <span>Analyses & Stats</span>
          </button>
        </div>

        <!-- Content rendering based on Active Tab -->
        <div>
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

          <PeriodInfoTab
            v-if="activeTab === 'info'"
            class="animate-fade-in"
            :period="periods.find(p => p.id === selectedPeriodId) || {}"
            @edit="openEditPeriodDialog"
          />

          <StatsTab
            v-if="activeTab === 'stats'"
            class="animate-fade-in"
            :kpis="kpis"
            :active-period-name="activePeriodName"
          />
        </div>
      </div>
    </div>

    <!-- Period Form Dialog -->
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
