<script setup>
import {onMounted, ref, watch} from 'vue';
import {ErrorView, ListSkeleton, SimpleSkeleton, ArticleSkeleton} from "@components";
import {getEvaluationService, getGroupesService, getEtudiantsService} from "@requests";

const hasError = ref(false);
const isLoadingEvaluation = ref(true);
const evaluation = ref({});
const isLoadingGroupes = ref(true);
const groupes = ref([]);
const selectedGroupe = ref(null);
const isLoadingEtudiants = ref(true);
const etudiants = ref([]);

const props = defineProps({
  evaluationId: {
    type: Number,
    required: true
  },
  semestreId: {
    type: Number,
    required: false
  }
});

onMounted(async () => {
  await getEvaluation();
  await getGroupes();
});

watch(selectedGroupe, async () => {
  if (selectedGroupe.value) {
    await getEtudiants();
  }
})

const getEvaluation = async () => {
  try {
    isLoadingEvaluation.value = true;
    evaluation.value = await getEvaluationService(props.evaluationId);
  } catch (error) {
    console.error('Erreur lors du chargement de l\'évaluation:', error);
  } finally {
    console.log(evaluation.value);
    isLoadingEvaluation.value = false;
  }
};

const getGroupes = async () => {
  try {
    isLoadingGroupes.value = true;
    const params = {
      semestre: props.semestreId,
      type: evaluation.value.typeGroupe,
    };
    groupes.value = await getGroupesService(params);
    if (groupes.value.length > 0) {
      selectedGroupe.value = groupes.value[0];
    }
  } catch (error) {
    console.error('Erreur lors du chargement des groupes:', error);
    hasError.value = true;
  } finally {
    isLoadingGroupes.value = false;
  }
};

const getEtudiants = async () => {
  try {
    isLoadingEtudiants.value = true;
    const params = {
      groupe: selectedGroupe.value.id,
    };
    etudiants.value = await getEtudiantsService(params);
  } catch (error) {
    console.error('Erreur lors du chargement des étudiants:', error);
  } finally {
    console.log(etudiants.value);
    isLoadingEtudiants.value = false;
  }
};
</script>

<template>
  <ErrorView v-if="hasError"></ErrorView>
  <div v-else>
    <ListSkeleton v-if="isLoadingGroupes" class="flex items-center gap-4 w-1/2"></ListSkeleton>
    <Tabs v-else :value="selectedGroupe.id" scrollable>
      <TabList>
        <Tab v-for="groupe in groupes" :key="groupe.libelle" :value="groupe.id" @click="selectedGroupe = groupe">
          {{ groupe.libelle }}
        </Tab>
      </TabList>
    </Tabs>
    <ListSkeleton v-if="isLoadingEtudiants"></ListSkeleton>
    <div v-else class="my-8 flex items-center gap-4 w-1/2">
      <div v-for="etudiant in etudiants" :key="etudiant.id" class="p-4 border
        border-neutral-300 rounded-md dark:border-neutral-600">
        <div class="font-bold">
          {{ etudiant.prenom }} {{ etudiant.nom }}
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>

</style>
