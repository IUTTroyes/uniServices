<script setup>
import { ref, computed, watch } from 'vue';
import { Card, ConfigOptionItem } from '@components';
import { updateSemestreService } from '@requests';
import { useToast } from 'primevue/usetoast';
import Tag from 'primevue/tag';

const props = defineProps({
  semestres: {
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

const selectedSemestreId = ref(null);
const loadingKeys = ref({});

// N'afficher que les semestres actifs
const activeSemestres = computed(() => {
  return (props.semestres || []).filter(s => s.actif === true);
});

// Sélectionner par défaut le premier semestre actif
watch(activeSemestres, (list) => {
  if (list && list.length > 0) {
    if (!selectedSemestreId.value || !list.some(s => s.id === selectedSemestreId.value)) {
      selectedSemestreId.value = list[0].id;
    }
  } else {
    selectedSemestreId.value = null;
  }
}, { immediate: true });

const selectedSemestre = computed(() => {
  return activeSemestres.value.find(s => s.id === selectedSemestreId.value) || activeSemestres.value[0] || null;
});

const semOpt = computed(() => selectedSemestre.value?.opt || {});

const personnelOptions = computed(() => {
  const list = (props.personnels || []).map(p => ({
    label: `${p.nom || ''} ${p.prenom || ''}`.trim() || p.display || `Personnel #${p.id}`,
    value: p.id
  }));
  return [{ label: 'Aucun / Non défini', value: 0 }, ...list];
});

const ppnOptions = computed(() => {
  const ppns = selectedSemestre.value?.diplome?.ppns || [];
  const list = ppns.map(p => ({
    label: `${p.libelle || 'PPN'} (${p.annee || ''})`,
    value: p['@id'] || `/api/structure_pns/${p.id}`
  }));
  return [{ label: 'Aucun / Non sélectionné', value: '' }, ...list];
});

const updateOption = async (key, value) => {
  if (!selectedSemestre.value?.id) return;
  loadingKeys.value[key] = true;

  try {
    const currentOpt = { ...semOpt.value };
    currentOpt[key] = value;

    await updateSemestreService(selectedSemestre.value.id, {
      opt: currentOpt
    }, false);

    if (selectedSemestre.value) {
      if (!selectedSemestre.value.opt) selectedSemestre.value.opt = {};
      selectedSemestre.value.opt = { ...selectedSemestre.value.opt, [key]: value };
    }

    toast.add({
      severity: 'success',
      summary: 'Configuration enregistrée',
      detail: `L'option ${key} du semestre a été mise à jour.`,
      life: 2500
    });
    emit('updated', { semestreId: selectedSemestre.value.id, key, value });
  } catch (error) {
    console.error(`Erreur mise à jour option semestre ${key}:`, error);
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de mettre à jour le semestre.',
      life: 3500
    });
  } finally {
    loadingKeys.value[key] = false;
  }
};

const updateDirectField = async (fieldName, value) => {
  if (!selectedSemestre.value?.id) return;
  loadingKeys.value[fieldName] = true;

  try {
    await updateSemestreService(selectedSemestre.value.id, {
      [fieldName]: value
    }, false);

    toast.add({
      severity: 'success',
      summary: 'Enregistré',
      detail: `Le champ ${fieldName} a été mis à jour.`,
      life: 2500
    });
    emit('updated', { semestreId: selectedSemestre.value.id, key: fieldName, value });
  } catch (error) {
    console.error(`Erreur mise à jour semestre ${fieldName}:`, error);
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de mettre à jour le semestre.',
      life: 3500
    });
  } finally {
    loadingKeys.value[fieldName] = false;
  }
};
</script>

