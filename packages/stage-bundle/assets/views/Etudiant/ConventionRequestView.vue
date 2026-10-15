<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { getStagePeriodesService } from '../../requests/stage_service';
import { getStageEtudiantsService, createStageEtudiantService, updateStageEtudiantService } from '../../requests/stage_etudiant';
import { useUsersStore, useEtablissementStore } from '@stores';
import api from '@helpers/axios';
import { ValidatedInput, validationRules } from '@components';
import { FormSection, ToggleCard } from '../../components/Form';
import {
  UserIcon,
  BuildingOffice2Icon,
  CalendarDaysIcon,
  ClipboardDocumentCheckIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline';

const router = useRouter();
const route = useRoute();
const toast = useToast();
const userStore = useUsersStore();
const etablissementStore = useEtablissementStore();

const currentStep = ref(1);
const totalSteps = 4;
const stagePeriodes = ref([]);
const existingStageId = ref(null);
const allStudentStages = ref([]);
const loading = ref(false);

// Validation errors tracking
const formErrors = ref({});
const handleValidation = (fieldName, result) => {
  formErrors.value[fieldName] = result.isValid ? null : result.errorMessage;
};

// Expressions régulières de validation en temps réel
const phoneRegex = /^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/;
const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

// Détection en direct des champs bloquants pour l'étape courante
const currentStepBlockers = computed(() => {
  const blockers = [];
  if (currentStep.value === 1) {
    if (!form.value.stagePeriodeIri) {
      blockers.push({ field: 'stagePeriodeIri', label: 'Période de stage', message: 'Sélectionnez une période' });
    }
    if (!form.value.phone?.trim()) {
      blockers.push({ field: 'phone', label: 'Téléphone personnel', message: 'Numéro requis' });
    } else if (!phoneRegex.test(form.value.phone.trim())) {
      blockers.push({ field: 'phone', label: 'Téléphone personnel', message: 'Format invalide (ex: 06 12 34 56 78 ou +33612345678)' });
    }
    if (!form.value.emailPerso?.trim()) {
      blockers.push({ field: 'emailPerso', label: 'E-mail personnel', message: 'Adresse e-mail requise' });
    } else if (!emailRegex.test(form.value.emailPerso.trim())) {
      blockers.push({ field: 'emailPerso', label: 'E-mail personnel', message: 'Format d\'e-mail invalide (ex: etudiant@gmail.com)' });
    }
  } else if (currentStep.value === 2) {
    if (!form.value.companyName?.trim()) {
      blockers.push({ field: 'companyName', label: 'Raison sociale', message: 'Nom de l\'entreprise requis' });
    }
    const cleanSiret = (form.value.companySiret || '').replace(/\s+/g, '');
    if (!cleanSiret) {
      blockers.push({ field: 'companySiret', label: 'Numéro SIRET', message: 'Numéro SIRET requis (14 chiffres)' });
    } else if (!/^[0-9]{14}$/.test(cleanSiret)) {
      blockers.push({ field: 'companySiret', label: 'Numéro SIRET', message: 'Le SIRET doit comporter exactement 14 chiffres' });
    }
    if (!form.value.companyPhone?.trim()) {
      blockers.push({ field: 'companyPhone', label: 'Téléphone standard', message: 'Téléphone standard requis' });
    } else if (!phoneRegex.test(form.value.companyPhone.trim())) {
      blockers.push({ field: 'companyPhone', label: 'Téléphone standard', message: 'Format de téléphone invalide' });
    }
    const addr = form.value.companyAddress;
    let hasAddr = false;
    if (typeof addr === 'string') {
      hasAddr = !!addr.trim();
    } else if (addr && typeof addr === 'object') {
      const hasStreet = !!addr.adresse?.trim();
      const hasCp = !!addr.codePostal?.trim() || /\b\d{5}\b/.test(addr.adresse || '');
      const hasCity = !!addr.ville?.trim();
      hasAddr = hasStreet && (hasCp || hasCity || addr.adresse.length > 3);
    }
    if (!hasAddr) {
      blockers.push({ field: 'companyAddress', label: 'Adresse de l\'entreprise', message: 'Adresse de l\'entreprise requise' });
    }
    if (!form.value.signatoryCivilite) {
      blockers.push({ field: 'signatoryCivilite', label: 'Civilité du signataire', message: 'Civilité requise' });
    }
    if (!form.value.signatoryPrenom?.trim()) {
      blockers.push({ field: 'signatoryPrenom', label: 'Prénom signataire', message: 'Prénom du représentant légal requis' });
    }
    if (!form.value.signatoryNom?.trim()) {
      blockers.push({ field: 'signatoryNom', label: 'Nom signataire', message: 'Nom du représentant légal requis' });
    }
    if (!form.value.signatoryTitle?.trim()) {
      blockers.push({ field: 'signatoryTitle', label: 'Fonction signataire', message: 'Fonction ou titre requis' });
    }
    if (!form.value.signatoryEmail?.trim()) {
      blockers.push({ field: 'signatoryEmail', label: 'E-mail signataire', message: 'E-mail valide requis' });
    } else if (!emailRegex.test(form.value.signatoryEmail.trim())) {
      blockers.push({ field: 'signatoryEmail', label: 'E-mail signataire', message: 'Format d\'e-mail invalide' });
    }
    if (!form.value.signatoryPhone?.trim()) {
      blockers.push({ field: 'signatoryPhone', label: 'Téléphone direct signataire', message: 'Numéro de téléphone requis' });
    } else if (!phoneRegex.test(form.value.signatoryPhone.trim())) {
      blockers.push({ field: 'signatoryPhone', label: 'Téléphone direct signataire', message: 'Format de téléphone invalide' });
    }
    if (!form.value.tuteurSameAsSignatory) {
      if (!form.value.supervisorCivilite) {
        blockers.push({ field: 'supervisorCivilite', label: 'Civilité maître de stage', message: 'Civilité requise' });
      }
      if (!form.value.supervisorPrenom?.trim()) {
        blockers.push({ field: 'supervisorPrenom', label: 'Prénom maître de stage', message: 'Prénom requis' });
      }
      if (!form.value.supervisorNom?.trim()) {
        blockers.push({ field: 'supervisorNom', label: 'Nom maître de stage', message: 'Nom requis' });
      }
      if (!form.value.supervisorFunction?.trim()) {
        blockers.push({ field: 'supervisorFunction', label: 'Fonction maître de stage', message: 'Fonction ou poste requis' });
      }
      if (!form.value.supervisorEmail?.trim()) {
        blockers.push({ field: 'supervisorEmail', label: 'E-mail maître de stage', message: 'E-mail valide requis' });
      } else if (!emailRegex.test(form.value.supervisorEmail.trim())) {
        blockers.push({ field: 'supervisorEmail', label: 'E-mail maître de stage', message: 'Format d\'e-mail invalide' });
      }
      if (!form.value.supervisorPhone?.trim()) {
        blockers.push({ field: 'supervisorPhone', label: 'Téléphone maître de stage', message: 'Numéro de téléphone requis' });
      } else if (!phoneRegex.test(form.value.supervisorPhone.trim())) {
        blockers.push({ field: 'supervisorPhone', label: 'Téléphone maître de stage', message: 'Format de téléphone invalide' });
      }
    }
  } else if (currentStep.value === 3) {
    if (!form.value.startDate) {
      blockers.push({ field: 'startDate', label: 'Date de début', message: 'Date de début de stage requise' });
    }
    if (!form.value.endDate) {
      blockers.push({ field: 'endDate', label: 'Date de fin', message: 'Date de fin de stage requise' });
    }
    if (form.value.startDate && form.value.endDate && new Date(form.value.startDate) > new Date(form.value.endDate)) {
      blockers.push({ field: 'datesOrder', label: 'Dates de stage', message: 'Le début doit précéder la fin' });
    }
    if (form.value.weeklyHours === null || form.value.weeklyHours === undefined || Number(form.value.weeklyHours) <= 0) {
      blockers.push({ field: 'weeklyHours', label: 'Volume horaire', message: 'Nombre d\'heures requis (ex: 35)' });
    }
    if (form.value.salaryAmount === null || form.value.salaryAmount === undefined || form.value.salaryAmount === '' || Number(form.value.salaryAmount) < minGratification.value) {
      blockers.push({
        field: 'salaryAmount',
        label: 'Gratification horaire',
        message: `Montant minimum requis : ${minGratification.value} €/h`
      });
    }
    if (!form.value.subject?.trim()) {
      blockers.push({ field: 'subject', label: 'Sujet du stage', message: 'Sujet du stage requis' });
    }
    if (!form.value.activities?.trim()) {
      blockers.push({ field: 'activities', label: 'Missions / Activités', message: 'Activités requises' });
    }
  }
  return blockers;
});

// L'étape a des erreurs / champs manquants s'il reste des éléments bloquants
const stepHasErrors = computed(() => {
  return currentStepBlockers.value.length > 0;
});

// ─── Form state ─────────────────────────────────────────────────────────────
const form = ref({
  // Étape 1 : Étudiant & Assurances
  stagePeriodeIri: '',
  phone: '',
  emailPerso: '',
  insuranceCompany: '',
  insurancePolicyNumber: '',

  // Étape 2 : Entreprise
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

  // Représentant légal (signe la convention)
  signatoryCivilite: 'M',
  signatoryPrenom: '',
  signatoryNom: '',
  signatoryTitle: '',
  signatoryEmail: '',
  signatoryPhone: '',

  // Maître de stage (encadrant en entreprise) — peut être identique au signataire
  tuteurSameAsSignatory: false,
  supervisorCivilite: 'M',
  supervisorPrenom: '',
  supervisorNom: '',
  supervisorFunction: '',
  supervisorEmail: '',
  supervisorPhone: '',

  // Étape 3 : Dates & Mission
  startDate: '',
  endDate: '',
  weeklyHours: 35,
  salaryAmount: 4.35,
  subject: '',
  activities: '',
  amenagementStage: '',
});

// Quand "même personne" est coché, synchroniser depuis le signataire
const syncTuteurFromSignatory = () => {
  if (form.value.tuteurSameAsSignatory) {
    form.value.supervisorCivilite = form.value.signatoryCivilite;
    form.value.supervisorPrenom   = form.value.signatoryPrenom;
    form.value.supervisorNom      = form.value.signatoryNom;
    form.value.supervisorFunction = form.value.signatoryTitle;
    form.value.supervisorEmail    = form.value.signatoryEmail;
    form.value.supervisorPhone    = form.value.signatoryPhone;
  }
};

watch(() => form.value.tuteurSameAsSignatory, (val) => {
  if (val) {
    syncTuteurFromSignatory();
  }
});

watch([
  () => form.value.signatoryCivilite,
  () => form.value.signatoryPrenom,
  () => form.value.signatoryNom,
  () => form.value.signatoryTitle,
  () => form.value.signatoryEmail,
  () => form.value.signatoryPhone,
], () => {
  if (form.value.tuteurSameAsSignatory) {
    syncTuteurFromSignatory();
  }
});

// Auto-correction / extraction du code postal et de la ville si inclus dans l'adresse
watch(() => form.value.companyAddress, (val) => {
  if (val && typeof val === 'object') {
    if (!val.codePostal?.trim() && val.adresse) {
      const match = val.adresse.match(/\b(\d{5})\b/);
      if (match) {
        val.codePostal = match[1];
      }
    }
    if (!val.ville?.trim() && val.adresse && val.codePostal) {
      const parts = val.adresse.split(val.codePostal);
      if (parts[1]?.trim()) {
        val.ville = parts[1].replace(/^[,\s-]+|[,\s-]+$/g, '').trim();
      }
    }
  }
}, { deep: true, immediate: true });

// Steps labels
const steps = [
  { id: 1, label: 'Profil & Sécurité sociale', icon: 'pi pi-user' },
  { id: 2, label: "Entreprise d'accueil", icon: 'pi pi-briefcase' },
  { id: 3, label: 'Dates & Mission',       icon: 'pi pi-file-edit' },
  { id: 4, label: 'Vérification',           icon: 'pi pi-check-circle' }
];

// ─── Init ────────────────────────────────────────────────────────────────────
onMounted(async () => {
  loading.value = true;
  try {
    await Promise.all([
      userStore.initAuth(),
      etablissementStore.getEtablissement()
    ]);
    const user = await userStore.getUser();
    if (user) {
      form.value.phone      = user.tel1 || user.tel2 || '';
      form.value.emailPerso = user.mailPerso || '';
      form.value.insuranceCompany = user.opt?.intitule_securite_sociale || user.intituleSecuriteSociale || 'CPAM';
      form.value.insurancePolicyNumber = user.opt?.adresse_securite_sociale || user.adresseSecuriteSociale || '';
    }

    const studentDeptId = userStore.departementDefaut?.id 
      || userStore.scolariteActif?.departement?.id 
      || user?.scolarites?.find(s => s.actif)?.departement?.id;

    const params = { actif: true };
    if (studentDeptId) {
      params.departement = studentDeptId;
    }
    const response = await getStagePeriodesService(params);
    let periods = Array.isArray(response) ? response : [];
    if (studentDeptId) {
      periods = periods.filter(p => {
        const pDeptId = p.departement?.id || p.semestreProgramme?.annee?.pn?.diplome?.departement?.id;
        return !pDeptId || pDeptId === studentDeptId;
      });
    }
    stagePeriodes.value = periods;
    if (route.query.periodId) {
      const exists = stagePeriodes.value.find(p => p.id == route.query.periodId);
      if (exists) {
        form.value.stagePeriodeIri = `/api/stage_periodes/${exists.id}`;
      }
    } else if (stagePeriodes.value.length === 1) {
      form.value.stagePeriodeIri = `/api/stage_periodes/${stagePeriodes.value[0].id}`;
    }
    // Charger les déclarations de stage existantes pour l'étudiant
    const stagesParams = {};
    if (userStore.userId) {
      stagesParams.etudiant = `/api/etudiants/${userStore.userId}`;
    }
    const studentStages = await getStageEtudiantsService(stagesParams, false);
    allStudentStages.value = Array.isArray(studentStages) ? studentStages : [];
    populateFromExistingStage();
  } catch (error) {
    console.error('Erreur de chargement:', error);
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Erreur lors du chargement des données initiales.', life: 3000 });
  } finally {
    loading.value = false;
  }
});

// ─── Computed ────────────────────────────────────────────────────────────────
const selectedPeriod = computed(() => {
  return stagePeriodes.value.find(p => `/api/stage_periodes/${p.id}` === form.value.stagePeriodeIri || p.id == form.value.stagePeriodeIri);
});

const populateFromExistingStage = () => {
  const curPeriodId = selectedPeriod.value?.id;
  if (!curPeriodId || !allStudentStages.value.length) return;

  const found = allStudentStages.value.find(s => {
    const sPid = typeof s.stagePeriode === 'object' && s.stagePeriode !== null
      ? s.stagePeriode.id
      : (typeof s.stagePeriode === 'string' ? parseInt(s.stagePeriode.split('/').pop(), 10) : s.stagePeriode);
    return sPid === curPeriodId;
  });

  if (found) {
    existingStageId.value = found.id;
    if (found.sujetStage) form.value.subject = found.sujetStage;
    if (found.activites) form.value.activities = found.activites;
    if (found.amenagementStage) form.value.amenagementStage = found.amenagementStage;
    if (found.dateDebutStage) form.value.startDate = new Date(found.dateDebutStage);
    if (found.dateFinStage) form.value.endDate = new Date(found.dateFinStage);
    if (found.dureeHebdomadaire) form.value.weeklyHours = found.dureeHebdomadaire;
    if (found.gratificationMontant) form.value.salaryAmount = found.gratificationMontant;
    if (found.assuranceCompagnie) form.value.insuranceCompany = found.assuranceCompagnie;
    if (found.assuranceNumero) form.value.insurancePolicyNumber = found.assuranceNumero;

    if (found.entreprise) {
      if (found.entreprise.raisonSociale) form.value.companyName = found.entreprise.raisonSociale;
      if (found.entreprise.siret) form.value.companySiret = found.entreprise.siret;
      if (found.entreprise.adresse) {
        form.value.companyAddress = {
          adresse: found.entreprise.adresse.adresse || '',
          complement1: found.entreprise.adresse.complement1 || '',
          complement2: found.entreprise.adresse.complement2 || '',
          ville: found.entreprise.adresse.ville || '',
          codePostal: found.entreprise.adresse.codePostal || '',
          pays: found.entreprise.adresse.pays || 'France',
        };
      }
      if (found.entreprise.responsable) {
        const resp = found.entreprise.responsable;
        if (resp.civilite) form.value.signatoryCivilite = resp.civilite;
        if (resp.prenom) form.value.signatoryPrenom = resp.prenom;
        if (resp.nom) form.value.signatoryNom = resp.nom;
        if (resp.fonction) form.value.signatoryTitle = resp.fonction;
        if (resp.telephone) form.value.signatoryPhone = resp.telephone;
        if (resp.email) form.value.signatoryEmail = resp.email;
      }
    }

    if (found.tuteur) {
      const tut = found.tuteur;
      if (tut.civilite) form.value.supervisorCivilite = tut.civilite;
      if (tut.prenom) form.value.supervisorPrenom = tut.prenom;
      if (tut.nom) form.value.supervisorNom = tut.nom;
      if (tut.fonction) form.value.supervisorFunction = tut.fonction;
      if (tut.telephone) form.value.supervisorPhone = tut.telephone;
      if (tut.email) form.value.supervisorEmail = tut.email;
    }
  } else {
    existingStageId.value = null;
  }
};

const areDatesEditable = computed(() => {
  return !!selectedPeriod.value?.datesFlexibles;
});

const minGratification = computed(() => {
  const settings = etablissementStore.etablissement?.settings;
  const val = Number(settings?.stage?.gratification_minimale);
  return !isNaN(val) && val > 0 ? val : 4.35;
});

const formatDate = (val) => {
  if (!val) return '-';
  const d = typeof val === 'string' ? new Date(val) : val;
  return isNaN(d.getTime()) ? '-' : d.toLocaleDateString('fr-FR');
};

const formatDateForApi = (val) => {
  if (!val) return null;
  const d = typeof val === 'string' ? new Date(val) : val;
  if (isNaN(d.getTime())) return null;
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

// Pré-remplir les dates depuis la période choisie (et verrouiller si non flexibles)
watch(selectedPeriod, (period) => {
  if (period) {
    if (period.dateDebut) {
      const dDebut = new Date(period.dateDebut);
      if (!form.value.startDate || !period.datesFlexibles) {
        form.value.startDate = dDebut;
      }
    }
    if (period.dateFin) {
      const dFin = new Date(period.dateFin);
      if (!form.value.endDate || !period.datesFlexibles) {
        form.value.endDate = dFin;
      }
    }
    populateFromExistingStage();
  }
}, { immediate: true });

// Garantir que la gratification ne descende pas sous le minimum établissement
watch(minGratification, (minVal) => {
  if (form.value.salaryAmount === null || form.value.salaryAmount === undefined || form.value.salaryAmount === '' || Number(form.value.salaryAmount) < minVal) {
    form.value.salaryAmount = minVal;
  }
}, { immediate: true });

const selectedPeriodLabel = computed(() => {
  const selected = selectedPeriod.value;
  return selected ? `${selected.libelle} (${selected.nbSemaines} sem.)` : '';
});

const periodOptions = computed(() => {
  return stagePeriodes.value.map(p => ({
    label: `${p.libelle} (${p.nbSemaines} sem.)${p.anneeUniversitaire?.libelle ? ' - ' + p.anneeUniversitaire.libelle : ''}`,
    value: `/api/stage_periodes/${p.id}`
  }));
});

const supervisorDisplay = computed(() => {
  if (form.value.tuteurSameAsSignatory) {
    return `${form.value.signatoryCivilite} ${form.value.signatoryPrenom} ${form.value.signatoryNom}`.trim() || '-';
  }
  return `${form.value.supervisorCivilite} ${form.value.supervisorPrenom} ${form.value.supervisorNom}`.trim() || '-';
});

// ─── Navigation ──────────────────────────────────────────────────────────────
const nextStep = () => {
  if (currentStepBlockers.value.length > 0) {
    const first = currentStepBlockers.value[0];
    toast.add({
      severity: 'warn',
      summary: 'Champ requis manquant',
      detail: `${first.label} : ${first.message}`,
      life: 3500
    });
    return;
  }

  if (currentStep.value < totalSteps) {
    if (currentStep.value === 2) syncTuteurFromSignatory();
    currentStep.value++;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
};
const prevStep = () => { if (currentStep.value > 1) currentStep.value--; };

// ─── Soumission ──────────────────────────────────────────────────────────────
const isSubmitting = ref(false);

const submitRequest = async () => {
  isSubmitting.value = true;
  try {
    // 1. Mise à jour du profil étudiant
    await api.patch(`/api/etudiants/${userStore.userId}`, {
      tel1:      form.value.phone,
      mailPerso: form.value.emailPerso,
    }, { headers: { 'Content-Type': 'application/merge-patch+json' } });

    // 2. Résolution du maître de stage
    const tuteur = form.value.tuteurSameAsSignatory
      ? {
          civilite:  form.value.signatoryCivilite,
          prenom:    form.value.signatoryPrenom,
          nom:       form.value.signatoryNom,
          fonction:  form.value.signatoryTitle,
          telephone: form.value.signatoryPhone,
          email:     form.value.signatoryEmail,
        }
      : {
          civilite:  form.value.supervisorCivilite,
          prenom:    form.value.supervisorPrenom,
          nom:       form.value.supervisorNom,
          fonction:  form.value.supervisorFunction,
          telephone: form.value.supervisorPhone,
          email:     form.value.supervisorEmail,
        };

    // 3. Calcul durée approx.
    const calcDureeJours = () => {
      if (!form.value.startDate || !form.value.endDate) return 0;
      const diffMs = new Date(form.value.endDate) - new Date(form.value.startDate);
      return Math.max(0, Math.round(diffMs / (1000 * 60 * 60 * 24)));
    };

    // 4. Payload StageEtudiant
    const payload = {
      stagePeriode:          form.value.stagePeriodeIri,
      etudiant:              `/api/etudiants/${userStore.userId}`,
      sujetStage:            form.value.subject,
      activites:             form.value.activities,
      amenagementStage:      form.value.amenagementStage,
      dateDebutStage:        formatDateForApi(form.value.startDate),
      dateFinStage:          formatDateForApi(form.value.endDate),
      dureeHebdomadaire:     Number(form.value.weeklyHours),
      dureeJoursStage:       calcDureeJours(),
      gratification:         Number(form.value.salaryAmount) > 0,
      gratificationMontant:  Number(form.value.salaryAmount),
      gratificationPeriode:  'H',
      assuranceCompagnie:    form.value.insuranceCompany       || null,
      assuranceNumero:       form.value.insurancePolicyNumber  || null,
      dateDepotFormulaire:   new Date().toISOString(),
      etatStage:             'ETAT_STAGE_DEPOSE',
      entreprise: {
        raisonSociale: form.value.companyName,
        siret:         form.value.companySiret,
        adresse: {
          adresse:     form.value.companyAddress.adresse,
          complement1: form.value.companyAddress.complement1 || '',
          complement2: form.value.companyAddress.complement2 || '',
          ville:       form.value.companyAddress.ville,
          codePostal:  form.value.companyAddress.codePostal,
          pays:        form.value.companyAddress.pays || 'France',
        },
        responsable: {
          civilite:  form.value.signatoryCivilite,
          prenom:    form.value.signatoryPrenom,
          nom:       form.value.signatoryNom,
          fonction:  form.value.signatoryTitle,
          telephone: form.value.signatoryPhone,
          email:     form.value.signatoryEmail,
        }
      },
      tuteur,
    };

    if (existingStageId.value) {
      await updateStageEtudiantService(existingStageId.value, payload);
    } else {
      await createStageEtudiantService(payload);
    }

    toast.add({
      severity: 'success',
      summary: 'Demande déposée',
      detail: 'Votre convention de stage a été déposée avec succès. Elle est désormais transmise au responsable pour validation pédagogique.',
      life: 5000
    });
    router.push({ name: 'EtudiantDashboard' });

  } catch (error) {
    console.error('Erreur lors de la soumission:', error);
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de soumettre la demande. Veuillez vérifier vos saisies.', life: 5000 });
  } finally {
    isSubmitting.value = false;
  }
};

const cancelRequest = () => { router.push({ name: 'EtudiantDashboard' }); };
</script>

<template>
  <div class="mx-auto space-y-6">
    <Toast />

    <!-- ── En-tête ─────────────────────────────────────────────────────────── -->
    <div class="flex items-center gap-4 justify-between border-b border-slate-200 dark:border-slate-800 pb-5">
      <div>
        <h1 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2">
          <i class="pi pi-file-edit text-teal-600"></i>
          <span>{{ existingStageId ? 'Compléter / Modifier ma convention' : 'Demande de Convention de Stage' }}</span>
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
          {{ existingStageId ? 'Modifiez et déposez à nouveau votre convention de stage auprès du responsable pédagogique.' : 'Remplissez les informations nécessaires pour la validation et l\'édition de votre convention de stage.' }}
        </p>
      </div>
      <button
        @click="cancelRequest"
        class="text-xs font-semibold px-4 py-2 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-all cursor-pointer"
      >
        Annuler
      </button>
    </div>

    <!-- ── Stepper ─────────────────────────────────────────────────────────── -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
      <div
        v-for="step in steps"
        :key="step.id"
        :class="[
          'flex flex-col items-center text-center p-3 rounded-2xl border transition-all duration-300',
          currentStep === step.id
            ? 'bg-teal-50/70 dark:bg-teal-950/30 border-teal-200 dark:border-teal-800 text-teal-800 dark:text-teal-300 shadow-2xs'
            : currentStep > step.id
            ? 'bg-slate-50 dark:bg-slate-800/40 border-slate-200 dark:border-slate-700 text-emerald-600 dark:text-emerald-400'
            : 'bg-white dark:bg-slate-800/20 border-slate-200 dark:border-slate-800 text-slate-400 dark:text-slate-600'
        ]"
      >
        <div
          :class="[
            'w-9 h-9 rounded-xl flex items-center justify-center font-bold text-xs border mb-1.5 transition-all shadow-2xs',
            currentStep === step.id
              ? 'border-teal-600 bg-teal-600 text-white'
              : currentStep > step.id
              ? 'border-emerald-500 bg-emerald-500 text-white'
              : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-400'
          ]"
        >
          <i v-if="currentStep > step.id" class="pi pi-check text-xs"></i>
          <span v-else>{{ step.id }}</span>
        </div>
        <span class="text-[11px] font-bold tracking-tight">{{ step.label }}</span>
      </div>
    </div>

    <!-- ── Carte principale ───────────────────────────────────────────────── -->
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80 rounded-3xl p-6 md:p-8 shadow-xs space-y-6">

      <!-- ═══════════════════════════════════════════════════════════════════
           ÉTAPE 1 : Profil étudiant & Assurances
           ═══════════════════════════════════════════════════════════════════ -->
      <div v-if="currentStep === 1" class="space-y-6 animate-slide-in">
        <FormSection
          :icon="UserIcon"
          tone="teal"
          title="Coordonnées de l'étudiant & Sécurité sociale"
          description="Vérifiez vos coordonnées personnelles et précisez votre caisse de sécurité sociale obligatoire."
        >
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <!-- Période de stage -->
            <ValidatedInput
              v-model="form.stagePeriodeIri"
              name="stagePeriodeIri"
              label="Période du parcours universitaire"
              type="select"
              placeholder="Sélectionnez une période de stage"
              :options="periodOptions"
              :rules="validationRules.required"
              :validate-on-input="true"
              @validation="res => handleValidation('stagePeriodeIri', res)"
              :disabled="!!route.query.periodId"
              :help-text="route.query.periodId ? 'La période est pré-remplie à partir de votre tableau de bord.' : ''"
              class="md:col-span-2"
            />

            <!-- Téléphone -->
            <ValidatedInput
              v-model="form.phone"
              name="phone"
              label="Téléphone personnel"
              placeholder="Ex: 06 12 34 56 78 ou +33612345678"
              type="text"
              :rules="[validationRules.phone, validationRules.required]"
              :validate-on-input="true"
              @validation="res => handleValidation('phone', res)"
            />

            <!-- Email perso -->
            <ValidatedInput
              v-model="form.emailPerso"
              name="emailPerso"
              label="E-mail personnel (de secours)"
              placeholder="Ex: etudiant@gmail.com"
              type="text"
              :rules="[validationRules.email, validationRules.required]"
              :validate-on-input="true"
              @validation="res => handleValidation('emailPerso', res)"
            />

            <!-- Organisme de sécurité sociale -->
            <ValidatedInput
              v-model="form.insuranceCompany"
              name="insuranceCompany"
              label="Organisme de sécurité sociale"
              placeholder="Ex: CPAM de l'Aube, MSA, etc."
              type="text"
              :validate-on-input="true"
              help-text="Caisse d'assurance maladie de rattachement du stagiaire (lieu de résidence)"
            />
            <ValidatedInput
              v-model="form.insurancePolicyNumber"
              name="insurancePolicyNumber"
              label="Adresse de l'organisme"
              placeholder="Ex: 113 rue Roger Salengro, 10000 Troyes"
              type="text"
              :validate-on-input="true"
              help-text="Adresse de la caisse à contacter en cas d'accident du travail"
            />
          </div>
        </FormSection>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════
           ÉTAPE 2 : Entreprise d'accueil
           ═══════════════════════════════════════════════════════════════════ -->
      <div v-if="currentStep === 2" class="space-y-6 animate-slide-in">
        <!-- Informations générales de l'entreprise -->
        <FormSection
          :icon="BuildingOffice2Icon"
          tone="teal"
          title="Identification de l'entreprise"
          description="Tous les champs sont obligatoires. Coordonnées et adresse de la structure qui vous accueille pour votre stage."
        >
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <ValidatedInput
              v-model="form.companyName"
              name="companyName"
              label="Raison sociale"
              placeholder="Ex: Google France"
              type="text"
              :rules="[validationRules.required]"
              :validate-on-input="true"
              @validation="res => handleValidation('companyName', res)"
            />
            <ValidatedInput
              v-model="form.companySiret"
              name="companySiret"
              label="Numéro SIRET"
              placeholder="Ex: 12345678900010"
              type="text"
              :rules="[validationRules.required, validationRules.numeric, validationRules.minLength(14), validationRules.maxLength(14)]"
              :validate-on-input="true"
              @validation="res => handleValidation('companySiret', res)"
            />
            <ValidatedInput
              v-model="form.companyPhone"
              name="companyPhone"
              label="Téléphone standard"
              placeholder="Ex: 01 02 03 04 05"
              type="text"
              :rules="[validationRules.required, validationRules.phone]"
              :validate-on-input="true"
              @validation="res => handleValidation('companyPhone', res)"
              class="md:col-span-2"
            />
            <div class="md:col-span-2">
              <ValidatedInput
                v-model="form.companyAddress"
                name="companyAddress"
                label="Adresse de l'entreprise"
                type="address"
                placeholder="Cherchez l'adresse de l'entreprise…"
                :rules="[validationRules.required]"
                :validate-on-input="true"
                @validation="res => handleValidation('companyAddress', res)"
              />
            </div>
          </div>
        </FormSection>

        <!-- Représentant légal (signataire) -->
        <FormSection
          :icon="UserIcon"
          tone="indigo"
          title="Représentant légal (signataire de la convention)"
          description="Tous les champs sont obligatoires. Personne habilitée à signer les conventions au nom de l'entreprise (Directeur, DRH...)."
        >
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <ValidatedInput
              v-model="form.signatoryCivilite"
              name="signatoryCivilite"
              label="Civilité"
              type="select"
              :options="[{ label: 'Monsieur (M.)', value: 'M' }, { label: 'Madame (Mme)', value: 'Mme' }]"
              :rules="[validationRules.required]"
              :validate-on-input="true"
              @validation="res => handleValidation('signatoryCivilite', res)"
            />
            <ValidatedInput
              v-model="form.signatoryPrenom"
              name="signatoryPrenom"
              label="Prénom"
              placeholder="Ex: Sylvie"
              type="text"
              :rules="[validationRules.required]"
              :validate-on-input="true"
              @validation="res => handleValidation('signatoryPrenom', res)"
            />
            <ValidatedInput
              v-model="form.signatoryNom"
              name="signatoryNom"
              label="Nom"
              placeholder="Ex: MARTIN"
              type="text"
              :rules="[validationRules.required]"
              :validate-on-input="true"
              @validation="res => handleValidation('signatoryNom', res)"
            />
            <ValidatedInput
              v-model="form.signatoryTitle"
              name="signatoryTitle"
              label="Fonction / Titre"
              placeholder="Ex: Directrice RH"
              type="text"
              :rules="[validationRules.required]"
              :validate-on-input="true"
              @validation="res => handleValidation('signatoryTitle', res)"
            />
            <ValidatedInput
              v-model="form.signatoryEmail"
              name="signatoryEmail"
              label="E-mail"
              placeholder="Ex: s.martin@entreprise.com"
              type="text"
              :rules="[validationRules.required, validationRules.email]"
              :validate-on-input="true"
              @validation="res => handleValidation('signatoryEmail', res)"
            />
            <ValidatedInput
              v-model="form.signatoryPhone"
              name="signatoryPhone"
              label="Téléphone direct"
              placeholder="Ex: 01 02 03 04 05"
              type="text"
              :rules="[validationRules.required, validationRules.phone]"
              :validate-on-input="true"
              @validation="res => handleValidation('signatoryPhone', res)"
            />
          </div>
        </FormSection>

        <!-- Maître de stage (encadrant) -->
        <FormSection
          :icon="UserIcon"
          tone="purple"
          title="Maître de stage (encadrant en entreprise)"
          description="Tous les champs sont obligatoires. Votre responsable opérationnel au quotidien au sein de l'entreprise."
        >
          <!-- Toggle "même personne" avec ToggleCard -->
          <ToggleCard
            v-model="form.tuteurSameAsSignatory"
            title="Le maître de stage est identique au représentant légal"
            description="Cochez cette option si la personne qui vous encadre est également celle qui signe la convention."
            class="mb-4"
          />

          <!-- Champs du tuteur (masqués si même personne) -->
          <div v-if="!form.tuteurSameAsSignatory" class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <ValidatedInput
              v-model="form.supervisorCivilite"
              name="supervisorCivilite"
              label="Civilité"
              type="select"
              :options="[{ label: 'Monsieur (M.)', value: 'M' }, { label: 'Madame (Mme)', value: 'Mme' }]"
              :rules="[validationRules.required]"
              :validate-on-input="true"
              @validation="res => handleValidation('supervisorCivilite', res)"
            />
            <ValidatedInput
              v-model="form.supervisorPrenom"
              name="supervisorPrenom"
              label="Prénom"
              placeholder="Ex: Robert"
              type="text"
              :rules="[validationRules.required]"
              :validate-on-input="true"
              @validation="res => handleValidation('supervisorPrenom', res)"
            />
            <ValidatedInput
              v-model="form.supervisorNom"
              name="supervisorNom"
              label="Nom"
              placeholder="Ex: LEGRAND"
              type="text"
              :rules="[validationRules.required]"
              :validate-on-input="true"
              @validation="res => handleValidation('supervisorNom', res)"
            />
            <ValidatedInput
              v-model="form.supervisorFunction"
              name="supervisorFunction"
              label="Fonction"
              placeholder="Ex: Chef de Projet"
              type="text"
              :rules="[validationRules.required]"
              :validate-on-input="true"
              @validation="res => handleValidation('supervisorFunction', res)"
            />
            <ValidatedInput
              v-model="form.supervisorEmail"
              name="supervisorEmail"
              label="E-mail"
              placeholder="Ex: r.legrand@entreprise.com"
              type="text"
              :rules="[validationRules.required, validationRules.email]"
              :validate-on-input="true"
              @validation="res => handleValidation('supervisorEmail', res)"
            />
            <ValidatedInput
              v-model="form.supervisorPhone"
              name="supervisorPhone"
              label="Téléphone"
              placeholder="Ex: 06 99 88 77 66"
              type="text"
              :rules="[validationRules.required, validationRules.phone]"
              :validate-on-input="true"
              @validation="res => handleValidation('supervisorPhone', res)"
            />
          </div>

          <!-- Récap quand même personne -->
          <div v-else class="q-callout q-callout-info">
            <i class="pi pi-info-circle text-base text-blue-600 dark:text-blue-400 mt-0.5"></i>
            <div>
              <p class="font-semibold">Coordonnées synchronisées avec le représentant légal</p>
              <p class="text-2xs text-blue-700 dark:text-blue-300 mt-0.5">
                {{ form.signatoryCivilite }} {{ form.signatoryPrenom }} {{ form.signatoryNom }}
                <span v-if="form.signatoryTitle"> — {{ form.signatoryTitle }}</span>
              </p>
            </div>
          </div>
        </FormSection>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════
           ÉTAPE 3 : Dates & Mission
           ═══════════════════════════════════════════════════════════════════ -->
      <div v-if="currentStep === 3" class="space-y-6 animate-slide-in">
        <FormSection
          :icon="CalendarDaysIcon"
          tone="teal"
          title="Dates, Gratification & Missions"
          description="Période effective de réalisation, indemnisation légale et descriptif du projet confié."
        >
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <!-- Statut de flexibilité des dates -->
            <div v-if="!areDatesEditable" class="md:col-span-2 q-callout q-callout-info">
              <i class="pi pi-lock text-base text-blue-600 dark:text-blue-400 mt-0.5"></i>
              <div>
                <p class="font-semibold">Dates fixes de la période de stage</p>
                <p class="text-2xs text-blue-700 dark:text-blue-300 mt-0.5">
                  Les dates de début et de fin sont imposées par la maquette pédagogique : du {{ formatDate(selectedPeriod?.dateDebut) }} au {{ formatDate(selectedPeriod?.dateFin) }}. Elles ne sont pas modifiables.
                </p>
              </div>
            </div>
            <div v-else class="md:col-span-2 q-callout q-callout-success">
              <i class="pi pi-calendar-plus text-base text-emerald-600 dark:text-emerald-400 mt-0.5"></i>
              <div>
                <p class="font-semibold">Dates flexibles autorisées</p>
                <p class="text-2xs text-emerald-700 dark:text-emerald-300 mt-0.5">
                  Les dates ont été pré-remplies selon le calendrier standard (du {{ formatDate(selectedPeriod?.dateDebut) }} au {{ formatDate(selectedPeriod?.dateFin) }}). Vous pouvez les ajuster en fonction des dates réelles convenues avec l'entreprise d'accueil.
                </p>
              </div>
            </div>

            <!-- Rappel des interruptions programmées de la période -->
            <div
              v-if="selectedPeriod?.periodesInterruption && selectedPeriod.periodesInterruption.length > 0"
              class="md:col-span-2 bg-amber-50/70 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800/50 rounded-2xl p-4 text-xs space-y-2.5"
            >
              <div class="flex items-center gap-2 font-bold text-amber-900 dark:text-amber-200">
                <i class="pi pi-exclamation-circle text-amber-600 dark:text-amber-400 text-sm"></i>
                <span>Rappel des interruptions programmées sur cette période :</span>
              </div>
              <p class="text-amber-800 dark:text-amber-300 text-[11px] leading-relaxed">
                Pendant ces périodes (ex: congés pédagogiques, examens ou fermeture), l'activité en entreprise est suspendue.
              </p>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-1">
                <div
                  v-for="inter in selectedPeriod.periodesInterruption"
                  :key="inter.id"
                  class="bg-white/90 dark:bg-slate-800/80 border border-amber-200 dark:border-amber-700/60 rounded-xl p-3 flex items-start justify-between gap-2 shadow-2xs"
                >
                  <div>
                    <span class="font-bold text-slate-800 dark:text-slate-100 block text-xs">
                      {{ inter.motif || 'Interruption de stage' }}
                    </span>
                    <span class="text-slate-500 dark:text-slate-400 text-[11px] mt-0.5 block">
                      Du {{ formatDate(inter.dateDebut) }} au {{ formatDate(inter.dateFin) }}
                    </span>
                  </div>
                  <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-md bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300 shrink-0">
                    Interruption
                  </span>
                </div>
              </div>
            </div>

            <ValidatedInput
              v-model="form.startDate"
              name="startDate"
              label="Date de début"
              type="date"
              :disabled="!areDatesEditable"
              :rules="[validationRules.required]"
              :validate-on-input="true"
              @validation="res => handleValidation('startDate', res)"
            />
            <ValidatedInput
              v-model="form.endDate"
              name="endDate"
              label="Date de fin"
              type="date"
              :disabled="!areDatesEditable"
              :rules="[validationRules.required]"
              :validate-on-input="true"
              @validation="res => handleValidation('endDate', res)"
            />
            <ValidatedInput
              v-model="form.weeklyHours"
              name="weeklyHours"
              label="Volume horaire hebdomadaire"
              type="number"
              :min="1"
              :max="48"
              :rules="[validationRules.required, validationRules.numeric, validationRules.minValue(1), validationRules.maxValue(48)]"
              :validate-on-input="true"
              @validation="res => handleValidation('weeklyHours', res)"
            />
            <ValidatedInput
              v-model="form.salaryAmount"
              name="salaryAmount"
              label="Gratification horaire nette (€/h)"
              type="number"
              :min="minGratification"
              :minfractiondigits="2"
              :maxfractiondigits="2"
              :rules="[validationRules.required, validationRules.minValue(minGratification)]"
              :help-text="`Minimum obligatoire fixé par l'établissement : ${minGratification} €/h`"
              :validate-on-input="true"
              @validation="res => handleValidation('salaryAmount', res)"
            />
            <ValidatedInput
              v-model="form.subject"
              name="subject"
              label="Sujet de stage (mission principale)"
              placeholder="Ex: Déploiement d'une solution de supervision réseau…"
              type="text"
              :rules="validationRules.required"
              @validation="res => handleValidation('subject', res)"
              class="md:col-span-2"
            />
            <ValidatedInput
              v-model="form.activities"
              name="activities"
              label="Détail des activités confiées"
              placeholder="Décrivez les tâches et compétences mises en œuvre au quotidien…"
              type="textarea"
              :rules="validationRules.required"
              @validation="res => handleValidation('activities', res)"
              class="md:col-span-2"
            />
            <ValidatedInput
              v-model="form.amenagementStage"
              name="amenagementStage"
              label="Aménagements éventuels"
              placeholder="Ex: Télétravail 2 jours/semaine, horaires adaptés..."
              type="text"
              class="md:col-span-2"
            />
          </div>
        </FormSection>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════
           ÉTAPE 4 : Récapitulatif
           ═══════════════════════════════════════════════════════════════════ -->
      <div v-if="currentStep === 4" class="space-y-6 animate-slide-in">
        <FormSection
          :icon="ClipboardDocumentCheckIcon"
          tone="emerald"
          title="Récapitulatif et validation"
          description="Vérifiez l'ensemble des informations avant d'envoyer votre demande pour signature."
        >
          <div class="bg-slate-50/70 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-700/80 rounded-2xl p-5 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
              <!-- Période & contact -->
              <div>
                <span class="text-slate-400 block font-medium">Période du stage :</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">{{ selectedPeriodLabel || '-' }}</span>
              </div>
              <div>
                <span class="text-slate-400 block font-medium">Téléphone / e-mail étudiant :</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">{{ form.phone || '-' }} — {{ form.emailPerso || '-' }}</span>
              </div>
              <div>
                <span class="text-slate-400 block font-medium">Sécurité sociale :</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">
                  {{ form.insuranceCompany || 'Non renseigné' }}
                  <span v-if="form.insurancePolicyNumber" class="text-xs text-slate-500 font-normal block mt-0.5">
                    {{ form.insurancePolicyNumber }}
                  </span>
                </span>
              </div>

              <div class="md:col-span-2 border-t border-slate-200 dark:border-slate-700/60 pt-3 mt-1">
                <span class="text-2xs font-bold uppercase tracking-wider text-slate-400 block mb-2">Entreprise</span>
              </div>
              <div class="md:col-span-2">
                <span class="text-slate-400 block font-medium">Raison sociale &amp; SIRET :</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">{{ form.companyName || '-' }} <span class="font-normal">({{ form.companySiret || 'SIRET non renseigné' }})</span></span>
              </div>
              <div class="md:col-span-2">
                <span class="text-slate-400 block font-medium">Adresse :</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">
                  {{ form.companyAddress.adresse || '-' }}, {{ form.companyAddress.codePostal }} {{ form.companyAddress.ville }}
                </span>
              </div>
              <div>
                <span class="text-slate-400 block font-medium">Représentant légal :</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">{{ form.signatoryCivilite }} {{ form.signatoryPrenom }} {{ form.signatoryNom || '-' }}</span>
                <span class="text-slate-500 block">{{ form.signatoryTitle }}</span>
              </div>
              <div>
                <span class="text-slate-400 block font-medium">Maître de stage :</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">{{ supervisorDisplay }}</span>
                <span v-if="form.tuteurSameAsSignatory" class="text-2xs text-teal-600 dark:text-teal-400 block">(idem représentant légal)</span>
              </div>

              <div class="md:col-span-2 border-t border-slate-200 dark:border-slate-700/60 pt-3 mt-1">
                <span class="text-2xs font-bold uppercase tracking-wider text-slate-400 block mb-2">Mission</span>
              </div>
              <div>
                <span class="text-slate-400 block font-medium">Dates :</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">
                  Du {{ formatDate(form.startDate) }} au {{ formatDate(form.endDate) }}
                </span>
                <span v-if="!areDatesEditable" class="text-2xs text-slate-500 block mt-0.5">(dates fixes de la période)</span>
                <span v-else class="text-2xs text-teal-600 dark:text-teal-400 block mt-0.5">(dates flexibles adaptées)</span>
              </div>
              <div>
                <span class="text-slate-400 block font-medium">Gratification :</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">{{ form.salaryAmount }} €/h — {{ form.weeklyHours }}h/semaine</span>
                <span class="text-2xs text-slate-500 block mt-0.5">(min. établissement : {{ minGratification }} €/h)</span>
              </div>
              <div class="md:col-span-2">
                <span class="text-slate-400 block font-medium">Sujet :</span>
                <p class="font-bold text-slate-800 dark:text-slate-200 mt-1">{{ form.subject || '-' }}</p>
              </div>
              <div class="md:col-span-2">
                <span class="text-slate-400 block font-medium">Activités :</span>
                <p class="font-medium text-slate-700 dark:text-slate-300 mt-1 leading-relaxed whitespace-pre-line">{{ form.activities || '-' }}</p>
              </div>
            </div>
          </div>

          <!-- Déclaration sur l'honneur -->
          <div class="q-callout q-callout-warning">
            <ExclamationTriangleIcon class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
            <div>
              <h5 class="font-bold text-xs">Déclaration sur l'honneur</h5>
              <p class="text-[11px] mt-0.5">
                En soumettant cette demande, vous certifiez l'exactitude des informations relatives à l'entreprise d'accueil, à votre organisme de sécurité sociale et aux modalités de stage.
              </p>
            </div>
          </div>
        </FormSection>
      </div>

      <!-- ── Bannière de statut de validation en "Live" ─────────────────────── -->
      <div v-if="currentStep < totalSteps" class="pt-2">
        <!-- Si bloquant : alerte claire avec la liste des champs manquants -->
        <div
          v-if="currentStepBlockers.length > 0"
          class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/60 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs"
        >
          <div class="flex items-start gap-2.5">
            <ExclamationTriangleIcon class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
            <div>
              <span class="font-bold text-amber-900 dark:text-amber-200">
                Informations requises pour débloquer le bouton Suivant :
              </span>
              <div class="flex flex-wrap gap-1.5 mt-1.5">
                <span
                  v-for="b in currentStepBlockers"
                  :key="b.field"
                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-amber-100 dark:bg-amber-900/50 text-amber-900 dark:text-amber-200 font-semibold text-[11px]"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                  <span class="font-bold">{{ b.label }}</span>
                  <span class="text-amber-700 dark:text-amber-300">({{ b.message }})</span>
                </span>
              </div>
            </div>
          </div>
          <span class="text-[11px] text-amber-700 dark:text-amber-400 font-medium shrink-0 self-end sm:self-center">
            {{ currentStepBlockers.length }} champ{{ currentStepBlockers.length > 1 ? 's' : '' }} à compléter
          </span>
        </div>

        <!-- Si tout est valide pour l'étape : message positif et rassurant -->
        <div
          v-else
          class="bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/60 rounded-2xl px-4 py-2.5 flex items-center gap-2.5 text-xs font-semibold text-emerald-800 dark:text-emerald-300"
        >
          <i class="pi pi-check-circle text-emerald-600 dark:text-emerald-400 text-base shrink-0"></i>
          <span>Toutes les informations requises pour cette étape sont complètes. Vous pouvez passer à l'étape suivante.</span>
        </div>
      </div>

      <!-- ── Boutons de navigation ──────────────────────────────────────── -->
      <div class="flex items-center justify-between border-t border-slate-200 dark:border-slate-700/80 pt-5 mt-6">
        <button
          v-if="currentStep > 1"
          @click="prevStep"
          class="text-xs font-semibold px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition-all flex items-center gap-2 cursor-pointer"
        >
          <i class="pi pi-arrow-left text-[10px]"></i>
          <span>Précédent</span>
        </button>
        <div v-else></div>

        <div class="flex items-center gap-2">
          <button
            v-if="currentStep < totalSteps"
            type="button"
            @click="nextStep"
            :disabled="stepHasErrors"
            class="text-xs font-bold px-5 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 disabled:opacity-50 disabled:cursor-not-allowed text-white transition-all flex items-center gap-2 cursor-pointer shadow-xs"
            :title="stepHasErrors ? 'Complétez les champs requis ci-dessus pour continuer' : 'Passer à l\'étape suivante'"
          >
            <span>Suivant</span>
            <i class="pi pi-arrow-right text-[10px]"></i>
          </button>
          <button
            v-else
            @click="submitRequest"
            :disabled="isSubmitting || stepHasErrors"
            class="text-xs font-bold px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed text-white transition-all flex items-center gap-2 cursor-pointer shadow-xs"
          >
            <i v-if="isSubmitting" class="pi pi-spin pi-spinner"></i>
            <i v-else class="pi pi-check"></i>
            <span>{{ isSubmitting ? 'Dépôt en cours…' : (existingStageId ? 'Mettre à jour et déposer ma convention' : 'Déposer ma demande de convention') }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.animate-slide-in {
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from { opacity: 0; transform: translateX(10px); }
  to   { opacity: 1; transform: translateX(0); }
}
</style>

