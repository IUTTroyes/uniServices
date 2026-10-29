<script setup>
import { ref, computed, watch } from 'vue';
import { Card, ConfigOptionItem } from '@components';
import { updateDiplomeService } from '@requests';
import { useToast } from 'primevue/usetoast';
import Tag from 'primevue/tag';

const props = defineProps({
  diplomes: {
    type: Array,
    default: () => []
  },
  anneesUniv: {
    type: Array,
    default: () => []
  },
  personnels: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['updated']);
const toast = useToast();

const selectedDiplomeId = ref(null);
const loadingKeys = ref({});

// Filtrer uniquement les diplômes actifs
const activeDiplomes = computed(() => {
  return (props.diplomes || []).filter(d => d.actif !== false);
});

// Sélectionner par défaut le premier diplôme actif
watch(activeDiplomes, (list) => {
  if (list && list.length > 0) {
    if (!selectedDiplomeId.value || !list.some(d => d.id === selectedDiplomeId.value)) {
      selectedDiplomeId.value = list[0].id;
    }
  } else {
    selectedDiplomeId.value = null;
  }
}, { immediate: true });

const selectedDiplome = computed(() => {
  return activeDiplomes.value.find(d => d.id === selectedDiplomeId.value) || activeDiplomes.value[0] || null;
});

const diplomeOpt = computed(() => selectedDiplome.value?.opt || {});

const anneeUnivOptions = computed(() => {
  return props.anneesUniv.map(a => ({
    label: a.displayAnneeUniversitaire || a.libelle || String(a.annee || a.id),
    value: a['@id'] || `/api/structure_annee_universitaires/${a.id}`
  }));
});

const personnelOptions = computed(() => {
  const list = (props.personnels || []).map(p => ({
    label: `${p.nom || ''} ${p.prenom || ''}`.trim() || p.display || `Personnel #${p.id}`,
    value: p.id
  }));
  return [{ label: 'Aucun / Non défini', value: 0 }, ...list];
});

const updateOption = async (key, value) => {
  if (!selectedDiplome.value?.id) return;
  loadingKeys.value[key] = true;

  try {
    const currentOpt = { ...diplomeOpt.value };
    currentOpt[key] = value;

    await updateDiplomeService(selectedDiplome.value.id, {
      opt: currentOpt
    }, false);

    if (selectedDiplome.value) {
      if (!selectedDiplome.value.opt) selectedDiplome.value.opt = {};
      selectedDiplome.value.opt = { ...selectedDiplome.value.opt, [key]: value };
    }

    toast.add({
      severity: 'success',
      summary: 'Configuration enregistrée',
      detail: `L'option ${key} du diplôme a été mise à jour.`,
      life: 2500
    });
    emit('updated', { diplomeId: selectedDiplome.value.id, key, value });
  } catch (error) {
    console.error(`Erreur mise à jour option diplôme ${key}:`, error);
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de mettre à jour le diplôme.',
      life: 3500
    });
  } finally {
    loadingKeys.value[key] = false;
  }
};

const updateAnneeUniversitaire = async (anneeUnivIri) => {
  if (!selectedDiplome.value?.id) return;
  loadingKeys.value['anneeUniversitaire'] = true;

  try {
    await updateDiplomeService(selectedDiplome.value.id, {
      anneeUniversitaire: anneeUnivIri
    }, false);

    toast.add({
      severity: 'success',
      summary: 'Année universitaire mise à jour',
      detail: 'L\'année courante du diplôme a été enregistrée.',
      life: 2500
    });
    emit('updated', { diplomeId: selectedDiplome.value.id, key: 'anneeUniversitaire', value: anneeUnivIri });
  } catch (error) {
    console.error('Erreur mise à jour anneeUniversitaire diplôme:', error);
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible d\'associer l\'année universitaire.',
      life: 3500
    });
  } finally {
    loadingKeys.value['anneeUniversitaire'] = false;
  }
};
</script>

