<script setup>
import {computed, onMounted, ref, watch} from 'vue';
import {ErrorView} from "@components";
import {useToast} from "primevue/usetoast";
import {useAnneeStore, useEnseignementsStore, useUsersStore} from "@stores";
import {
  getPersonnelPreviService,
  getAnneeUnivPreviService
} from "@requests";

const toast = useToast();
const hasError = ref(false);
const anneeUniv = localStorage.getItem('selectedAnneeUniv') ? JSON.parse(localStorage.getItem('selectedAnneeUniv')) : { id: null };
const usersStore = useUsersStore();
const enseignementStore = useEnseignementsStore();
const departementId = usersStore.departementDefaut.id;
const isLoadingPrevisionnel = ref(true);
const previPersonnels = ref([]);
const previPersonnelsOriginal = ref([]);

onMounted(async () => {
  await getPreviPersonnels();
});

const getPreviPersonnels = async () => {
  try {
    const params = {
      departement: departementId,
      anneeUniversitaire: anneeUniv.id,
    }
    previPersonnels.value = await getAnneeUnivPreviService(params);
    previPersonnelsOriginal.value = JSON.parse(JSON.stringify(previPersonnels.value));

  } catch (error) {
    hasError.value = true;
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors du chargement des données.' });
  } finally {
    console.log(previPersonnels.value)
    isLoadingPrevisionnel.value = false;
  }
}
</script>

<template>
  <ErrorView v-if="hasError" />
  <div v-else class="px-4 flex flex-col">

  </div>
</template>