<template>
  <div class="space-y-6">
    <!-- Barre de sélection des semestres actifs -->
    <div v-if="activeSemestres.length > 0" class="flex flex-wrap items-center gap-2 p-2 bg-slate-50 dark:bg-slate-900/40 rounded-2xl border border-slate-200/60 dark:border-slate-800">
      <button
        v-for="s in activeSemestres"
        :key="s.id"
        type="button"
        @click="selectedSemestreId = s.id"
        :class="[
          'flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 cursor-pointer',
          selectedSemestreId === s.id
            ? 'bg-white dark:bg-slate-800 text-primary shadow-sm border border-slate-200/80 dark:border-slate-700'
            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-white/60 dark:hover:bg-slate-800/50'
        ]"
      >
        <i class="pi pi-list text-base" />
        <span>{{ s.libelle }}</span>
        <Tag
          v-if="s.annee?.libelle"
          :value="s.annee.libelle"
          severity="secondary"
          class="text-[10px] font-bold"
        />
      </button>
    </div>

    <div v-if="!selectedSemestre" class="card p-12 text-center text-slate-500">
      <i class="pi pi-info-circle text-3xl mb-3 text-slate-400 block" />
      <p class="font-medium">Aucun semestre actif n'est disponible pour ce département.</p>
    </div>

    <template v-else>
      <!-- Card 1: Notifications & Alertes -->
      <Card
        :title="`Notifications & Alertes Mails — ${selectedSemestre.libelle}`"
        subtitle="Envois automatiques de courriels et correspondants d'alerte"
        icon="pi pi-envelope"
        color="amber"
      >
        <div class="flex flex-col divide-y divide-slate-100 dark:divide-slate-800/60">
          <ConfigOptionItem
            title="Envoi par mail du relevé de notes"
            description="Autorise l'envoi automatisé des bulletins et relevés de notes aux étudiants par courrier électronique."
            icon="pi pi-send"
            icon-color-class="text-amber-500"
            icon-bg-class="bg-amber-500/10"
            type="switch"
            :model-value="semOpt.mail_releve ?? false"
            :loading="loadingKeys['mail_releve']"
            @change="(val) => updateOption('mail_releve', val)"
          />

          <ConfigOptionItem
            title="Destinataire en copie du relevé de notes"
            description="Enseignant ou secrétariat systématiquement en copie lors de l'envoi des relevés."
            icon="pi pi-user-plus"
            icon-color-class="text-amber-500"
            icon-bg-class="bg-amber-500/10"
            type="select"
            :model-value="semOpt.dest_mail_releve ?? 0"
            :options="personnelOptions"
            :loading="loadingKeys['dest_mail_releve']"
            @change="(val) => updateOption('dest_mail_releve', Number(val))"
          />

          <ConfigOptionItem
            title="Alerte mail lors de modification d'une note"
            description="Avertit le responsable lorsqu'une note déjà validée est modifiée ultérieurement par un enseignant."
            icon="pi pi-bell"
            icon-color-class="text-orange-500"
            icon-bg-class="bg-orange-500/10"
            type="switch"
            :model-value="semOpt.mail_modif_note ?? false"
            :loading="loadingKeys['mail_modif_note']"
            @change="(val) => updateOption('mail_modif_note', val)"
          />

          <ConfigOptionItem
            title="Destinataire alerte modification de note"
            description="Personnel recevant l'alerte en cas de changement de note."
            icon="pi pi-user"
            icon-color-class="text-orange-500"
            icon-bg-class="bg-orange-500/10"
            type="select"
            :model-value="semOpt.dest_mail_modif_note ?? 0"
            :options="personnelOptions"
            :loading="loadingKeys['dest_mail_modif_note']"
            @change="(val) => updateOption('dest_mail_modif_note', Number(val))"
          />

          <ConfigOptionItem
            title="Alerte dépassement d'absences au responsable"
            description="Envoie un récapitulatif dès qu'un étudiant franchit le seuil critique d'absences tolérées."
            icon="pi pi-exclamation-circle"
            icon-color-class="text-red-500"
            icon-bg-class="bg-red-500/10"
            type="switch"
            :model-value="semOpt.mail_absence_resp ?? false"
            :loading="loadingKeys['mail_absence_resp']"
            @change="(val) => updateOption('mail_absence_resp', val)"
          />

          <ConfigOptionItem
            title="Destinataire alerte dépassement absences"
            description="Responsable recevant le signalement des absences excessives."
            icon="pi pi-user"
            icon-color-class="text-red-500"
            icon-bg-class="bg-red-500/10"
            type="select"
            :model-value="semOpt.dest_mail_absence_resp ?? 0"
            :options="personnelOptions"
            :loading="loadingKeys['dest_mail_absence_resp']"
            @change="(val) => updateOption('dest_mail_absence_resp', Number(val))"
          />

          <ConfigOptionItem
            title="Notification immédiate envoyée à l'étudiant"
            description="Avertit par email l'étudiant à chaque nouvelle absence saisie par un enseignant."
            icon="pi pi-inbox"
            icon-color-class="text-blue-500"
            icon-bg-class="bg-blue-500/10"
            type="switch"
            :model-value="semOpt.mail_absence_etudiant ?? false"
            :loading="loadingKeys['mail_absence_etudiant']"
            @change="(val) => updateOption('mail_absence_etudiant', val)"
          />

          <ConfigOptionItem
            title="Notification secrétariat lors du dépôt d'un justificatif"
            description="Alerte le secrétariat lorsqu'un étudiant dépose un justificatif d'absence sur son portail."
            icon="pi pi-file-check"
            icon-color-class="text-teal-500"
            icon-bg-class="bg-teal-500/10"
            type="switch"
            :model-value="semOpt.mail_assistante_justif_absence ?? false"
            :loading="loadingKeys['mail_assistante_justif_absence']"
            @change="(val) => updateOption('mail_assistante_justif_absence', val)"
          />
        </div>
      </Card>

      <!-- Card 2: Évaluations, Examens & Compétences -->
      <Card
        title="Évaluations, Notes & Référentiels"
        subtitle="Visibilité des résultats, bilans de compétences et rattrapages"
        icon="pi pi-check-square"
        color="violet"
      >
        <div class="flex flex-col divide-y divide-slate-100 dark:divide-slate-800/60">
          <ConfigOptionItem
            v-if="ppnOptions.length > 1"
            title="Programme Pédagogique (PPN) actif"
            description="Associe la maquette ou le PPN de référence applicable aux enseignements de ce semestre."
            icon="pi pi-book"
            icon-color-class="text-violet-500"
            icon-bg-class="bg-violet-500/10"
            type="select"
            :model-value="selectedSemestre.ppnActif?.['@id'] || (selectedSemestre.ppnActif ? `/api/structure_pns/${selectedSemestre.ppnActif.id || selectedSemestre.ppnActif}` : '')"
            :options="ppnOptions"
            :loading="loadingKeys['ppnActif']"
            placeholder="Sélectionner le PPN..."
            @change="(val) => updateDirectField('ppnActif', val)"
          />

          <ConfigOptionItem
            title="Affichage immédiat des évaluations"
            description="Les notes saisies par les enseignants sont immédiatement consultables par les étudiants sans blocage préalable."
            icon="pi pi-eye"
            icon-color-class="text-blue-500"
            icon-bg-class="bg-blue-500/10"
            type="switch"
            :model-value="semOpt.eval_visible ?? true"
            :loading="loadingKeys['eval_visible']"
            @change="(val) => updateOption('eval_visible', val)"
          />

          <ConfigOptionItem
            title="Modification des évaluations autorisée"
            description="Autorise les enseignants à corriger ou réajuster les notes d'une évaluation déjà saisie."
            icon="pi pi-file-edit"
            icon-color-class="text-indigo-500"
            icon-bg-class="bg-indigo-500/10"
            type="switch"
            :model-value="semOpt.eval_modif ?? true"
            :loading="loadingKeys['eval_modif']"
            @change="(val) => updateOption('eval_modif', val)"
          />

          <ConfigOptionItem
            title="Afficher le bilan de semestre B.U.T."
            description="Affiche l'onglet de bilan des compétences, niveaux et moyennes B.U.T. aux étudiants dans leur profil."
            icon="pi pi-chart-bar"
            icon-color-class="text-emerald-500"
            icon-bg-class="bg-emerald-500/10"
            badge="B.U.T."
            badge-severity="info"
            type="switch"
            :model-value="semOpt.bilan_semestre ?? true"
            :loading="loadingKeys['bilan_semestre']"
            @change="(val) => updateOption('bilan_semestre', val)"
          />

          <ConfigOptionItem
            title="Organisation des sessions de rattrapage"
            description="Active la gestion des sessions d'examens de rattrapage sur ce semestre."
            icon="pi pi-refresh"
            icon-color-class="text-amber-500"
            icon-bg-class="bg-amber-500/10"
            type="switch"
            :model-value="semOpt.rattrapage ?? true"
            :loading="loadingKeys['rattrapage']"
            @change="(val) => updateOption('rattrapage', val)"
          />

          <ConfigOptionItem
            title="Responsable des rattrapages"
            description="Personnel désigné comme référent pour l'organisation et le calendrier des rattrapages."
            icon="pi pi-user"
            icon-color-class="text-amber-500"
            icon-bg-class="bg-amber-500/10"
            type="select"
            :model-value="semOpt.mail_rattrapage ?? 0"
            :options="personnelOptions"
            :loading="loadingKeys['mail_rattrapage']"
            @change="(val) => updateOption('mail_rattrapage', Number(val))"
          />
        </div>
      </Card>

      <!-- Card 3: Pénalités d'absence -->
      <Card
        title="Règles de Pénalité d'absence"
        subtitle="Malus automatique sur la moyenne générale en cas d'absences injustifiées"
        icon="pi pi-exclamation-triangle"
        color="red"
      >
        <div class="flex flex-col divide-y divide-slate-100 dark:divide-slate-800/60">
          <ConfigOptionItem
            title="Activer la pénalité sur la moyenne pour absences injustifiées"
            description="Déduit un malus sur la moyenne générale du semestre lorsque les absences ne sont pas justifiées dans les délais."
            icon="pi pi-minus-circle"
            icon-color-class="text-red-500"
            icon-bg-class="bg-red-500/10"
            type="switch"
            :model-value="semOpt.opt_penalite_absence ?? true"
            :loading="loadingKeys['opt_penalite_absence']"
            @change="(val) => updateOption('opt_penalite_absence', val)"
          />

          <ConfigOptionItem
            title="Points de pénalité par absence injustifiée"
            description="Valeur du retrait de points sur la moyenne (ex: 0.5 point par absence non justifiée)."
            icon="pi pi-percentage"
            icon-color-class="text-red-500"
            icon-bg-class="bg-red-500/10"
            type="number"
            :model-value="semOpt.penalite_absence ?? 0.5"
            :min="0.1"
            :max="5"
            :step="0.1"
            suffix="point(s)"
            :loading="loadingKeys['penalite_absence']"
            @change="(val) => updateOption('penalite_absence', Number(val))"
          />
        </div>
      </Card>
    </template>
  </div>
</template>