<template>
  <div class="space-y-6">
    <!-- Barre de sélection des diplômes actifs -->
    <div v-if="activeDiplomes.length > 0" class="flex flex-wrap items-center gap-2 p-2 bg-slate-50 dark:bg-slate-900/40 rounded-2xl border border-slate-200/60 dark:border-slate-800">
      <button
        v-for="d in activeDiplomes"
        :key="d.id"
        type="button"
        @click="selectedDiplomeId = d.id"
        :class="[
          'flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 cursor-pointer',
          selectedDiplomeId === d.id
            ? 'bg-white dark:bg-slate-800 text-primary shadow-sm border border-slate-200/80 dark:border-slate-700'
            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-white/60 dark:hover:bg-slate-800/50'
        ]"
      >
        <i class="pi pi-graduation-cap text-base" />
        <span>{{ d.sigle || d.displayCourt || d.libelle }}</span>
        <Tag v-if="d.typeDiplome?.sigle" :value="d.typeDiplome.sigle" severity="secondary" class="text-[10px] font-bold" />
      </button>
    </div>

    <div v-if="!selectedDiplome" class="card p-12 text-center text-slate-500">
      <i class="pi pi-info-circle text-3xl mb-3 text-slate-400 block" />
      <p class="font-medium">Aucun diplôme actif n'est disponible pour ce département.</p>
    </div>

    <template v-else>
      <!-- Card 1: Calendrier & Référence -->
      <Card
        :title="`Organisation & Références — ${selectedDiplome.libelle}`"
        subtitle="Année universitaire active et référents qualité"
        icon="pi pi-calendar"
        color="violet"
      >
        <div class="flex flex-col divide-y divide-slate-100 dark:divide-slate-800/60">
          <ConfigOptionItem
            title="Démarche Qualité"
            description="Active le suivi et la production des indicateurs qualité pour cette formation."
            icon="pi pi-check-circle"
            icon-color-class="text-emerald-500"
            icon-bg-class="bg-emerald-500/10"
            type="switch"
            :model-value="diplomeOpt.certif_qualite ?? true"
            :loading="loadingKeys['certif_qualite']"
            @change="(val) => updateOption('certif_qualite', val)"
          />

          <ConfigOptionItem
            title="Responsable Qualité"
            description="Personnel désigné comme référent qualité pour ce diplôme."
            icon="pi pi-user"
            icon-color-class="text-indigo-500"
            icon-bg-class="bg-indigo-500/10"
            type="select"
            :model-value="diplomeOpt.resp_qualite ?? 0"
            :options="personnelOptions"
            :loading="loadingKeys['resp_qualite']"
            placeholder="Sélectionner un responsable qualité..."
            @change="(val) => updateOption('resp_qualite', Number(val))"
          />

          <ConfigOptionItem
            title="Mise à jour automatique CELCAT"
            description="Synchronisation automatique des plannings et cours depuis l'outil CELCAT."
            icon="pi pi-sync"
            icon-color-class="text-blue-500"
            icon-bg-class="bg-blue-500/10"
            type="switch"
            :model-value="diplomeOpt.update_celcat ?? false"
            :loading="loadingKeys['update_celcat']"
            @change="(val) => updateOption('update_celcat', val)"
          />
        </div>
      </Card>

      <!-- Card 2: Gestion des Absences -->
      <Card
        title="Règles de Gestion des Absences"
        subtitle="Délais et autorisations de saisie des absences par les enseignants"
        icon="pi pi-calendar-times"
        color="amber"
      >
        <div class="flex flex-col divide-y divide-slate-100 dark:divide-slate-800/60">
          <ConfigOptionItem
            title="Délai maximum de saisie des absences"
            description="Nombre de jours accordé aux enseignants pour déclarer une absence après la séance (0 = illimité)."
            icon="pi pi-hourglass"
            icon-color-class="text-amber-500"
            icon-bg-class="bg-amber-500/10"
            type="number"
            :model-value="diplomeOpt.nb_jours_saisie_absence ?? 15"
            :min="0"
            :max="60"
            suffix="jour(s)"
            :loading="loadingKeys['nb_jours_saisie_absence']"
            @change="(val) => updateOption('nb_jours_saisie_absence', Number(val))"
          />

          <ConfigOptionItem
            title="Suppression d'absence par les enseignants"
            description="Autorise les enseignants à supprimer eux-mêmes une absence signalée par erreur."
            icon="pi pi-trash"
            icon-color-class="text-red-500"
            icon-bg-class="bg-red-500/10"
            type="switch"
            :model-value="diplomeOpt.supp_absence ?? true"
            :loading="loadingKeys['supp_absence']"
            @change="(val) => updateOption('supp_absence', val)"
          />

          <ConfigOptionItem
            title="Saisie d'absences en Cours Magistral (CM)"
            description="Autorise l'appel et l'enregistrement des absences lors des séances de cours magistraux."
            icon="pi pi-megaphone"
            icon-color-class="text-purple-500"
            icon-bg-class="bg-purple-500/10"
            type="switch"
            :model-value="diplomeOpt.saisie_cm_autorisee ?? true"
            :loading="loadingKeys['saisie_cm_autorisee']"
            @change="(val) => updateOption('saisie_cm_autorisee', val)"
          />
        </div>
      </Card>

      <!-- Card 3: Espace Étudiant & Évaluations -->
      <Card
        title="Espace Étudiant & Examens"
        subtitle="Visibilité des notes, anonymat des copies et affichage de l'EDT"
        icon="pi pi-eye"
        color="teal"
      >
        <div class="flex flex-col divide-y divide-slate-100 dark:divide-slate-800/60">
          <ConfigOptionItem
            title="Espace personnel visible"
            description="Rend visible l'espace personnel, notes et suivis dans le compte étudiant de ce diplôme."
            icon="pi pi-user-check"
            icon-color-class="text-teal-500"
            icon-bg-class="bg-teal-500/10"
            type="switch"
            :model-value="diplomeOpt.espace_perso_visible ?? false"
            :loading="loadingKeys['espace_perso_visible']"
            @change="(val) => updateOption('espace_perso_visible', val)"
          />

          <ConfigOptionItem
            title="Anonymat des copies et évaluations"
            description="Masque les identités des étudiants lors des évaluations pour une correction à l'aveugle."
            icon="pi pi-user-minus"
            icon-color-class="text-slate-500"
            icon-bg-class="bg-slate-500/10"
            type="switch"
            :model-value="diplomeOpt.anonymat ?? false"
            :loading="loadingKeys['anonymat']"
            @change="(val) => updateOption('anonymat', val)"
          />

          <ConfigOptionItem
            title="Semaines visibles sur l'emploi du temps"
            description="Nombre de semaines prévisionnelles affichées aux étudiants (0 = toutes les semaines affichées)."
            icon="pi pi-calendar"
            icon-color-class="text-blue-500"
            icon-bg-class="bg-blue-500/10"
            type="number"
            :model-value="diplomeOpt.semaine_visible ?? 2"
            :min="0"
            :max="30"
            suffix="semaine(s)"
            :loading="loadingKeys['semaine_visible']"
            @change="(val) => updateOption('semaine_visible', Number(val))"
          />

          <ConfigOptionItem
            title="Commentaires sur les relevés de notes"
            description="Affiche les appréciations et remarques individuelles sur les bulletins de notes."
            icon="pi pi-comment"
            icon-color-class="text-emerald-500"
            icon-bg-class="bg-emerald-500/10"
            type="switch"
            :model-value="diplomeOpt.commentaire_releve ?? false"
            :loading="loadingKeys['commentaire_releve']"
            @change="(val) => updateOption('commentaire_releve', val)"
          />
        </div>
      </Card>
    </template>
  </div>
</template>
