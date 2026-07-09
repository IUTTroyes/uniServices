<script setup>
import { ref, computed, nextTick } from 'vue';
import {PermissionGuard} from '@components';
import EvaluationSaisieNotesForm from "./EvaluationSaisieNotesForm.vue";
import EvaluationForm from "./EvaluationForm.vue";
import EvaluationListeInitForm from "./EvaluationListeInitForm.vue";
import EvaluationStatistiques from "./EvaluationStatistiques.vue";
import { useConfirm } from 'primevue/useconfirm'
import { updateEtudiantNoteService, updateEvaluationService, getEtudiantNotesService } from '@requests';
import { useToast } from "primevue/usetoast";

const toast = useToast();
const showDialog = ref(false);
const dialogMode = ref(''); // 'init' | 'edit' | 'saisie'
const dialogHeader = ref('');

const props = defineProps({
  evaluation: { type: Object, required: true },
  semestreId: { type: Number, required: true },
  useLocalDialog: { type: Boolean, default: false },
  inStatsContext: { type: Boolean, default: false },
});

const emit = defineEmits(['open-dialog', 'update-visibility', 'update-edit', 'saved', 'publish-results', 'cancel-eval', 'reactiver-eval']);

const getSeverity = (type) => {
  switch (type) {
    case 'Examen':
    return 'error';
    case 'Travaux Pratiques':
    return 'info';
    case 'Projet':
    return 'warn';
    default:
    return 'secondary';
  }
};

const onOpen = async (mode, header) => {
  if (props.useLocalDialog) {
    if (showDialog.value) {
      showDialog.value = false;
      await nextTick();
    }
    dialogMode.value = mode;
    dialogHeader.value = header;
    showDialog.value = true;
  } else {
    // Déporter l'ouverture au parent pour éviter l'ouverture en double
    emit('open-dialog', props.evaluation.id, mode, header);
  }
};

const onToggleVisibility = () => {
  emit('update-visibility', props.evaluation);
};

const onToggleEdit = () => {
  emit('update-edit', props.evaluation);
};

// Choix du composant selon le mode
const dialogComponent = computed(() => {
  return dialogMode.value === 'saisie' ? EvaluationSaisieNotesForm : dialogMode.value === 'edit' ? EvaluationForm : dialogMode.value === 'initAll' ? EvaluationListeInitForm : dialogMode.value === 'stat' ? EvaluationStatistiques : null;
});

const onEvaluationClosed = async () => {
  // fermeture locale uniquement
  showDialog.value = false;
};
const onEvaluationSaved = async () => {
  // Propager l'événement afin que le parent (ex: modal Statistiques) puisse rafraîchir et remonter jusqu'à la vue
  emit('saved');
};

const getEtatLabel = (etat) => {
  switch (etat) {
    case 'non_initialisee':
    return 'À initialiser';
    case 'initialisee':
    return 'Initialisée';
    case 'planifiee':
    return 'Planifiée';
    case 'terminee':
    return 'Terminée';
    case 'completee':
    return 'Notes saisies';
    case 'publiee':
    return 'Publiée';
    case 'annulee':
    return 'Annulée';
    default:
    return 'Erreur';
  }
};


const confirm = useConfirm()

const showConfirmDialog = () => {
  confirm.require({
    message: 'Êtes-vous sûr de vouloir publier les résultats de cet évaluation ?',
    header: 'Confirmation de publication',
    icon: 'pi pi-exclamation-triangle',
    rejectProps: {
      label: 'Annuler',
      severity: 'secondary',
      outlined: true
    },
    acceptProps: {
      label: 'Confirmer',
      severity: 'primary'
    },
    accept: () => {
      onPublishResults()
    }
  })
}


const onPublishResults = async () => {
  try {
    const dataEval = {
      etat: 'publiee',
      modifiable: false,
    }
    await updateEvaluationService(props.evaluation.id, dataEval);
    const paramsEtudiantsNotes = {
      evaluationId: props.evaluation.id,
    }
    const etudiantsNotes = await getEtudiantNotesService(paramsEtudiantsNotes)
    etudiantsNotes.forEach(async (etudiantNote) => {
      const dataNotes = {
        publiee: true,
      }
      await updateEtudiantNoteService(etudiantNote.id, dataNotes);
    })
    emit('saved');
  } catch (error) {
    console.error('Erreur lors de la publication des résultats:', error);
    toast.add({severity: 'error', summary: 'Erreur', detail: 'Erreur lors de la publication des résultats', life: 3000});
  } finally {
    toast.add({severity: 'success', summary: 'Succès', detail: 'Résultats publiés avec succès', life: 3000});
  }
}

