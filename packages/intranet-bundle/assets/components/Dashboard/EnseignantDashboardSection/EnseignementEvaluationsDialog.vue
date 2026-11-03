<script setup>
import { defineProps, defineEmits, ref, watch } from 'vue';
import { getEvaluationsService } from '@requests';
import { ListSkeleton } from '@components';
import {useUsersStore} from '@stores';

const usersStore = useUsersStore();
const evaluations = ref([]);
const isLoadingEvaluations = ref(false);
const userId = usersStore.userId;

const props = defineProps({
  visible: Boolean,
  enseignement: { type: Object, default: null },
  anneeUniversitaire: { type: [Number, String], default: null },
});
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
  try {
    const params = {
      enseignement: props.enseignement?.id,
      anneeUniversitaire: props.anneeUniversitaire,
      personnel: userId,
    };
    evaluations.value = await getEvaluationsService(params);
  } catch (error) {
    console.error('Erreur lors de la récupération des évaluations :', error);
  } finally {
    console.log('evaluations.value', evaluations.value);
    isLoadingEvaluations.value = false;
  }
};
</script>

<template>
  <Dialog :visible="visible" @update:visible="emit('update:visible', $event)" modal
          :header="`Évaluations · ${enseignement?.codeEnseignement || enseignement?.libelle || ''}`"
          class="w-[90vw] md:w-[75vw] lg:w-[50vw]">
    <div class="card-header">
      <h3 class="m-0! text-lg!">Évaluations</h3>
    </div>

    <div class="card-body">
      <div v-if="isLoadingEvaluations" class="flex justify-center items-center h-32">
        <ListSkeleton />
      </div>

      <div v-else-if="evaluations.length === 0" class="text-center text-gray-500">
        Aucune évaluation trouvée pour cet enseignement.
      </div>

      <ul v-else class="list-disc pl-5 space-y-2">
        <li v-for="evaluation in evaluations" :key="evaluation.id">
          <strong>{{ evaluation.label }}</strong> - {{ evaluation.group }} - {{ evaluation.status }}
        </li>
      </ul>
    </div>
  </Dialog>
</template>
