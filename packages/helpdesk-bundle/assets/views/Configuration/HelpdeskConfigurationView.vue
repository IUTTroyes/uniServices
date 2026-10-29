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
  return [{ label: 'Non défini / Équipe générale', value: '' }, ...list];
});

const prioriteOptions = [
  { label: 'Faible (information, demande mineure)', value: 'faible' },
  { label: 'Normale (fonctionnement courant)', value: 'normale' },
  { label: 'Haute (gêne pédagogique importante)', value: 'haute' },
  { label: 'Urgente (panne bloquante, examen)', value: 'urgente' }
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
    console.error('Erreur chargement configuration helpdesk:', error);
    hasError.value = true;
    errorMessage.value = error?.message || 'Impossible de charger la configuration de l\'assistance.';
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
      title="Configuration — Assistance & Support"
      subtitle="Gestion des délais de réponse, règles d'escalade et référents techniques"
      icon="pi pi-question-circle"
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
          color="orange"
          description="Service helpdesk rattaché"
        />
        <Kpi
          label="Alerte sans réponse"
          :value="`${deptOpt.helpdesk_delai_alerte_heures ?? 48} h`"
          icon="pi pi-clock"
          color="amber"
          description="Délai max. avant relance de l'agent"
        />
        <Kpi
          label="Fermeture auto"
          :value="`${deptOpt.helpdesk_fermeture_auto_jours ?? 7} j`"
          icon="pi pi-check-circle"
          color="green"
          description="Délai de clôture après résolution"
        />
      </div>

      <!-- Card 1: Délais & Règles de Traitement -->
      <Card
        title="Traitement & Délais des Demandes"
        subtitle="Automatisation des rappels et cycles de vie des tickets"
        icon="pi pi-clock"
        color="orange"
      >
        <div class="flex flex-col divide-y divide-slate-100 dark:divide-slate-800/60">
          <ConfigOptionItem
            title="Délai d'alerte pour ticket sans prise en charge"
            description="Avertit le responsable lorsqu'un nouveau ticket n'a reçu aucune attribution ni réponse dans le temps imparti."
            icon="pi pi-hourglass"
            icon-color-class="text-amber-500"
            icon-bg-class="bg-amber-500/10"
            type="number"
            :model-value="deptOpt.helpdesk_delai_alerte_heures ?? 48"
            :min="4"
            :max="168"
            :step="4"
            suffix="heure(s)"
            :loading="loadingKeys['helpdesk_delai_alerte_heures']"
            @change="(val) => updateOption('helpdesk_delai_alerte_heures', Number(val))"
          />

          <ConfigOptionItem
            title="Délai de fermeture automatique des tickets résolus"
            description="Nombre de jours avant clôture définitive d'un ticket résolu sans nouvelle réponse de l'usager."
            icon="pi pi-check-circle"
            icon-color-class="text-green-500"
            icon-bg-class="bg-green-500/10"
            type="number"
            :model-value="deptOpt.helpdesk_fermeture_auto_jours ?? 7"
            :min="1"
            :max="30"
            suffix="jour(s)"
            :loading="loadingKeys['helpdesk_fermeture_auto_jours']"
            @change="(val) => updateOption('helpdesk_fermeture_auto_jours', Number(val))"
          />

          <ConfigOptionItem
            title="Niveau de priorité assigné par défaut"
            description="Priorité appliquée aux nouvelles demandes lorsque le demandeur ne la spécifie pas."
            icon="pi pi-flag"
            icon-color-class="text-rose-500"
            icon-bg-class="bg-rose-500/10"
            type="select"
            :model-value="deptOpt.helpdesk_priorite_default || 'normale'"
            :options="prioriteOptions"
            :loading="loadingKeys['helpdesk_priorite_default']"
            @change="(val) => updateOption('helpdesk_priorite_default', val)"
          />

          <ConfigOptionItem
            title="Accès au helpdesk ouvert aux étudiants"
            description="Autorise les étudiants à ouvrir des tickets d'assistance technique ou pédagogique pour le département."
            icon="pi pi-user"
            icon-color-class="text-blue-500"
            icon-bg-class="bg-blue-500/10"
            type="switch"
            :model-value="deptOpt.helpdesk_acces_etudiants ?? true"
            :loading="loadingKeys['helpdesk_acces_etudiants']"
            @change="(val) => updateOption('helpdesk_acces_etudiants', val)"
          />
        </div>
      </Card>

      <!-- Card 2: Équipe & Notifications -->
      <Card
        title="Référent & Notifications"
        subtitle="Alertes et visibilité des demandes d'assistance"
        icon="pi pi-users"
        color="amber"
      >
        <div class="flex flex-col divide-y divide-slate-100 dark:divide-slate-800/60">
          <ConfigOptionItem
            title="Référent Assistance / Support du département"
            description="Personnel désigné comme point d'entrée pour la qualification des demandes."
            icon="pi pi-user"
            icon-color-class="text-orange-500"
            icon-bg-class="bg-orange-500/10"
            type="select"
            :model-value="deptOpt.helpdesk_responsable ? String(deptOpt.helpdesk_responsable) : ''"
            :options="personnelOptions"
            :loading="loadingKeys['helpdesk_responsable']"
            @change="(val) => updateOption('helpdesk_responsable', val)"
          />

          <ConfigOptionItem
            title="Notification par mail à l'ouverture d'un ticket"
            description="Avertit le responsable de service dès qu'une nouvelle demande est enregistrée."
            icon="pi pi-envelope"
            icon-color-class="text-blue-500"
            icon-bg-class="bg-blue-500/10"
            type="switch"
            :model-value="deptOpt.helpdesk_mail_nouveau_ticket ?? true"
            :loading="loadingKeys['helpdesk_mail_nouveau_ticket']"
            @change="(val) => updateOption('helpdesk_mail_nouveau_ticket', val)"
          />

          <ConfigOptionItem
            title="Visibilité partagée des tickets au sein du département"
            description="Permet aux collègues d'un même département de consulter les tickets ouverts pour éviter les signalements en doublon."
            icon="pi pi-eye"
            icon-color-class="text-purple-500"
            icon-bg-class="bg-purple-500/10"
            type="switch"
            :model-value="deptOpt.helpdesk_visibilite_partagee ?? false"
            :loading="loadingKeys['helpdesk_visibilite_partagee']"
            @change="(val) => updateOption('helpdesk_visibilite_partagee', val)"
          />
        </div>
      </Card>
    </div>
  </div>
</template>

<style scoped>
</style>

