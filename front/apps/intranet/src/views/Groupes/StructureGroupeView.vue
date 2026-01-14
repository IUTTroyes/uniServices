<script setup>
import { ref, onMounted, watch } from 'vue'
import {ErrorView, SimpleSkeleton} from "@components";
import { typesGroupes } from '@config/uniServices.js';
import {useSemestreStore} from "@stores";
import { useRoute } from 'vue-router';

const hasError = ref(false);
const route = useRoute();
const semestre = ref({});
const isLoadingSemestre = ref(true);
const groupes = ref([]);
const isLoadingGroupes = ref(true);
const semestreStore = useSemestreStore();

onMounted(() => {
  getSemestre();
});

const getSemestre = async () => {
  isLoadingSemestre.value = true;
  hasError.value = false;
  // Récupération de l'id du semestre dans l'url
  try {
    semestre.value = semestreStore.semestre;
  } catch (error) {
    hasError.value = true;
    console.error("Erreur lors de la récupération du semestre :", error);
  } finally {
    isLoadingSemestre.value = false;
    console.log(semestre.value);
  }
};
</script>

<template>
  <div class="card">
    <h2 class="text-2xl font-bold flex items-end gap-2">Structure des groupes du <SimpleSkeleton v-if="isLoadingSemestre" class="!w-32"></SimpleSkeleton><span v-else>{{semestre.libelle}}</span></h2>
    <em>Créer, modifier et organiser les groupes</em>
    <Divider/>
    <ErrorView v-if="hasError"></ErrorView>

  </div>
</template>

<style scoped>

</style>
