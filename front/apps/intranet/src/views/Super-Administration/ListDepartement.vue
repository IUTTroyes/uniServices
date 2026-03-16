<script setup>
import {computed, onMounted, ref} from "vue";
import {getAllDepartementsService} from "@requests";
import {ErrorView} from "@components";

const hasError = ref(false)

const isLoadingDepartement = ref(false);
const departements = ref({})

const page = ref(0);
const rowOptions = [10, 20, 30];
const offset = computed(() => limit.value * page.value);
const limit = ref(rowOptions[0]);

onMounted(async () => {
await getDepartements();
})

const getDepartements = async () => {
  try {
    isLoadingDepartement.value = true
    departements.value = await getAllDepartementsService();
  } catch (error) {
    console.error("Erreur lors de la récupération des départements:", error);
    hasError.value = true
  } finally {
    isLoadingDepartement.value = false
  }
};
</script>

<template>
  <div class="card">
    <div class="card-title mb-8">
      <h1 class="text-2xl font-bold">Liste des départements</h1>
      <p class="text-muted-color">Gérer les départements.</p>
    </div>

    <ErrorView v-if="hasError"/>
    <template v-else>
      <DataTable
        :value="departements"
        striped-rows
        paginator
        :first="offset"
        :rows="limit"
        :rowsPerPageOptions="rowOptions"
        >
        <Column field="id" header="ID" :sortable="true"/>
        <Column field="libelle" header="Libellé" :sortable="true"/>
      </DataTable>
    </template>
  </div>
</template>

<style scoped>

</style>
