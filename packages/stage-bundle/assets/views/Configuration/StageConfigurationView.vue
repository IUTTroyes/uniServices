<script setup>
import { ref, onMounted, computed } from 'vue';
import { HeaderComponent, Kpi, Card, ConfigOptionItem, GlobalLoader, ErrorView } from '@components';
import { useUsersStore } from '@stores';
import { getDepartementService, updateDepartementService, getPersonnelsService } from '@requests';
import { useToast } from 'primevue/usetoast';

const userStore = useUsersStore();
const toast = useToast();

const isLoading = ref(true);
const hasError = ref(false);
const errorMessage = ref('');
const departement = ref(null);
const personnels = ref([]);
const loadingKeys = ref({});

const currentDeptId = computed(() => userStore.departementDefaut?.id);
const deptOpt = computed(() => departement.value?.opt || {});

const personnelOptions = computed(() => {
  const list = (personnels.value || []).map(p => ({
    label: `${p.nom || ''} ${p.prenom || ''}`.trim() || p.display || `Personnel #${p.id}`,
    value: String(p.id)
  }));
  return [{ label: 'Non assigné / Responsable par défaut', value: '' }, ...list];
});

const loadData = async () => {
  if (!currentDeptId.value) {
    isLoading.value = false;
    return;
  }

  isLoading.value = true;
  hasError.value = false;

  try {
    const [deptData, personnelsData] = await Promise.all([
      getDepartementService(currentDeptId.value),
      getPersonnelsService({ departement: currentDeptId.value, pagination: false }).catch(() => [])
    ]);

    departement.value = deptData;
    personnels.value = personnelsData || [];
  } catch (error) {
    console.error('Erreur chargement configuration stage:', error);
    hasError.value = true;
    errorMessage.value = error?.message || 'Impossible de charger la configuration des stages.';
  } finally {
    isLoading.value = false;
  }
};

const updateOption = async (key, value) => {
  if (!departement.value?.id) return;
  loadingKeys.value[key] = true;

  try {
    const currentOpt = { ...deptOpt.value, [key]: value };

    await updateDepartementService(departement.value.id, {
      opt: currentOpt
    }, false);

    if (departement.value) {
      if (!departement.value.opt) departement.value.opt = {};
      departement.value.opt = { ...departement.value.opt, [key]: value };
    }

    toast.add({
      severity: 'success',
      summary: 'Configuration enregistrée',
      detail: `L'option a été mise à jour avec succès.`,
      life: 2500
    });
  } catch (error) {
    console.error(`Erreur mise à jour option ${key}:`, error);
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de sauvegarder l\'option.',
      life: 3500
    });
  } finally {
    loadingKeys.value[key] = false;
  }
};

onMounted(() => {
  loadData();
});
</script>

