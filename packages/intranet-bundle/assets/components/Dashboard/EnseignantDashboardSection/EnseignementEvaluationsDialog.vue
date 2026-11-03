<script setup>
import { defineProps, defineEmits, ref, onMounted } from 'vue';
import { getEvaluationsService } from '@requests';
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

onMounted(async () => {
  if (props.visible && props.enseignement) {
    await getEvaluations();
  }
});

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
  </Dialog>
</template>
