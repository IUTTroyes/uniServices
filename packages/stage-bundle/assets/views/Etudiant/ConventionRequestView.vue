<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { getStagePeriodesService } from '../../requests/stage_service';
import { useUsersStore } from '@stores';
import api from '@helpers/axios';
import { ValidatedInput, validationRules } from '@components';

const router = useRouter();
const route = useRoute();
const toast = useToast();
const userStore = useUsersStore();

const currentStep = ref(1);
const totalSteps = 4;
const stagePeriodes = ref([]);
const loading = ref(false);

// Validation errors tracking
const formErrors = ref({});
const handleValidation = (fieldName, result) => {
  formErrors.value[fieldName] = result.isValid ? null : result.errorMessage;
};

// Check if current step has active validation errors
const stepHasErrors = computed(() => {
  if (currentStep.value === 1) {
    return !!formErrors.value.stagePeriodeIri || !!formErrors.value.phone || !!formErrors.value.emailPerso;
  } else if (currentStep.value === 2) {
    const companyErrors = !!formErrors.value.companyName || !!formErrors.value.companySiret || !!formErrors.value.companyPhone || !!formErrors.value.companyAddress;
    const signatoryErrors = !!formErrors.value.signatoryPrenom || !!formErrors.value.signatoryNom || !!formErrors.value.signatoryTitle || !!formErrors.value.signatoryEmail || !!formErrors.value.signatoryPhone;
    let tutorErrors = false;
    if (!form.value.tuteurSameAsSignatory) {
      tutorErrors = !!formErrors.value.supervisorPrenom || !!formErrors.value.supervisorNom || !!formErrors.value.supervisorFunction || !!formErrors.value.supervisorEmail || !!formErrors.value.supervisorPhone;
    }
    return companyErrors || signatoryErrors || tutorErrors;
  } else if (currentStep.value === 3) {
    return !!formErrors.value.startDate || !!formErrors.value.endDate || !!formErrors.value.weeklyHours || !!formErrors.value.salaryAmount || !!formErrors.value.subject || !!formErrors.value.activities;
  }
  return false;
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

// Steps labels
const steps = [
  { id: 1, label: 'Profil & Assurances', icon: 'pi pi-user' },
  { id: 2, label: "Entreprise d'accueil", icon: 'pi pi-briefcase' },
  { id: 3, label: 'Dates & Mission',       icon: 'pi pi-file-edit' },
  { id: 4, label: 'Vérification',           icon: 'pi pi-check-circle' }
];

// ─── Init ────────────────────────────────────────────────────────────────────
onMounted(async () => {
  loading.value = true;
  try {
    await userStore.initAuth();
    const user = await userStore.getUser();
    if (user) {
      form.value.phone      = user.tel1     || '';
      form.value.emailPerso = user.mailPerso || '';
    }
    const response = await getStagePeriodesService();
    stagePeriodes.value = response || [];
    if (route.query.periodId) {
      const exists = stagePeriodes.value.find(p => p.id == route.query.periodId);
      if (exists) {
        form.value.stagePeriodeIri = `/api/stage_periodes/${exists.id}`;
      }
    }
  } catch (error) {
    console.error('Erreur de chargement:', error);
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Erreur lors du chargement des données initiales.', life: 3000 });
  } finally {
    loading.value = false;
  }
});

// ─── Computed ────────────────────────────────────────────────────────────────
const selectedPeriodLabel = computed(() => {
  const selected = stagePeriodes.value.find(p => `/api/stage_periodes/${p.id}` === form.value.stagePeriodeIri);
  return selected ? `${selected.libelle} (${selected.nbSemaines} sem.)` : '';
});

const periodOptions = computed(() => {
  return stagePeriodes.value.map(p => ({
    label: `${p.libelle} (${p.nbSemaines} sem.)`,
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
  // Step 1 check
  if (currentStep.value === 1) {
    if (!form.value.stagePeriodeIri) {
      toast.add({ severity: 'warn', summary: 'Champs requis', detail: 'Veuillez sélectionner une période de stage.', life: 3000 });
      return;
    }
  }
  
  // Step 2 check
  if (currentStep.value === 2) {
    if (!form.value.companyName || !form.value.companyAddress || !form.value.signatoryPrenom || !form.value.signatoryNom || !form.value.signatoryTitle || !form.value.signatoryEmail || !form.value.signatoryPhone) {
      toast.add({ severity: 'warn', summary: 'Champs requis', detail: 'Veuillez remplir toutes les informations de l\'entreprise et du représentant.', life: 3000 });
      return;
    }
    if (!form.value.tuteurSameAsSignatory) {
      if (!form.value.supervisorPrenom || !form.value.supervisorNom || !form.value.supervisorFunction || !form.value.supervisorEmail || !form.value.supervisorPhone) {
        toast.add({ severity: 'warn', summary: 'Champs requis', detail: 'Veuillez remplir toutes les informations du maître de stage.', life: 3000 });
        return;
      }
    }
  }

  // Step 3 check
  if (currentStep.value === 3) {
    if (!form.value.startDate || !form.value.endDate || !form.value.subject || !form.value.activities) {
      toast.add({ severity: 'warn', summary: 'Champs requis', detail: 'Veuillez remplir les informations obligatoires de votre stage.', life: 3000 });
      return;
    }
  }

  if (stepHasErrors.value) {
    toast.add({ severity: 'warn', summary: 'Champs invalides', detail: 'Veuillez corriger les erreurs de saisie avant de continuer.', life: 3000 });
    return;
  }

  if (currentStep.value < totalSteps) {
    if (currentStep.value === 2) syncTuteurFromSignatory();
    currentStep.value++;
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
      dateDebutStage:        form.value.startDate   || null,
      dateFinStage:          form.value.endDate     || null,
      dureeHebdomadaire:     Number(form.value.weeklyHours),
      dureeJoursStage:       calcDureeJours(),
      gratification:         Number(form.value.salaryAmount) > 0,
      gratificationMontant:  Number(form.value.salaryAmount),
      gratificationPeriode:  'H',
      assuranceCompagnie:    form.value.insuranceCompany       || null,
      assuranceNumero:       form.value.insurancePolicyNumber  || null,
      dateDepotFormulaire:   new Date().toISOString(),
      etatStage:             'ETAT_STAGE_AUTORISE',
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

    await api.post('/api/stage_etudiants', payload, {
      headers: { 'Content-Type': 'application/ld+json' }
    });

    toast.add({ severity: 'success', summary: 'Demande soumise', detail: 'Votre demande de convention a été enregistrée avec succès.', life: 5000 });
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
    <div class="flex items-center gap-4 justify-between border-b border-slate-100 dark:border-slate-800 pb-5">
      <div>
        <h1 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2">
          <i class="pi pi-file-edit text-violet-600"></i>
          <span>Demande de Convention de Stage</span>
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
          Remplissez les informations nécessaires pour la signature de votre convention de stage.
        </p>
      </div>
      <button @click="cancelRequest" class="text-xs font-semibold px-4 py-2 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-all">
        Annuler
      </button>
    </div>

    <!-- ── Stepper ─────────────────────────────────────────────────────────── -->
    <div class="grid grid-cols-4 gap-4">
      <div
        v-for="step in steps"
        :key="step.id"
        :class="[
          'flex flex-col items-center text-center p-3 rounded-2xl transition-all duration-300',
          currentStep === step.id  ? 'bg-violet-50 dark:bg-violet-950/20 text-violet-700 dark:text-violet-400'
          : currentStep > step.id ? 'text-emerald-600 dark:text-emerald-400'
          : 'text-slate-400 dark:text-slate-600'
        ]"
      >
        <div :class="[
          'w-10 h-10 rounded-full flex items-center justify-center font-bold text-xs border-2 mb-2 transition-all',
          currentStep === step.id  ? 'border-violet-600 bg-violet-600 text-white'
          : currentStep > step.id ? 'border-emerald-500 bg-emerald-500 text-white'
          : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-500'
        ]">
          <i v-if="currentStep > step.id" class="pi pi-check text-xs"></i>
          <span v-else>{{ step.id }}</span>
        </div>
        <span class="text-[10px] font-bold uppercase tracking-wider hidden md:block">{{ step.label }}</span>
      </div>
    </div>

    <!-- ── Carte principale ───────────────────────────────────────────────── -->
    <div class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700/60 rounded-3xl p-8 shadow-sm">

      <!-- ═══════════════════════════════════════════════════════════════════
           ÉTAPE 1 : Profil étudiant & Assurances
           ═══════════════════════════════════════════════════════════════════ -->
      <div v-if="currentStep === 1" class="space-y-6 animate-slide-in">
        <h3 class="text-base font-bold text-slate-800 dark:text-slate-100 border-b border-slate-50 dark:border-slate-700/40 pb-2">
          Étape 1 : Coordonnées de l'étudiant &amp; Assurances
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Période de stage -->
          <ValidatedInput
            v-model="form.stagePeriodeIri"
            name="stagePeriodeIri"
            label="Période du parcours universitaire"
            type="select"
            placeholder="Sélectionnez une période de stage"
            :options="periodOptions"
            :rules="validationRules.required"
            @validation="res => handleValidation('stagePeriodeIri', res)"
            :disabled="!!route.query.periodId"
            :help-text="route.query.periodId ? 'La période est pré-remplie à partir de votre tableau de bord et n\'est pas modifiable.' : ''"
            class="md:col-span-2"
          />

          <!-- Téléphone -->
          <ValidatedInput
            v-model="form.phone"
            name="phone"
            label="Téléphone personnel"
            placeholder="Ex: 06 12 34 56 78"
            type="text"
            :rules="[validationRules.phone, validationRules.required]"
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
            @validation="res => handleValidation('emailPerso', res)"
          />

          <!-- Assurance -->
          <ValidatedInput
            v-model="form.insuranceCompany"
            name="insuranceCompany"
            label="Compagnie d'assurance RC"
            placeholder="Ex: MAIF, MACIF, MAAF…"
            type="text"
          />
          <ValidatedInput
            v-model="form.insurancePolicyNumber"
            name="insurancePolicyNumber"
            label="Numéro de police d'assurance"
            placeholder="Ex: 9876543-A"
            type="text"
          />
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════
           ÉTAPE 2 : Entreprise d'accueil
           ═══════════════════════════════════════════════════════════════════ -->
      <div v-if="currentStep === 2" class="space-y-8 animate-slide-in">
        <h3 class="text-base font-bold text-slate-800 dark:text-slate-100 border-b border-slate-50 dark:border-slate-700/40 pb-2">
          Étape 2 : Entreprise d'accueil
        </h3>

        <!-- ─── Informations générales de l'entreprise ─── -->
        <div class="space-y-4">
          <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-2">
            <i class="pi pi-building text-violet-500"></i> Identification
          </h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <ValidatedInput
              v-model="form.companyName"
              name="companyName"
              label="Raison sociale"
              placeholder="Ex: Google France"
              type="text"
              :rules="validationRules.required"
              @validation="res => handleValidation('companyName', res)"
            />
            <ValidatedInput
              v-model="form.companySiret"
              name="companySiret"
              label="Numéro SIRET"
              placeholder="Ex: 12345678900010"
              type="text"
              :rules="[validationRules.numeric, validationRules.minLength(14), validationRules.maxLength(14)]"
              @validation="res => handleValidation('companySiret', res)"
            />
            <ValidatedInput
              v-model="form.companyPhone"
              name="companyPhone"
              label="Téléphone standard"
              placeholder="Ex: 01 02 03 04 05"
              type="text"
              :rules="[validationRules.phone]"
              @validation="res => handleValidation('companyPhone', res)"
              class="md:col-span-2"
            />
          </div>

          <!-- Adresse avec autocomplete -->
          <div class="flex flex-col gap-2">
            <ValidatedInput
              v-model="form.companyAddress"
              name="companyAddress"
              label="Adresse de l'entreprise"
              type="address"
              placeholder="Cherchez l'adresse de l'entreprise…"
              :rules="validationRules.required"
              @validation="res => handleValidation('companyAddress', res)"
            />
          </div>
        </div>

        <!-- ─── Représentant légal (signataire de la convention) ─── -->
        <div class="space-y-4">
          <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-2">
            <i class="pi pi-id-card text-violet-500"></i> Représentant légal (signataire de la convention)
          </h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <ValidatedInput
              v-model="form.signatoryCivilite"
              name="signatoryCivilite"
              label="Civilité"
              type="select"
              :options="[{ label: 'Monsieur (M.)', value: 'M' }, { label: 'Madame (Mme)', value: 'Mme' }]"
              :rules="validationRules.required"
              @validation="res => handleValidation('signatoryCivilite', res)"
            />
            <ValidatedInput
              v-model="form.signatoryPrenom"
              name="signatoryPrenom"
              label="Prénom"
              placeholder="Ex: Sylvie"
              type="text"
              :rules="validationRules.required"
              @validation="res => handleValidation('signatoryPrenom', res)"
            />
            <ValidatedInput
              v-model="form.signatoryNom"
              name="signatoryNom"
              label="Nom"
              placeholder="Ex: MARTIN"
              type="text"
              :rules="validationRules.required"
              @validation="res => handleValidation('signatoryNom', res)"
            />
            <ValidatedInput
              v-model="form.signatoryTitle"
              name="signatoryTitle"
              label="Fonction / Titre"
              placeholder="Ex: Directrice RH"
              type="text"
              :rules="validationRules.required"
              @validation="res => handleValidation('signatoryTitle', res)"
            />
            <ValidatedInput
              v-model="form.signatoryEmail"
              name="signatoryEmail"
              label="E-mail"
              placeholder="Ex: s.martin@entreprise.com"
              type="text"
              :rules="[validationRules.required, validationRules.email]"
              @validation="res => handleValidation('signatoryEmail', res)"
            />
            <ValidatedInput
              v-model="form.signatoryPhone"
              name="signatoryPhone"
              label="Téléphone direct"
              placeholder="Ex: 01 02 03 04 05"
              type="text"
              :rules="[validationRules.required, validationRules.phone]"
              @validation="res => handleValidation('signatoryPhone', res)"
            />
          </div>
        </div>

        <!-- ─── Maître de stage (encadrant) ─── -->
        <div class="space-y-4">
          <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-2">
            <i class="pi pi-user-edit text-violet-500"></i> Maître de stage (encadrant en entreprise)
          </h4>

          <!-- Toggle "même personne" -->
          <label class="flex items-center gap-3 cursor-pointer select-none group w-fit">
            <div class="relative">
              <input type="checkbox" v-model="form.tuteurSameAsSignatory" @change="syncTuteurFromSignatory" class="sr-only peer" />
              <div class="w-10 h-5 bg-slate-200 dark:bg-slate-700 rounded-full peer-checked:bg-violet-600 transition-all"></div>
              <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full transition-all peer-checked:translate-x-5 shadow-sm"></div>
            </div>
            <span class="text-xs font-semibold text-slate-700 dark:text-slate-300 group-hover:text-violet-600 transition-colors">
              Le maître de stage est le même que le représentant légal
            </span>
          </label>

          <!-- Champs du tuteur (masqués si même personne) -->
          <div v-if="!form.tuteurSameAsSignatory" class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <ValidatedInput
              v-model="form.supervisorCivilite"
              name="supervisorCivilite"
              label="Civilité"
              type="select"
              :options="[{ label: 'Monsieur (M.)', value: 'M' }, { label: 'Madame (Mme)', value: 'Mme' }]"
              :rules="validationRules.required"
              @validation="res => handleValidation('supervisorCivilite', res)"
            />
            <ValidatedInput
              v-model="form.supervisorPrenom"
              name="supervisorPrenom"
              label="Prénom"
              placeholder="Ex: Robert"
              type="text"
              :rules="validationRules.required"
              @validation="res => handleValidation('supervisorPrenom', res)"
            />
            <ValidatedInput
              v-model="form.supervisorNom"
              name="supervisorNom"
              label="Nom"
              placeholder="Ex: LEGRAND"
              type="text"
              :rules="validationRules.required"
              @validation="res => handleValidation('supervisorNom', res)"
            />
            <ValidatedInput
              v-model="form.supervisorFunction"
              name="supervisorFunction"
              label="Fonction"
              placeholder="Ex: Chef de Projet"
              type="text"
              :rules="validationRules.required"
              @validation="res => handleValidation('supervisorFunction', res)"
            />
            <ValidatedInput
              v-model="form.supervisorEmail"
              name="supervisorEmail"
              label="E-mail"
              placeholder="Ex: r.legrand@entreprise.com"
              type="text"
              :rules="[validationRules.required, validationRules.email]"
              @validation="res => handleValidation('supervisorEmail', res)"
            />
            <ValidatedInput
              v-model="form.supervisorPhone"
              name="supervisorPhone"
              label="Téléphone"
              placeholder="Ex: 06 99 88 77 66"
              type="text"
              :rules="[validationRules.required, validationRules.phone]"
              @validation="res => handleValidation('supervisorPhone', res)"
            />
          </div>

          <!-- Récap quand même personne -->
          <div v-else class="flex items-center gap-3 p-3 rounded-xl bg-violet-50 dark:bg-violet-950/20 border border-violet-200 dark:border-violet-800/40">
            <i class="pi pi-info-circle text-violet-600"></i>
            <p class="text-xs text-violet-800 dark:text-violet-300">
              Les coordonnées du maître de stage seront identiques à celles du représentant légal :
              <span class="font-bold">{{ form.signatoryCivilite }} {{ form.signatoryPrenom }} {{ form.signatoryNom }}</span>
              <span v-if="form.signatoryTitle"> – {{ form.signatoryTitle }}</span>.
            </p>
          </div>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════
           ÉTAPE 3 : Dates & Mission
           ═══════════════════════════════════════════════════════════════════ -->
      <div v-if="currentStep === 3" class="space-y-6 animate-slide-in">
        <h3 class="text-base font-bold text-slate-800 dark:text-slate-100 border-b border-slate-50 dark:border-slate-700/40 pb-2">
          Étape 3 : Dates, Gratification &amp; Missions
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <ValidatedInput
            v-model="form.startDate"
            name="startDate"
            label="Date de début"
            type="date"
            :rules="validationRules.required"
            @validation="res => handleValidation('startDate', res)"
          />
          <ValidatedInput
            v-model="form.endDate"
            name="endDate"
            label="Date de fin"
            type="date"
            :rules="validationRules.required"
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
            @validation="res => handleValidation('weeklyHours', res)"
          />
          <ValidatedInput
            v-model="form.salaryAmount"
            name="salaryAmount"
            label="Gratification horaire nette (€/h)"
            type="number"
            :rules="[validationRules.required, validationRules.minValue(0)]"
            @validation="res => handleValidation('salaryAmount', res)"
          />
          <ValidatedInput
            v-model="form.subject"
            name="subject"
            label="Sujet de stage (mission principale)"
            placeholder="Ex: Développement d'une API de suivi…"
            type="text"
            :rules="validationRules.required"
            @validation="res => handleValidation('subject', res)"
            class="md:col-span-2"
          />
          <ValidatedInput
            v-model="form.activities"
            name="activities"
            label="Détail des activités confiées"
            placeholder="Décrivez les tâches au quotidien…"
            type="textarea"
            :rules="validationRules.required"
            @validation="res => handleValidation('activities', res)"
            class="md:col-span-2"
          />
          <ValidatedInput
            v-model="form.amenagementStage"
            name="amenagementStage"
            label="Aménagements éventuels"
            placeholder="Ex: Télétravail 2 jours/semaine"
            type="text"
            class="md:col-span-2"
          />
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════
           ÉTAPE 4 : Récapitulatif
           ═══════════════════════════════════════════════════════════════════ -->
      <div v-if="currentStep === 4" class="space-y-6 animate-slide-in">
        <h3 class="text-base font-bold text-slate-800 dark:text-slate-100 border-b border-slate-50 dark:border-slate-700/40 pb-2">
          Étape 4 : Récapitulatif et envoi
        </h3>

        <div class="bg-slate-50 dark:bg-slate-900/60 border border-slate-100 dark:border-slate-800 rounded-2xl p-6 space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <!-- Période & contact -->
            <div>
              <span class="text-slate-400 block">Période du stage :</span>
              <span class="font-bold text-slate-800 dark:text-slate-200">{{ selectedPeriodLabel || '-' }}</span>
            </div>
            <div>
              <span class="text-slate-400 block">Téléphone / e-mail étudiant :</span>
              <span class="font-bold text-slate-800 dark:text-slate-200">{{ form.phone || '-' }} — {{ form.emailPerso || '-' }}</span>
            </div>
            <div>
              <span class="text-slate-400 block">Assurance RC :</span>
              <span class="font-bold text-slate-800 dark:text-slate-200">{{ form.insuranceCompany || '-' }} ({{ form.insurancePolicyNumber || 'n° non renseigné' }})</span>
            </div>

            <div class="md:col-span-2 border-t border-slate-200/50 dark:border-slate-700/40 pt-3 mt-1">
              <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-2">Entreprise</span>
            </div>
            <div class="md:col-span-2">
              <span class="text-slate-400 block">Raison sociale &amp; SIRET :</span>
              <span class="font-bold text-slate-800 dark:text-slate-200">{{ form.companyName || '-' }} <span class="font-normal">({{ form.companySiret || 'SIRET non renseigné' }})</span></span>
            </div>
            <div class="md:col-span-2">
              <span class="text-slate-400 block">Adresse :</span>
              <span class="font-bold text-slate-800 dark:text-slate-200">
                {{ form.companyAddress.adresse || '-' }}, {{ form.companyAddress.codePostal }} {{ form.companyAddress.ville }}
              </span>
            </div>
            <div>
              <span class="text-slate-400 block">Représentant légal :</span>
              <span class="font-bold text-slate-800 dark:text-slate-200">{{ form.signatoryCivilite }} {{ form.signatoryPrenom }} {{ form.signatoryNom || '-' }}</span>
              <span class="text-slate-500 block">{{ form.signatoryTitle }}</span>
            </div>
            <div>
              <span class="text-slate-400 block">Maître de stage :</span>
              <span class="font-bold text-slate-800 dark:text-slate-200">{{ supervisorDisplay }}</span>
              <span v-if="form.tuteurSameAsSignatory" class="text-[10px] text-violet-500">(idem représentant légal)</span>
            </div>

            <div class="md:col-span-2 border-t border-slate-200/50 dark:border-slate-700/40 pt-3 mt-1">
              <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-2">Mission</span>
            </div>
            <div>
              <span class="text-slate-400 block">Dates :</span>
              <span class="font-bold text-slate-800 dark:text-slate-200">
                {{ form.startDate ? new Date(form.startDate).toLocaleDateString('fr-FR') : '-' }}
                au {{ form.endDate   ? new Date(form.endDate).toLocaleDateString('fr-FR')   : '-' }}
              </span>
            </div>
            <div>
              <span class="text-slate-400 block">Gratification :</span>
              <span class="font-bold text-slate-800 dark:text-slate-200">{{ form.salaryAmount }} €/h — {{ form.weeklyHours }}h/semaine</span>
            </div>
            <div class="md:col-span-2">
              <span class="text-slate-400 block">Sujet :</span>
              <p class="font-bold text-slate-800 dark:text-slate-200 mt-1">{{ form.subject || '-' }}</p>
            </div>
            <div class="md:col-span-2">
              <span class="text-slate-400 block">Activités :</span>
              <p class="font-medium text-slate-700 dark:text-slate-300 mt-1 leading-relaxed whitespace-pre-line">{{ form.activities || '-' }}</p>
            </div>
          </div>
        </div>

        <!-- Déclaration sur l'honneur -->
        <div class="bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/40 rounded-2xl p-4 flex items-start gap-3">
          <i class="pi pi-exclamation-triangle text-amber-600 mt-0.5"></i>
          <div>
            <h5 class="text-xs font-bold text-amber-800 dark:text-amber-300">Déclaration sur l'honneur</h5>
            <p class="text-[10px] text-amber-600 dark:text-amber-400 mt-0.5">
              En soumettant cette demande, vous certifiez l'exactitude des informations relatives à l'entreprise d'accueil et à vos garanties d'assurance responsabilité civile. Des informations fausses retarderont l'édition et la signature de la convention.
            </p>
          </div>
        </div>
      </div>

      <!-- ── Boutons de navigation ──────────────────────────────────────── -->
      <div class="flex items-center justify-between border-t border-slate-50 dark:border-slate-700/40 pt-6 mt-8">
        <button
          v-if="currentStep > 1"
          @click="prevStep"
          class="text-xs font-bold px-5 py-3 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all flex items-center gap-2"
        >
          <i class="pi pi-arrow-left text-[10px]"></i>
          <span>Précédent</span>
        </button>
        <div v-else></div>

        <div>
          <button
            v-if="currentStep < totalSteps"
            @click="nextStep"
            :disabled="stepHasErrors"
            class="text-xs font-bold px-6 py-3 rounded-xl bg-violet-600 hover:bg-violet-700 disabled:opacity-50 text-white transition-all flex items-center gap-2"
          >
            <span>Suivant</span>
            <i class="pi pi-arrow-right text-[10px]"></i>
          </button>
          <button
            v-else
            @click="submitRequest"
            :disabled="isSubmitting || stepHasErrors"
            class="text-xs font-bold px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white transition-all flex items-center gap-2"
          >
            <i v-if="isSubmitting" class="pi pi-spin pi-spinner"></i>
            <i v-else class="pi pi-check"></i>
            <span>{{ isSubmitting ? 'Soumission…' : 'Soumettre ma demande' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.input-field {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid rgb(226 232 240);
  border-radius: 0.75rem;
  background-color: rgb(248 250 252);
  font-size: 0.75rem;
  color: rgb(30 41 59);
  transition: all 0.2s;
}
.input-field:focus {
  outline: none;
  box-shadow: 0 0 0 2px rgb(139 92 246);
}
@media (prefers-color-scheme: dark) {
  .input-field {
    border-color: rgb(51 65 85);
    background-color: rgb(15 23 42);
    color: rgb(226 232 240);
  }
}


.animate-slide-in {
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from { opacity: 0; transform: translateX(10px); }
  to   { opacity: 1; transform: translateX(0); }
}
</style>