<template>
  <div class="space-y-6">
    <HeaderComponent
      title="Configuration — Stages & Alternance"
      subtitle="Règles de gestion des conventions, des tuteurs pédagogiques et des critères de validation"
      icon="pi pi-briefcase"
    />

    <GlobalLoader v-if="isLoading" />
    <ErrorView v-else-if="hasError" :message="errorMessage" />

    <div v-else class="space-y-6">
      <!-- Cartes KPI supérieures -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Kpi
          label="Département"
          :value="departement?.libelle || 'N/A'"
          icon="pi pi-building"
          color="teal"
          description="Département pédagogique rattaché"
        />
        <Kpi
          label="Dépôt en ligne"
          :value="deptOpt.stage_convention_online !== false ? 'Actif' : 'Désactivé'"
          icon="pi pi-file-edit"
          :color="deptOpt.stage_convention_online !== false ? 'green' : 'amber'"
          description="Saisie des conventions par les étudiants"
        />
        <Kpi
          label="Délai min. dépôt"
          :value="`${deptOpt.stage_delai_depot ?? 15} j`"
          icon="pi pi-hourglass"
          color="blue"
          description="Délai avant le premier jour de stage"
        />
      </div>

      <!-- Card 1: Conventions & Dépôt -->
      <Card
        title="Gestion des Conventions de Stage"
        subtitle="Conditions de création et de validation administrative des conventions"
        icon="pi pi-file-check"
        color="teal"
      >
        <div class="flex flex-col divide-y divide-slate-100 dark:divide-slate-800/60">
          <ConfigOptionItem
            title="Saisie en ligne des conventions par les étudiants"
            description="Permet aux étudiants de soumettre directement leur demande de convention de stage depuis leur portail."
            icon="pi pi-cloud-upload"
            icon-color-class="text-teal-600"
            icon-bg-class="bg-teal-500/10"
            type="switch"
            :model-value="deptOpt.stage_convention_online ?? true"
            :loading="loadingKeys['stage_convention_online']"
            @change="(val) => updateOption('stage_convention_online', val)"
          />

          <ConfigOptionItem
            title="Délai minimum de dépôt avant le début du stage"
            description="Nombre de jours requis entre la soumission de la convention et la date de démarrage (ex: 15 jours pour signature)."
            icon="pi pi-calendar-times"
            icon-color-class="text-blue-500"
            icon-bg-class="bg-blue-500/10"
            type="number"
            :model-value="deptOpt.stage_delai_depot ?? 15"
            :min="1"
            :max="60"
            suffix="jour(s)"
            :loading="loadingKeys['stage_delai_depot']"
            @change="(val) => updateOption('stage_delai_depot', Number(val))"
          />

          <ConfigOptionItem
            title="Durée maximale de stage par année universitaire"
            description="Plafond légal ou pédagogique autorisé en nombre de semaines de stage par étudiant."
            icon="pi pi-clock"
            icon-color-class="text-purple-500"
            icon-bg-class="bg-purple-500/10"
            type="number"
            :model-value="deptOpt.stage_duree_max_semaines ?? 26"
            :min="4"
            :max="52"
            suffix="semaine(s)"
            :loading="loadingKeys['stage_duree_max_semaines']"
            @change="(val) => updateOption('stage_duree_max_semaines', Number(val))"
          />

          <ConfigOptionItem
            title="Vérification automatisée de l'entreprise (API SIRET)"
            description="Contrôle l'existence juridique et l'adresse de l'entreprise dès la saisie du numéro de SIRET."
            icon="pi pi-check-circle"
            icon-color-class="text-emerald-500"
            icon-bg-class="bg-emerald-500/10"
            type="switch"
            :model-value="deptOpt.stage_siret_auto ?? true"
            :loading="loadingKeys['stage_siret_auto']"
            @change="(val) => updateOption('stage_siret_auto', val)"
          />
        </div>
      </Card>

      <!-- Card 2: Encadrement & Tuteurs -->
      <Card
        title="Encadrement & Tuteurs Enseignants"
        subtitle="Attribution des tuteurs universitaires et suivi pédagogique"
        icon="pi pi-users"
        color="blue"
      >
        <div class="flex flex-col divide-y divide-slate-100 dark:divide-slate-800/60">
          <ConfigOptionItem
            title="Responsable principal des stages"
            description="Enseignant ou personnel référent pour la coordination globale des stages du département."
            icon="pi pi-user"
            icon-color-class="text-teal-600"
            icon-bg-class="bg-teal-500/10"
            type="select"
            :model-value="deptOpt.stage_responsable ? String(deptOpt.stage_responsable) : ''"
            :options="personnelOptions"
            :loading="loadingKeys['stage_responsable']"
            @change="(val) => updateOption('stage_responsable', val)"
          />

          <ConfigOptionItem
            title="Attribution automatique des tuteurs"
            description="Associe automatiquement un tuteur enseignant aux nouvelles conventions validées selon la charge de service."
            icon="pi pi-user-plus"
            icon-color-class="text-indigo-500"
            icon-bg-class="bg-indigo-500/10"
            type="switch"
            :model-value="deptOpt.stage_tuteur_auto ?? false"
            :loading="loadingKeys['stage_tuteur_auto']"
            @change="(val) => updateOption('stage_tuteur_auto', val)"
          />

          <ConfigOptionItem
            title="Capacité maximale par tuteur enseignant"
            description="Nombre maximal de stagiaires ou apprentis pouvant être suivis par un même enseignant."
            icon="pi pi-id-card"
            icon-color-class="text-amber-500"
            icon-bg-class="bg-amber-500/10"
            type="number"
            :model-value="deptOpt.stage_max_stagiaires_tuteur ?? 8"
            :min="1"
            :max="30"
            suffix="étudiant(s)"
            :loading="loadingKeys['stage_max_stagiaires_tuteur']"
            @change="(val) => updateOption('stage_max_stagiaires_tuteur', Number(val))"
          />

          <ConfigOptionItem
            title="Visite en entreprise obligatoire"
            description="Exige la réalisation d'une visite physique ou d'un entretien téléphonique formel par le tuteur enseignant."
            icon="pi pi-map-marker"
            icon-color-class="text-rose-500"
            icon-bg-class="bg-rose-500/10"
            type="switch"
            :model-value="deptOpt.stage_visite_obligatoire ?? true"
            :loading="loadingKeys['stage_visite_obligatoire']"
            @change="(val) => updateOption('stage_visite_obligatoire', val)"
          />

          <ConfigOptionItem
            title="Relances automatiques pour les fiches d'évaluation"
            description="Envoie des rappels automatiques par courriel aux maîtres de stage pour le remplissage de la grille finale."
            icon="pi pi-bell"
            icon-color-class="text-orange-500"
            icon-bg-class="bg-orange-500/10"
            type="switch"
            :model-value="deptOpt.stage_relance_eval_auto ?? true"
            :loading="loadingKeys['stage_relance_eval_auto']"
            @change="(val) => updateOption('stage_relance_eval_auto', val)"
          />
        </div>
      </Card>
    </div>
  </div>
</template>

<style scoped>
</style>

