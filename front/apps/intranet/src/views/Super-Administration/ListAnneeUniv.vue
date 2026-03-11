<script setup>
import {ref, onMounted, computed} from "vue";
import { useAnneeUnivStore } from "@stores";
import {ErrorView} from "@components";
import ButtonInfo from "@components/components/Buttons/ButtonInfo.vue";
import ButtonEdit from "@components/components/Buttons/ButtonEdit.vue";
import ButtonDelete from "@components/components/Buttons/ButtonDelete.vue";
import ButtonSave from "@components/components/Buttons/ButtonSave.vue";

const hasError = ref(false);

const anneeUnivStore = useAnneeUnivStore();
const isLoadingAnneesUniv = ref(false);
const anneesUniv = ref([]);

const page = ref(0);
const rowOptions = [5, 10, 20];
const offset = computed(() => limit.value * page.value);
const limit = ref(rowOptions[0]);

onMounted(async () => {
  await getAnneesUniv();
});

const getAnneesUniv = async () => {
  try {
    isLoadingAnneesUniv.value = true;
    await anneeUnivStore.getAllAnneesUniv();
    anneesUniv.value = await anneeUnivStore.anneesUniv;
  } catch (error) {
    console.error("Erreur lors de la récupération des années universitaires:", error);
    hasError.value = true;
  } finally {
    isLoadingAnneesUniv.value = false;
  }
};
</script>

<template>
  <div class="card">
    <div class="card-title mb-8">
      <h1 class="text-2xl font-bold">Nouvelle Année Universitaire</h1>
      <p class="text-muted-color">Créez une nouvelle année universitaire en remplissant le formulaire ci-dessous.</p>
    </div>

    <ErrorView v-if="hasError"/>
    <template v-else>
      <DataTable
        :value="anneesUniv"
        striped-rows
        paginator
        :first="offset"
        :rows="limit"
        :rowsPerPageOptions="rowOptions"
        :loading="isLoadingAnneesUniv"
      >
        <Column field="libelle" header="Libellé"></Column>
        <Column field="annee" header="Année"></Column>
        <Column field="actif" header="Actif" :body="slotProps => slotProps.data.actif">
            <template #body="slotProps">
              <i :class="slotProps.data.actif ? 'pi pi-check text-green-500' : 'pi pi-times text-red-500'"></i>
            </template>
          </Column>
        <Column field="commentaire" header="Commentaire"></Column>
        <Column :showFilterMenu="false" style="min-width: 12rem">
          <template #body="slotProps">
            <ButtonInfo tooltip="Voir les diplômes" icon="pi pi-book" @click="" />
            <ButtonEdit tooltip="Modifier l'année universitaire" icon="pi pi-pencil" @click="" />
            <ButtonDelete tooltip="Supprimer l'année universitaire" icon="pi pi-trash" @click="" />
            <ButtonSave tooltip="Activer l'année universitaire" icon="pi pi-check" @click="" :disabled="slotProps.data.actif" />
          </template>
        </Column>
      </DataTable>
    </template>
  </div>
</template>

<style scoped>

</style>
