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
  return [{ label: 'Non défini / Référent qualité par défaut', value: '' }, ...list];
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
    console.error('Erreur chargement configuration questionnaire:', error);
    hasError.value = true;
    errorMessage.value = error?.message || 'Impossible de charger la configuration des questionnaires.';
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
      title="Configuration — Questionnaires & Enquêtes"
      subtitle="Gestion de l'anonymat, des règles de publication des résultats et du calendrier des évaluations"
      icon="pi pi-check-square"
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
          color="green"
          description="Périmètre d'évaluation rattaché"
        />
        <Kpi
          label="Anonymat par défaut"
          :value="deptOpt.questionnaire_anonymat_default !== false ? 'Activé' : 'Désactivé'"
          icon="pi pi-user-minus"
          :color="deptOpt.questionnaire_anonymat_default !== false ? 'green' : 'amber'"
          description="Protection de l'identité des répondants"
        />
        <Kpi
          label="Durée par défaut"
          :value="`${deptOpt.questionnaire_duree_default_jours ?? 14} j`"
          icon="pi pi-calendar"
          color="blue"
          description="Période d'ouverture des enquêtes"
        />
      </div>

      <!-- Card 1: Politique & Déroulement des Enquêtes -->
      <Card
        title="Politique des Enquêtes & Répondants"
        subtitle="Confidentialité et règles de saisie applicables aux sondages"
        icon="pi pi-shield"
        color="green"
      >
        <div class="flex flex-col divide-y divide-slate-100 dark:divide-slate-800/60">
          <ConfigOptionItem
            title="Anonymat systématique par défaut"
            description="Masque l'identité des étudiants sur l'ensemble des réponses textuelles et notes attribuées."
            icon="pi pi-lock"
            icon-color-class="text-green-600"
            icon-bg-class="bg-green-500/10"
            type="switch"
            :model-value="deptOpt.questionnaire_anonymat_default ?? true"
            :loading="loadingKeys['questionnaire_anonymat_default']"
            @change="(val) => updateOption('questionnaire_anonymat_default', val)"
          />

          <ConfigOptionItem
            title="Modification des réponses autorisée avant clôture"
            description="Permet aux étudiants de revenir sur leur formulaire tant que la campagne d'évaluation est ouverte."
            icon="pi pi-pencil"
            icon-color-class="text-blue-500"
            icon-bg-class="bg-blue-500/10"
            type="switch"
            :model-value="deptOpt.questionnaire_modif_reponse ?? true"
            :loading="loadingKeys['questionnaire_modif_reponse']"
            @change="(val) => updateOption('questionnaire_modif_reponse', val)"
          />

          <ConfigOptionItem
            title="Durée standard de mise à disposition des formulaires"
            description="Nombre de jours accordés par défaut aux promotions pour répondre aux enquêtes pédagogiques."
            icon="pi pi-hourglass"
            icon-color-class="text-purple-500"
            icon-bg-class="bg-purple-500/10"
            type="number"
            :model-value="deptOpt.questionnaire_duree_default_jours ?? 14"
            :min="3"
            :max="60"
            suffix="jour(s)"
            :loading="loadingKeys['questionnaire_duree_default_jours']"
            @change="(val) => updateOption('questionnaire_duree_default_jours', Number(val))"
          />

          <ConfigOptionItem
            title="Clôture automatique à la date d'échéance"
            description="Ferme automatiquement les enquêtes passée l'heure limite sans intervention manuelle du secrétariat."
            icon="pi pi-calendar-times"
            icon-color-class="text-amber-500"
            icon-bg-class="bg-amber-500/10"
            type="switch"
            :model-value="deptOpt.questionnaire_cloture_auto ?? true"
            :loading="loadingKeys['questionnaire_cloture_auto']"
            @change="(val) => updateOption('questionnaire_cloture_auto', val)"
          />
        </div>
      </Card>

      <!-- Card 2: Restitution & Qualité -->
      <Card
        title="Restitution des Résultats & Qualité"
        subtitle="Règles d'exploitation des bilans et seuils statistiques"
        icon="pi pi-chart-line"
        color="teal"
      >
        <div class="flex flex-col divide-y divide-slate-100 dark:divide-slate-800/60">
          <ConfigOptionItem
            title="Référent Qualité & Évaluations du département"
            description="Enseignant ou personnel responsable de la campagne annuelle d'évaluation des enseignements."
            icon="pi pi-user"
            icon-color-class="text-teal-600"
            icon-bg-class="bg-teal-500/10"
            type="select"
            :model-value="deptOpt.questionnaire_responsable_qualite ? String(deptOpt.questionnaire_responsable_qualite) : ''"
            :options="personnelOptions"
            :loading="loadingKeys['questionnaire_responsable_qualite']"
            @change="(val) => updateOption('questionnaire_responsable_qualite', val)"
          />

          <ConfigOptionItem
            title="Seuil minimum de répondants pour publication des synthèses"
            description="Nombre minimal de questionnaires validés nécessaire pour rendre le rapport d'évaluation consultable (évite l'identification)."
            icon="pi pi-users"
            icon-color-class="text-indigo-500"
            icon-bg-class="bg-indigo-500/10"
            type="number"
            :model-value="deptOpt.questionnaire_seuil_min_reponses ?? 5"
            :min="1"
            :max="25"
            suffix="réponse(s)"
            :loading="loadingKeys['questionnaire_seuil_min_reponses']"
            @change="(val) => updateOption('questionnaire_seuil_min_reponses', Number(val))"
          />

          <ConfigOptionItem
            title="Partage automatique des rapports aux intervenants"
            description="Transmet automatiquement le bilan pédagogique agrégé aux enseignants concernés dès la clôture officielle."
            icon="pi pi-share-alt"
            icon-color-class="text-emerald-500"
            icon-bg-class="bg-emerald-500/10"
            type="switch"
            :model-value="deptOpt.questionnaire_partage_auto_synthese ?? true"
            :loading="loadingKeys['questionnaire_partage_auto_synthese']"
            @change="(val) => updateOption('questionnaire_partage_auto_synthese', val)"
          />
        </div>
      </Card>
    </div>
  </div>
</template>

<style scoped>
</style>

