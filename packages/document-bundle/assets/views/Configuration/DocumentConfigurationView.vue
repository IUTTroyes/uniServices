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
  return [{ label: 'Non défini / Secrétariat', value: '' }, ...list];
});

const visibiliteOptions = [
  { label: 'Département entier (Personnels & Étudiants)', value: 'tous' },
  { label: 'Personnels uniquement (Enseignants & BIATSS)', value: 'personnels' },
  { label: 'Étudiants uniquement', value: 'etudiants' },
  { label: 'Privé (créateur seul)', value: 'prive' }
];

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
    console.error('Erreur chargement configuration documents:', error);
    hasError.value = true;
    errorMessage.value = error?.message || 'Impossible de charger la configuration des documents.';
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
      title="Configuration — Espace Documentaire"
      subtitle="Gestion des quotas, des règles de partage et des autorisations de téléversement"
      icon="pi pi-folder"
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
          color="blue"
          description="Espace documentaire dédié"
        />
        <Kpi
          label="Taille max. fichier"
          :value="`${deptOpt.document_max_file_size_mb ?? 25} Mo`"
          icon="pi pi-file"
          color="purple"
          description="Plafond par document téléversé"
        />
        <Kpi
          label="Rétention corbeille"
          :value="`${deptOpt.document_retention_corbeille_jours ?? 30} j`"
          icon="pi pi-trash"
          color="amber"
          description="Délai avant purge définitive"
        />
      </div>

      <!-- Card 1: Stockage & Quotas -->
      <Card
        title="Stockage & Quotas de Fichiers"
        subtitle="Règles d'envoi et volumétrie des pièces jointes"
        icon="pi pi-database"
        color="blue"
      >
        <div class="flex flex-col divide-y divide-slate-100 dark:divide-slate-800/60">
          <ConfigOptionItem
            title="Taille maximale autorisée par fichier"
            description="Plafond technique pour chaque document déposé sur la plateforme."
            icon="pi pi-file"
            icon-color-class="text-blue-500"
            icon-bg-class="bg-blue-500/10"
            type="number"
            :model-value="deptOpt.document_max_file_size_mb ?? 25"
            :min="2"
            :max="100"
            suffix="Mo"
            :loading="loadingKeys['document_max_file_size_mb']"
            @change="(val) => updateOption('document_max_file_size_mb', Number(val))"
          />

          <ConfigOptionItem
            title="Dépôt de documents par les étudiants"
            description="Autorise les étudiants à ajouter des fichiers dans les répertoires partagés prévus à cet effet."
            icon="pi pi-upload"
            icon-color-class="text-teal-600"
            icon-bg-class="bg-teal-500/10"
            type="switch"
            :model-value="deptOpt.document_upload_etudiant ?? false"
            :loading="loadingKeys['document_upload_etudiant']"
            @change="(val) => updateOption('document_upload_etudiant', val)"
          />

          <ConfigOptionItem
            title="Filtrage strict des extensions de fichiers"
            description="Bloque les formats exécutables ou archives non signées (autorise PDF, Office, images et textes)."
            icon="pi pi-shield"
            icon-color-class="text-emerald-500"
            icon-bg-class="bg-emerald-500/10"
            type="switch"
            :model-value="deptOpt.document_strict_formats ?? true"
            :loading="loadingKeys['document_strict_formats']"
            @change="(val) => updateOption('document_strict_formats', val)"
          />

          <ConfigOptionItem
            title="Durée de conservation des fichiers supprimés (Corbeille)"
            description="Nombre de jours avant l'effacement définitif et irréversible des documents placés en corbeille."
            icon="pi pi-trash"
            icon-color-class="text-amber-500"
            icon-bg-class="bg-amber-500/10"
            type="number"
            :model-value="deptOpt.document_retention_corbeille_jours ?? 30"
            :min="7"
            :max="180"
            suffix="jour(s)"
            :loading="loadingKeys['document_retention_corbeille_jours']"
            @change="(val) => updateOption('document_retention_corbeille_jours', Number(val))"
          />
        </div>
      </Card>

      <!-- Card 2: Visibilité & Diffusion -->
      <Card
        title="Partage & Droits de Consultation"
        subtitle="Paramètres par défaut lors de la création d'un document ou d'un dossier"
        icon="pi pi-share-alt"
        color="purple"
      >
        <div class="flex flex-col divide-y divide-slate-100 dark:divide-slate-800/60">
          <ConfigOptionItem
            title="Visibilité par défaut des nouveaux documents"
            description="Audience présélectionnée lors du dépôt d'un nouveau document."
            icon="pi pi-eye"
            icon-color-class="text-purple-500"
            icon-bg-class="bg-purple-500/10"
            type="select"
            :model-value="deptOpt.document_visibilite_default || 'tous'"
            :options="visibiliteOptions"
            :loading="loadingKeys['document_visibilite_default']"
            @change="(val) => updateOption('document_visibilite_default', val)"
          />

          <ConfigOptionItem
            title="Référent GED / Documentation du département"
            description="Personnel désigné pour l'arborescence et l'archivage documentaire."
            icon="pi pi-user"
            icon-color-class="text-indigo-500"
            icon-bg-class="bg-indigo-500/10"
            type="select"
            :model-value="deptOpt.document_responsable ? String(deptOpt.document_responsable) : ''"
            :options="personnelOptions"
            :loading="loadingKeys['document_responsable']"
            @change="(val) => updateOption('document_responsable', val)"
          />

          <ConfigOptionItem
            title="Notification par mail lors du dépôt d'un document officiel"
            description="Avertit les étudiants ou collègues lorsqu'un document marqué comme officiel ou urgent est mis en ligne."
            icon="pi pi-bell"
            icon-color-class="text-blue-500"
            icon-bg-class="bg-blue-500/10"
            type="switch"
            :model-value="deptOpt.document_mail_nouveau_doc ?? false"
            :loading="loadingKeys['document_mail_nouveau_doc']"
            @change="(val) => updateOption('document_mail_nouveau_doc', val)"
          />
        </div>
      </Card>
    </div>
  </div>
</template>

<style scoped>
</style>