const showCancelDialog = () => {
  confirm.require({
    message: 'Êtes-vous sûr de vouloir annuler l\'évaluation ?',
    header: 'Confirmation d\'annulation',
    icon: 'pi pi-exclamation-triangle',
    rejectProps: {
      label: 'Annuler',
      severity: 'secondary',
      outlined: true
    },
    acceptProps: {
      label: 'Confirmer',
      severity: 'danger'
    },
    accept: () => {
      onCancelEvaluation()
    }
  })
}

const onCancelEvaluation = async () => {
  try {
    const paramsEtudiantsNotes = {
      evaluationId: props.evaluation.id,
    }
    const etudiantsNotes = await getEtudiantNotesService(paramsEtudiantsNotes)
    etudiantsNotes.forEach(async (etudiantNote) => {
      const dataNotes = {
        publiee: false,
      }
      await updateEtudiantNoteService(etudiantNote.id, dataNotes);
    })
    const dataEval = {
      etat: 'annulee',
    }
    await updateEvaluationService(props.evaluation.id, dataEval);
    emit('cancel-eval');
  } catch (error) {
    console.error('Erreur lors de l\'annulation de l\'évaluation:', error);
    toast.add({severity: 'error', summary: 'Erreur', detail: 'Erreur lors de l\'annulation de l\'évaluation', life: 3000});
  } finally {
    toast.add({severity: 'success', summary: 'Succès', detail: 'Évaluation annulée avec succès', life: 3000});
  }
}

const reactiverEval = async () => {
  try {
    const paramsEtudiantsNotes = {
      evaluationId: props.evaluation.id,
    }
    const etudiantsNotes = await getEtudiantNotesService(paramsEtudiantsNotes)
    // si toutes les notes ont note !== null
    let allNotesFilled = true;
    etudiantsNotes.forEach(async (etudiantNote) => {
      if (etudiantNote.note === null) {
        allNotesFilled = false;
      }
    })
    let etat;
    if(allNotesFilled) {
      etat = 'completee';
    } else {
     if(props.evaluation.dateFin > new Date()) {
      etat = 'planifiee';
     } else {
      etat = 'initialisee';
     }
    }
    const dataEval = {
      etat: etat,
    }
    await updateEvaluationService(props.evaluation.id, dataEval);
    emit('reactiver-eval');
  } catch (error) {
    console.error('Erreur lors de la réactivation de l\'évaluation:', error);
    toast.add({severity: 'error', summary: 'Erreur', detail: 'Erreur lors de la réactivation de l\'évaluation', life: 3000});
  } finally {
    toast.add({severity: 'success', summary: 'Succès', detail: 'Évaluation réactivée avec succès', life: 3000});
  }
}
</script>

<template>
  {{ evaluation.id }}
  <div class="card m-0 py-2 px-4">
    <div class="flex flex-col gap-4">
      <div class="flex justify-between items-center gap-4">
        <div class="flex items-center gap-2">
          <div class="text-lg font-bold">
            {{ evaluation.typeIcon }} {{ evaluation.libelle }}
          </div>
          <Message v-if="evaluation.type" :severity="getSeverity(evaluation.type)" size="small">
            {{ evaluation.type }}
          </Message>
          <Message v-if="evaluation.typeGroupe" severity="secondary" size="small">
            {{ evaluation.typeGroupe }}
          </Message>
        </div>
        <div class="flex items-center justify-end gap-4">
          <div class="text-sm text-muted-color">
            {{ evaluation.scolEvaluationRattrapages?.length || 0 }} demandes de rattrapages
          </div>
          <Message
          :severity="evaluation.etatSeverity || 'error'"
          :icon="evaluation.etatIcon || 'pi pi-exclamation-triangle'"
          size="small">
          {{ getEtatLabel(evaluation.etat) }}
        </Message>
      </div>
    </div>

    <div>
      <div class="flex justify-between items-center gap-4">
        <div class="text-sm flex items-center gap-1"><i class="pi pi-users"></i>Notes saisies</div>
        <div class="text-sm flex items-center gap-1">
          <span class="font-bold">{{ evaluation.entered }}/{{ evaluation.total }}</span>
          ({{ evaluation.percent }}%)
        </div>
      </div>
      <ProgressBar :value="evaluation.percent" class="!h-3"></ProgressBar>
    </div>

    <div class="flex justify-between gap-2">
      <div class="flex items-center gap-2 flex-wrap">
        <div>Saisie autorisée :</div>
        <div v-if="evaluation.personnelAutorise?.length > 0">
          <div v-for="personnel in evaluation.personnelAutorise" :key="personnel.id || personnel.display"
          class="border border-neutral-200 dark:border-neutral-600 rounded-md px-3 py-1 text-sm bg-neutral-100 dark:bg-neutral-800 flex items-center gap-2">
          {{ personnel.display }}
        </div>
      </div>
      <div v-else class="border border-neutral-200 dark:border-neutral-600 rounded-md px-3 py-1 text-sm bg-neutral-100 dark:bg-neutral-800 flex items-center gap-2">
        Aucun personnel autorisé
      </div>
    </div>

    <div v-if="evaluation.date" class="text-sm text-neutral-500">
      Évaluation programée le :
      {{ evaluation.date ? (new Date(evaluation.date).getDate() + '/' + (new Date(evaluation.date).getMonth() + 1) + '/' + new Date(evaluation.date).getFullYear()) : '' }}
    </div>
    <div v-else class="text-sm text-red-400">
      Évaluation non programée
    </div>
  </div>
