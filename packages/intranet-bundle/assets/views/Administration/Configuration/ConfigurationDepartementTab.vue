<script setup>
import { ref, computed } from 'vue';
import { Card, ConfigOptionItem } from '@components';
import { updateDepartementService } from '@requests';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
  departement: {
    type: Object,
    required: true
  },
  personnels: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['updated']);
const toast = useToast();

const loadingKeys = ref({});

const deptOpt = computed(() => props.departement?.opt || {});

const personnelOptions = computed(() => {
  const list = (props.personnels || []).map(p => ({
    label: `${p.nom || ''} ${p.prenom || ''}`.trim() || p.display || `Personnel #${p.id}`,
    value: String(p.id)
  }));
  return [{ label: 'Aucun / Non sélectionné', value: '' }, ...list];
});

const updateOption = async (key, value) => {
  if (!props.departement?.id) return;
  loadingKeys.value[key] = true;

  try {
    const currentOpt = { ...deptOpt.value };
    currentOpt[key] = value;

    await updateDepartementService(props.departement.id, {
      opt: currentOpt
    }, false);

    if (props.departement) {
      if (!props.departement.opt) props.departement.opt = {};
      props.departement.opt = { ...props.departement.opt, [key]: value };
    }

    toast.add({
      severity: 'success',
      summary: 'Configuration enregistrée',
      detail: `L'option ${key} a été mise à jour.`,
      life: 2500
    });
    emit('updated', { key, value });
  } catch (error) {
    console.error(`Erreur mise à jour option département ${key}:`, error);
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
</script>

<template>
  <div class="space-y-6">
    <!-- Card Modules Transversaux -->
    <Card
      title="Modules & Outils Transversaux"
      :subtitle="`Activez ou désactivez les fonctionnalités associées au département ${departement?.libelle || ''}`"
      icon="pi pi-box"
      color="blue"
    >
      <div class="flex flex-col divide-y divide-slate-100 dark:divide-slate-800/60">
        <ConfigOptionItem
          title="Emplois du temps"
          description="Active l'affichage, la consultation et la synchronisation des emplois du temps pour ce département."
          icon="pi pi-calendar"
          icon-color-class="text-blue-500"
          icon-bg-class="bg-blue-500/10"
          type="switch"
          :model-value="deptOpt.edt ?? false"
          :loading="loadingKeys['edt']"
          @change="(val) => updateOption('edt', val)"
        />
      </div>
    </Card>

    <!-- Card Responsabilités -->
    <Card
      title="Responsabilités & Référents du Département"
      subtitle="Désignation des correspondants et référents transversaux"
      icon="pi pi-users"
      color="purple"
    >
      <div class="flex flex-col divide-y divide-slate-100 dark:divide-slate-800/60">
        <ConfigOptionItem
          title="Responsable Relations Internationales (RI)"
          description="Enseignant ou personnel référent assurant le suivi des mobilités et étudiants internationaux."
          icon="pi pi-globe"
          icon-color-class="text-indigo-500"
          icon-bg-class="bg-indigo-500/10"
          type="select"
          :model-value="deptOpt.resp_ri ? String(deptOpt.resp_ri) : ''"
          :options="personnelOptions"
          :loading="loadingKeys['resp_ri']"
          placeholder="Sélectionner un enseignant..."
          @change="(val) => updateOption('resp_ri', val)"
        />
      </div>
    </Card>
  </div>
</template>
