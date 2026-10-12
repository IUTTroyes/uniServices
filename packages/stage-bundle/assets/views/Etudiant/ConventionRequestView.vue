<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { getStagePeriodesService } from '../../requests/stage_service';
import { useUsersStore } from '@stores';
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
    <div class="flex items-center gap-4 justify-between border-b border-slate-200 dark:border-slate-800 pb-5">
      <div>
        <h1 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2">
          <i class="pi pi-file-edit text-teal-600"></i>
          <span>Demande de Convention de Stage</span>
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
          Remplissez les informations nécessaires pour la validation et l'édition de votre convention de stage.
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
          title="Coordonnées de l'étudiant & Assurances"
          description="Vérifiez vos coordonnées personnelles et précisez votre assurance responsabilité civile obligatoire."
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
          description="Coordonnées et adresse de la structure qui vous accueille pour votre stage."
        >
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
            <div class="md:col-span-2">
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
        </FormSection>

        <!-- Représentant légal (signataire) -->
        <FormSection
          :icon="UserIcon"
          tone="indigo"
          title="Représentant légal (signataire de la convention)"
          description="Personne habilitée à signer les conventions au nom de l'entreprise (Directeur, DRH...)."
        >
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
        </FormSection>

        <!-- Maître de stage (encadrant) -->
        <FormSection
          :icon="UserIcon"
          tone="purple"
          title="Maître de stage (encadrant en entreprise)"
          description="Votre responsable opérationnel au quotidien au sein de l'entreprise."
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
                <span class="text-slate-400 block font-medium">Assurance RC :</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">{{ form.insuranceCompany || '-' }} ({{ form.insurancePolicyNumber || 'n° non renseigné' }})</span>
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
                  {{ form.startDate ? new Date(form.startDate).toLocaleDateString('fr-FR') : '-' }}
                  au {{ form.endDate ? new Date(form.endDate).toLocaleDateString('fr-FR') : '-' }}
                </span>
              </div>
              <div>
                <span class="text-slate-400 block font-medium">Gratification :</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">{{ form.salaryAmount }} €/h — {{ form.weeklyHours }}h/semaine</span>
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
                En soumettant cette demande, vous certifiez l'exactitude des informations relatives à l'entreprise d'accueil et à vos garanties d'assurance responsabilité civile. Des informations inexactes retarderont l'édition et la signature de la convention.
              </p>
            </div>
          </div>
        </FormSection>
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
            @click="nextStep"
            :disabled="stepHasErrors"
            class="text-xs font-bold px-5 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 disabled:opacity-50 text-white transition-all flex items-center gap-2 cursor-pointer shadow-xs"
          >
            <span>Suivant</span>
            <i class="pi pi-arrow-right text-[10px]"></i>
          </button>
          <button
            v-else
            @click="submitRequest"
            :disabled="isSubmitting || stepHasErrors"
            class="text-xs font-bold px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white transition-all flex items-center gap-2 cursor-pointer shadow-xs"
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
.animate-slide-in {
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from { opacity: 0; transform: translateX(10px); }
  to   { opacity: 1; transform: translateX(0); }
}
</style>