</div>

<PermissionGuard
  :permission="[
    { permission: 'canManageEvaluation', context: { evaluation } },
    'canViewAdministration'
  ]"
  :requireAll="false"
>
  <Divider />
  <div class="flex justify-between items-center gap-4">
    <div class="flex items-center justify-start gap-2">
      <Button v-if="evaluation.etat === 'non_initialisee'" label="Initialiser" icon="pi pi-plus" outlined severity="primary" size="small" @click="onOpen('edit', 'Initialiser l\'évaluation')" />
      <Button v-if="evaluation.etat !== 'non_initialisee' && evaluation.etat !== 'annulee' && evaluation.etat !== 'publiee'" :label="evaluation.modifiable !== true ? 'Voir les notes' : 'Saisir les notes'" icon="pi pi-file-edit" outlined severity="primary" size="small" @click="onOpen('saisie', 'Saisie des notes')"/>
      <Button v-if="evaluation.etat !== 'non_initialisee' && evaluation.etat !== 'annulee' && evaluation.etat !== 'publiee'" label="Modifier" icon="pi pi-pencil" outlined severity="warn" size="small" @click="onOpen('edit', 'Édition de l\'évaluation')" :disabled="evaluation.modifiable !== true"/>
      <Button v-if="evaluation.etat !== 'non_initialisee' && !props.inStatsContext" label="Statistiques" icon="pi pi-chart-line" outlined severity="info" size="small" @click="onOpen('stat', 'Statistiques de l\'évaluation')" />
      <Button v-if="evaluation.etat == 'annulee' && !props.inStatsContext" label="Réactiver" icon="pi pi-play-circle" outlined severity="success" size="small" @click="reactiverEval()" />
      <Button v-if="evaluation.etat === 'completee'" label="Publier les résultats" icon="pi pi-plus" outlined severity="success" size="small" v-tooltip.top="'Publier les résultats aux étudiants'" @click="showConfirmDialog()"/>
      <Button v-if="evaluation.etat !== 'non_initialisee' && evaluation.etat !== 'annulee'" label="Annuler" icon="pi pi-times" outlined severity="danger" size="small" v-tooltip.top="'Annuler l\'évaluation'" @click="showCancelDialog()"/>
    </div>
    <div v-if="evaluation.etat !== 'non_initialisee' && evaluation.etat !== 'annulee' && evaluation.etat !== 'publiee'" class="flex items-center justify-end gap-4">
      <div class="flex items-center justify-end gap-1" v-tooltip.top="evaluation.visible ? 'Masquer l\'évaluation aux étudiants' : 'Rendre visible l\'évaluation aux étudiants'">
        <i :class="evaluation.visible ? 'pi pi-eye text-green-500' : 'pi pi-eye-slash text-red-400'"></i>
        <span class="text-sm">{{ evaluation.visible ? 'Visible' : 'Masquée' }}</span>
        <ToggleSwitch v-model="evaluation.visible" @change="onToggleVisibility"/>
      </div>
      <div class="flex items-center justify-end gap-1" v-tooltip.top="evaluation.modifiable !== true ? 'Activer les modifications pour pouvoir saisir les notes et modifier les infos' : 'Bloquer la saisie des notes et les modifications'">
        <i :class="evaluation.modifiable ? 'pi pi-lock-open text-green-500' : 'pi pi-lock text-red-400'"></i>
        <span class="text-sm">{{ evaluation.modifiable ? 'Modifiable' : 'Non-modifiable' }}</span>
        <ToggleSwitch v-model="evaluation.modifiable" @change="onToggleEdit"/>
      </div>
    </div>
  </div>

  <Dialog :header="dialogHeader" v-model:visible="showDialog" modal :style="{ width: '70vw' }" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">
    <component v-if="showDialog" :is="dialogComponent" :evaluationId="evaluation.id" :semestreId="semestreId" @saved="onEvaluationSaved" @close="onEvaluationClosed"/>
  </Dialog>
</PermissionGuard>
</div>
</template>
