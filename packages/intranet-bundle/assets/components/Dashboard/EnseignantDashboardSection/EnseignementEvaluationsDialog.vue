<script setup>
import {useRouter} from 'vue-router';
import {useEnseignementPage} from './useEnseignementPage';
import {defineProps, defineEmits, ref, watch, computed} from 'vue';
import { getEvaluationsService, updateEvaluationService } from '@requests';
import { ListSkeleton } from '@components';
import EvaluationCard from "@/components/Evaluation/EvaluationCard.vue";
import {useUsersStore} from '@stores';
import EvaluationForm from "@/components/Evaluation/EvaluationForm.vue";
import EvaluationSaisieNotesForm from "@/components/Evaluation/EvaluationSaisieNotesForm.vue";
import EvaluationListeInitForm from "../../Evaluation/EvaluationListeInitForm.vue";
import EvaluationStatistiques from "../../Evaluation/EvaluationStatistiques.vue";

const usersStore = useUsersStore();
const hasError = ref(false);
const evaluations = ref([]);
const selectedEvaluation = ref(null);
const dialogMode = ref('edit'); // 'init' | 'edit' | 'saisie' | 'stat'
const dialogHeader = ref('');
const showDialog = ref(false);
const isLoadingEvaluations = ref(false);
const userId = usersStore.userId;
const semestre = computed(() => props.enseignement?.semestre);

const props = defineProps({
  visible: Boolean,
  enseignement: { type: Object, default: null },
  anneeUniversitaire: { type: [Number, String], default: null },
});
const router = useRouter();
const emit = defineEmits(['update:visible']);

watch(
    () => [props.visible, props.enseignement?.id, props.anneeUniversitaire],
    async ([visible, enseignementId]) => {
      if (visible && enseignementId) {
        await getEvaluations();
        return;
      }

      if (!visible) {
        evaluations.value = [];
      }
    },
    { immediate: true }
);

const getEvaluations = async () => {
  isLoadingEvaluations.value = true;
  hasError.value = false;
  try {
    const params = {
      enseignement: props.enseignement?.id,
      anneeUniversitaire: props.anneeUniversitaire,
      personnel: userId,
    };
    const response = await getEvaluationsService(params);
    const evals = Array.isArray(response) ? response : [];
    evals.forEach(calcEvaluationProgress);
    evaluations.value = evals;
  } catch (error) {
    hasError.value = true;
    evaluations.value = [];
    console.error('Erreur lors de la récupération des évaluations :', error);
  } finally {
    isLoadingEvaluations.value = false;
  }
};


const updateEvaluationVisibility = async (evaluation) => {
  try {
    await updateEvaluationService(evaluation.id, { visible: evaluation.visible }, true);
  } catch (error) {
    hasError.value = true;
    console.error('Error updating evaluation visibility:', error);
  }
}

const updateEvaluationEdit = async (evaluation) => {
  try {
    await updateEvaluationService(evaluation.id, { modifiable: evaluation.modifiable }, true);
  } catch (error) {
    hasError.value = true;
    console.error('Error updating evaluation modifiable:', error);
  }
};

// Choix du composant selon le mode
const dialogComponent = computed(() => {
  return dialogMode.value === 'saisie' ? EvaluationSaisieNotesForm : dialogMode.value === 'edit' ? EvaluationForm : dialogMode.value === 'initAll' ? EvaluationListeInitForm : dialogMode.value === 'stat' ? EvaluationStatistiques : null;
});

// Ouvre le dialog en passant l'id de l'évaluation et le mode ('init'|'edit'|'saisie'|'stat')
const openEvaluationDialog = (evaluationId, mode = 'edit', header) => {
  if (evaluationId) {
    selectedEvaluation.value = evaluationId;
  }
  dialogMode.value = mode;
  dialogHeader.value = `${header || (mode === 'saisie' ? 'Saisie des notes' : mode === 'init' ? 'Initialiser l\'évaluation' : 'Modifier l\'évaluation')}`;
  showDialog.value = true;
};

const onEvaluationClosed = async () => {
  showDialog.value = false;
  selectedEvaluation.value = null;
  // rafraîchir la liste des enseignements/évaluations pour le semestre courant
  await getEvaluations();
};
const onEvaluationSaved = async () => {
  await getEvaluations();
};

const calcEvaluationProgress = (evaluation) => {
  // Ne compter que les notes existantes et dont la propriété `note` n'est pas null
  const notesExistantes = Array.isArray(evaluation?.notes) ? evaluation.notes.filter(n => n != null) : [];
  evaluation.total = notesExistantes.length;
  evaluation.entered = notesExistantes.filter(n => n.note !== null && n.note !== undefined).length;
  evaluation.percent = evaluation.total > 0 ? Math.round((evaluation.entered / evaluation.total) * 100) : 0;
};
const {destination, accessible} = useEnseignementPage('evaluations', () => props.enseignement);
</script>

<template>
  <Dialog :visible="visible" @update:visible="emit('update:visible', $event)" modal
          :header="`Évaluations · ${enseignement?.codeEnseignement} - ${enseignement?.libelle}`"
          class="w-[90vw] md:w-[85vw]">
    <ListSkeleton v-if="isLoadingEvaluations" class="card-body"/>
    <div v-else class="flex flex-col gap-2">
      <Message v-if="hasError" severity="error" class="w-fit p-4" icon="pi pi-times-circle">
        Impossible de récupérer les évaluations.
      </Message>
      <div v-else-if="evaluations.length !== 0" class="flex flex-col gap-2">
        <EvaluationCard
            v-for="evaluation in evaluations"
            :key="evaluation.id"
            :evaluation="evaluation"
            :semestreId="semestre?.id"
            @open-dialog="openEvaluationDialog"
            @update-visibility="updateEvaluationVisibility"
            @update-edit="updateEvaluationEdit"
            @saved="onEvaluationSaved"
            @cancel-eval="onEvaluationSaved"
            @reactiver-eval="onEvaluationSaved"
        />
      </div>
      <div v-else class="flex justify-center">
        <Message severity="warn" class="w-fit p-4" icon="pi pi-exclamation-triangle">
          Aucune évaluation trouvée.
        </Message>
      </div>
    </div>
    <template #footer>
      <div class="w-full border-t border-surface-200 dark:border-surface-700 bg-surface-50 dark:bg-surface-800/50 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3 text-left">
          <span class="flex items-center justify-center shrink-0 w-10 h-10 rounded-lg bg-primary/10 text-primary" aria-hidden="true"><i class="pi pi-pencil" /></span>
          <div>
            <p class="m-0! text-sm font-semibold text-color">Gestion des évaluations</p>
            <p class="m-0! mt-1! text-xs text-muted-color">Retrouvez les évaluations et la saisie des notes.</p>
          </div>
        </div>
        <Button v-if="accessible" label="Voir les évaluations" icon="pi pi-arrow-right" icon-pos="right" class="w-full sm:w-auto shrink-0" severity="primary" @click="router.push(destination)" />
      </div>
    </template>
  </Dialog>

  <Dialog :header="dialogHeader" v-model:visible="showDialog" modal :style="{ width: '70vw' }"
          :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">
    <component v-if="showDialog" :is="dialogComponent" :evaluationId="selectedEvaluation"
               :enseignements="[enseignement]" :semestreId="semestre?.id"
               @saved="onEvaluationSaved" @close="onEvaluationClosed"/>
  </Dialog>
</template>
