<script setup>
import {onMounted, ref} from 'vue';
import {ErrorView, SimpleSkeleton} from "@components";
import {getEvaluationService, updateEvaluationService} from "@requests/scol_services/evaluationService.js";
import EvaluationNotesRepartitionChart from "./EvaluationNotesRepartitionChart.vue";
import EvaluationCard from "@/components/Evaluation/EvaluationCard.vue";

const hasError = ref(false);
const isLoading = ref(true);
const evaluation = ref({});

const props = defineProps({
  evaluationId: {
    type: Number,
    required: true
  },
  semestreId: {
    type: Number,
    required: false
  }
})

onMounted(async () => {
  console.log(hasError.value);
  await getEvaluation();
});

const getEvaluation = async () => {
  try {
    isLoading.value = true;
    evaluation.value = await getEvaluationService(props.evaluationId);
    await calcEvaluationProgress(evaluation.value);
  } catch (error) {
    console.error('Erreur lors du chargement de l\'évaluation:', error);
  } finally {
    isLoading.value = false;
    console.log(evaluation.value);
  }
};

const calcEvaluationProgress = (evaluation) => {
  // Ne compter que les notes existantes et dont la propriété `note` n'est pas null
  const notesExistantes = Array.isArray(evaluation?.notes) ? evaluation.notes.filter(n => n != null) : [];
  evaluation.total = notesExistantes.length;
  evaluation.entered = notesExistantes.filter(n => n.note !== null && n.note !== undefined).length;
  evaluation.percent = evaluation.total > 0 ? Math.round((evaluation.entered / evaluation.total) * 100) : 0;
  if (evaluation.percent === 100) {
    evaluation.etat = 'complet';
  }
};

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
</script>

<template>
  <ErrorView v-if="hasError"></ErrorView>
  <div v-else>
    <SimpleSkeleton v-if="isLoading" :width="'100%'" :height="'400px'"/>
    <div v-else>
      <EvaluationCard class="mb-8"
                      :evaluation="evaluation"
                      :semestreId="props.semestreId"
                      :useLocalDialog="true"
                      @update-visibility="updateEvaluationVisibility"
                      @update-edit="updateEvaluationEdit"
      />
    </div>

    <Divider></Divider>

    <div class="mx-12 flex flex-col gap-8">
      <div class="flex items-center justify-between gap-4">
        <div class="w-full">
          <div class="text-xl font-bold mb-4">
            Résultats
          </div>
          <div class="flex justify-between items-center gap-4 h-full">
            <div v-for="(stat, key) in evaluation.stats" class="bg-neutral-300 bg-opacity-20 p-4 rounded-lg w-full min-w-48 flex flex-col items-center justify-center">
              <div class="first-letter:uppercase">
                {{ key }}
              </div>
              <div class="text-lg font-bold">
                {{ stat }}
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="w-full">
        <div class="text-xl font-bold mb-4">
          Répartition des notes
        </div>
        <div class="flex justify-between items-center gap-4">
          <EvaluationNotesRepartitionChart :notes="evaluation.notes" class="w-2/3"/>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>

</style>
