<script setup>
import {onMounted, ref} from 'vue';
import {ErrorView, SimpleSkeleton} from "@components";
import {getEvaluationService} from "@requests/scol_services/evaluationService.js";

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
</script>

<template>
  <ErrorView v-if="hasError"></ErrorView>
  <div v-else>

    <div class="card">
      <div class="flex flex-col gap-4">
        <div class="flex justify-between items-center gap-4">
          <div class="flex items-center gap-2">
            <div class="text-lg font-bold">
              {{ evaluation.typeIcon }} {{evaluation.libelle}}
            </div>
            <Message v-if="evaluation.type" :severity="getSeverity(evaluation.type)" size="small">
              {{evaluation.type}}
            </Message>
            <Message v-if="evaluation.typeGroupe" severity="secondary" size="small">
              {{evaluation.typeGroupe}}
            </Message>
          </div>
          <div>
            <Message
                :severity="evaluation.etat === 'non_initialisee' ? 'error' : evaluation.etat === 'initialisee' ? 'info' : evaluation.etat === 'planifiee' ? 'warn' : evaluation.etat === 'complet' ? 'success' : 'error'"
                :icon="evaluation.etat === 'non_initialisee' ? 'pi pi-exclamation-triangle' : evaluation.etat === 'initialisee' ? 'pi pi-info-circle' : evaluation.etat === 'planifiee' ? 'pi pi-clock'  : evaluation.etat === 'complet' ? 'pi pi-check-circle' : 'pi pi-exclamation-triangle'"
                size="small">
              {{ evaluation.etat === 'non_initialisee' ? 'À initialiser' : evaluation.etat === 'initialisee' ? 'Initialisée' : evaluation.etat === 'planifiee' ? 'À saisir' : evaluation.etat === 'complet' ? 'Complet' : 'Erreur' }}
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

        <div class="flex flex-wrap items-center gap-2">
          <div>Saisie autorisée :</div>
          <div v-if="evaluation.personnelAutorise?.length > 0" v-for="personnel in evaluation.personnelAutorise" class="border border-neutral-200 dark:border-neutral-600 rounded-md px-3 py-1 text-sm bg-neutral-100 dark:bg-neutral-800 flex items-center gap-2">
            {{personnel.display}}
          </div>
          <div v-else class="border border-neutral-200 dark:border-neutral-600 rounded-md px-3 py-1 text-sm bg-neutral-100 dark:bg-neutral-800 flex items-center gap-2">
            Aucun personnel autorisé
          </div>
        </div>
      </div>
    </div>

    <div class="mx-12">
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
    </div>
    <!--    <Chart type="radar" :data="chartData" :options="chartOptions" class="w-full md:w-[30rem]" />-->

  </div>
</template>

<style scoped>

</style>
