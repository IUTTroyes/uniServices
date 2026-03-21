<script setup>
import {computed, onMounted, ref} from "vue";
import {getAllDepartementsService, updateDepartementService} from "@requests";
import {ErrorView} from "@components";
import ButtonDelete from "@components/components/Buttons/ButtonDelete.vue";
import ButtonSave from "@components/components/Buttons/ButtonSave.vue";

const hasError = ref(false)

const isLoadingDepartement = ref(false);
const departements = ref([])

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
    departements.value = await getAllDepartementsService('/administration');
  } catch (error) {
    console.error("Erreur lors de la récupération des départements:", error);
    hasError.value = true
  } finally {
    console.log(departements.value)
    isLoadingDepartement.value = false
  }
};

const updateDepartement = async (departement) => {
  try {
    const data = {
      actif: !departement.actif,
    }
    await updateDepartementService(departement.id, data);
  } catch (error) {
    console.error("Erreur lors de la mise à jour du département:", error);
    hasError.value = true
  } finally {
    await getDepartements();
  }
}
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
        <Column header="Actions" :showFilterMenu="false">
          <template #body="slotProps">
            <ButtonDelete v-if="slotProps.data.actif" tooltip="Suspendre le département" icon="pi pi-trash" @confirm-delete="updateDepartement(slotProps.data)" />
            <ButtonSave v-else tooltip="Activer le département" icon="pi pi-check" @confirm-save="updateDepartement(slotProps.data)" :disabled="slotProps.data.actif" />
          </template>
        </Column>
      </DataTable>
    </template>
  </div>
</template>

<style scoped>

</style>
