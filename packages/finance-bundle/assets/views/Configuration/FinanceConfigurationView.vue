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
  return [{ label: 'Non défini / Chef de département', value: '' }, ...list];
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
    console.error('Erreur chargement configuration finance:', error);
    hasError.value = true;
    errorMessage.value = error?.message || 'Impossible de charger la configuration financière.';
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
      title="Configuration — Finances & Achats"
      subtitle="Gestion des plafonds budgétaires, circuits d'approbation et référents financiers"
      icon="pi pi-wallet"
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
          color="emerald"
          description="Centre de coûts rattaché"
        />
        <Kpi
          label="Seuil achat direct"
          :value="`${deptOpt.finance_seuil_achat_direct ?? 1500} €`"
          icon="pi pi-tag"
          color="green"
          description="Montant maximum sans mise en concurrence"
        />
        <Kpi
          label="Double validation"
          :value="`${deptOpt.finance_seuil_double_validation ?? 4000} €`"
          icon="pi pi-shield"
          color="blue"
          description="Seuil de visa obligatoire direction"
        />
      </div>

      <!-- Card 1: Seuils & Circuit de Validation -->
      <Card
        title="Seuils d'Achat & Approbations"
        subtitle="Règles financières appliquées aux demandes d'achat et commandes"
        icon="pi pi-sliders-h"
        color="emerald"
      >
        <div class="flex flex-col divide-y divide-slate-100 dark:divide-slate-800/60">
          <ConfigOptionItem
            title="Plafond pour achat direct sans mise en concurrence"
            description="Montant HT maximum en deçà duquel un seul devis fournisseur est suffisant."
            icon="pi pi-tag"
            icon-color-class="text-emerald-500"
            icon-bg-class="bg-emerald-500/10"
            type="number"
            :model-value="deptOpt.finance_seuil_achat_direct ?? 1500"
            :min="100"
            :max="10000"
            :step="50"
            suffix="€ HT"
            :loading="loadingKeys['finance_seuil_achat_direct']"
            @change="(val) => updateOption('finance_seuil_achat_direct', Number(val))"
          />

          <ConfigOptionItem
            title="Seuil de validation automatique pour menues dépenses"
            description="Montant TTC sous lequel la validation du chef de département est automatique (ex: papeterie d'urgence)."
            icon="pi pi-bolt"
            icon-color-class="text-amber-500"
            icon-bg-class="bg-amber-500/10"
            type="number"
            :model-value="deptOpt.finance_seuil_auto_validation ?? 100"
            :min="0"
            :max="500"
            :step="10"
            suffix="€ TTC"
            :loading="loadingKeys['finance_seuil_auto_validation']"
            @change="(val) => updateOption('finance_seuil_auto_validation', Number(val))"
          />

          <ConfigOptionItem
            title="Exiger systématiquement 3 devis comparatifs au-delà du seuil"
            description="Bloque la transmission au service financier si moins de 3 offres concurrentes sont attachées."
            icon="pi pi-copy"
            icon-color-class="text-blue-500"
            icon-bg-class="bg-blue-500/10"
            type="switch"
            :model-value="deptOpt.finance_exiger_devis_comparatif ?? true"
            :loading="loadingKeys['finance_exiger_devis_comparatif']"
            @change="(val) => updateOption('finance_exiger_devis_comparatif', val)"
          />

          <ConfigOptionItem
            title="Seuil de double validation requise"
            description="Montant nécessitant le visa conjoint du chef de département et du responsable financier."
            icon="pi pi-shield"
            icon-color-class="text-rose-500"
            icon-bg-class="bg-rose-500/10"
            type="number"
            :model-value="deptOpt.finance_seuil_double_validation ?? 4000"
            :min="1000"
            :max="50000"
            :step="500"
            suffix="€ HT"
            :loading="loadingKeys['finance_seuil_double_validation']"
            @change="(val) => updateOption('finance_seuil_double_validation', Number(val))"
          />
        </div>
      </Card>

      <!-- Card 2: Responsables & Notifications -->
      <Card
        title="Responsabilités & Alertes Mails"
        subtitle="Gestionnaires des lignes de crédits et notifications automatiques"
        icon="pi pi-bell"
        color="teal"
      >
        <div class="flex flex-col divide-y divide-slate-100 dark:divide-slate-800/60">
          <ConfigOptionItem
            title="Référent financier du département"
            description="Personnel désigné comme premier valideur des engagements budgétaires du département."
            icon="pi pi-user"
            icon-color-class="text-emerald-500"
            icon-bg-class="bg-emerald-500/10"
            type="select"
            :model-value="deptOpt.finance_responsable_budget ? String(deptOpt.finance_responsable_budget) : ''"
            :options="personnelOptions"
            :loading="loadingKeys['finance_responsable_budget']"
            @change="(val) => updateOption('finance_responsable_budget', val)"
          />

          <ConfigOptionItem
            title="Alerte mail lors d'une nouvelle demande d'achat"
            description="Notifie par courrier électronique le responsable de ligne dès qu'un agent soumet un besoin."
            icon="pi pi-envelope"
            icon-color-class="text-blue-500"
            icon-bg-class="bg-blue-500/10"
            type="switch"
            :model-value="deptOpt.finance_mail_nouvelle_demande ?? true"
            :loading="loadingKeys['finance_mail_nouvelle_demande']"
            @change="(val) => updateOption('finance_mail_nouvelle_demande', val)"
          />

          <ConfigOptionItem
            title="Alerte de dépassement du budget prévisionnel"
            description="Avertit le chef de département lorsque les engagements atteignent 90% du budget alloué."
            icon="pi pi-exclamation-triangle"
            icon-color-class="text-amber-500"
            icon-bg-class="bg-amber-500/10"
            type="switch"
            :model-value="deptOpt.finance_alerte_depassement_budget ?? true"
            :loading="loadingKeys['finance_alerte_depassement_budget']"
            @change="(val) => updateOption('finance_alerte_depassement_budget', val)"
          />
        </div>
      </Card>
    </div>
  </div>
</template>

<style scoped>
</style>

