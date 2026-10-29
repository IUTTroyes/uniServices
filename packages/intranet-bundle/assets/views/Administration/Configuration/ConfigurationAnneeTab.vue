<script setup>
import { ref, computed } from 'vue';
import { Card, ConfigOptionItem } from '@components';
import { updateAnneeService } from '@requests';
import { useToast } from 'primevue/usetoast';
import Tag from 'primevue/tag';

const props = defineProps({
  annees: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['updated']);
const toast = useToast();

const loadingKeys = ref({});

// Ne retenir que les années actives
const activeAnnees = computed(() => {
  return (props.annees || []).filter(a => a.actif === true);
});

// Grouper les années actives par diplôme
const groupedByDiplome = computed(() => {
  const groups = {};
  for (const annee of activeAnnees.value) {
    const diplomeName = annee.diplome?.libelle || annee.diplome?.sigle || 'Formation';
    if (!groups[diplomeName]) {
      groups[diplomeName] = [];
    }
    groups[diplomeName].push(annee);
  }
  return groups;
});

const updateAlternance = async (annee, value) => {
  if (!annee?.id) return;
  loadingKeys.value[annee.id] = true;

  try {
    const currentOpt = { ...(annee.opt || {}) };
    currentOpt.alternance = value;

    await updateAnneeService(annee.id, {
      opt: currentOpt
    }, false);

    if (annee) {
      if (!annee.opt) annee.opt = {};
      annee.opt = { ...annee.opt, alternance: value };
    }

    toast.add({
      severity: 'success',
      summary: 'Configuration enregistrée',
      detail: `L'alternance pour ${annee.libelle} a été mise à jour.`,
      life: 2500
    });
    emit('updated', { anneeId: annee.id, key: 'alternance', value });
  } catch (error) {
    console.error(`Erreur mise à jour alternance année ${annee.id}:`, error);
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de mettre à jour l\'année.',
      life: 3500
    });
  } finally {
    loadingKeys.value[annee.id] = false;
  }
};
</script>

<template>
  <div class="space-y-6">
    <div v-if="activeAnnees.length === 0" class="card p-12 text-center text-slate-500">
      <i class="pi pi-info-circle text-3xl mb-3 text-slate-400 block" />
      <p class="font-medium">Aucune année d'étude active n'est enregistrée pour ce département.</p>
    </div>

    <template v-else>
      <Card
        v-for="(years, diplomeName) in groupedByDiplome"
        :key="diplomeName"
        :title="`Années d'études — ${diplomeName}`"
        subtitle="Définissez le régime d'études (alternance / formation initiale) par promotion"
        icon="pi pi-calendar-plus"
        color="emerald"
      >
        <div class="flex flex-col divide-y divide-slate-100 dark:divide-slate-800/60">
          <div
            v-for="annee in years"
            :key="annee.id"
            class="py-1"
          >
            <ConfigOptionItem
              :title="`Régime Alternance — ${annee.libelle}`"
              :description="`Indique si l'année ${annee.libelle} est dispensée sous contrat d'apprentissage ou de professionnalisation.`"
              icon="pi pi-briefcase"
              icon-color-class="text-emerald-500"
              icon-bg-class="bg-emerald-500/10"
              type="switch"
              :model-value="annee.opt?.alternance ?? false"
              :loading="loadingKeys[annee.id]"
              badge="Alternance"
              :badge-severity="annee.opt?.alternance ? 'success' : 'secondary'"
              @change="(val) => updateAlternance(annee, val)"
            />
          </div>
        </div>
      </Card>
    </template>
  </div>
</template>
