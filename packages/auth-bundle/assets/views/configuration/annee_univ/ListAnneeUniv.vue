<script setup>
import {ref, onMounted, computed} from "vue";
import { useRouter } from "vue-router";
import { useAnneeUnivStore } from "@stores";
import {ErrorView, ListSkeleton, ButtonInfo, ButtonEdit, ButtonDelete, ButtonSave} from "@components";
import {deleteAnneeUniversitaireService} from "@requests";

const router = useRouter();
const hasError = ref(false);

const anneeUnivStore = useAnneeUnivStore();
const isLoadingAnneesUniv = ref(false);
const anneesUniv = ref([]);
const currentAnneeUniv = ref(null);

const page = ref(0);
const rowOptions = [5, 10, 20];
const offset = computed(() => limit.value * page.value);
const limit = ref(rowOptions[0]);

onMounted(async () => {
  await getAnneesUniv();
  await getCurrentAnneeUniv();
});

const getCurrentAnneeUniv = async () => {
  try {
    await anneeUnivStore.getCurrentAnneeUniv();
    currentAnneeUniv.value = await anneeUnivStore.anneeUniv;
  } catch (error) {
    console.error("Erreur lors de la récupération de l'année universitaire actuelle:", error);
    hasError.value = true;
  }
}

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

const deleteAnneeUniv = async (anneeUniv) => {
  try {
    await deleteAnneeUniversitaireService(anneeUniv.id, true);
    await getAnneesUniv();
    await getCurrentAnneeUniv();
  } catch (error) {
    console.error("Erreur lors de la suppression de l'année universitaire:", error);
    hasError.value = true;
  }
}

const activateAnneeUniv = async (anneeUniv) => {
  try {
    await anneeUnivStore.activateAnneeUniv(anneeUniv.id);
    anneesUniv.value = anneeUnivStore.anneesUniv;
    currentAnneeUniv.value = anneeUnivStore.anneeUniv;
  } catch (error) {
    console.error("Erreur lors de l'activation de l'année universitaire:", error);
    hasError.value = true;
  }
}

const viewDiplomes = (anneeUniv) => {
  // Redirection vers la page des diplômes de l'année universitaire
  router.push({ path: `/auth/configuration/annee-universitaire/${anneeUniv.id}/diplomes` });
}

const editAnneeUniv = (anneeUniv) => {
  // Redirection vers la page d'édition de l'année universitaire
  router.push({ path: `/auth/configuration/annee-universitaire/${anneeUniv.id}/edit` });
}
</script>

<template>
  <div class="card">
    <div class="card-title mb-8 flex items-center justify-between">
      <div>
        <h1 class="text-2xl! mb-0! font-bold">Années Universitaires</h1>
        <p class="text-muted-color">Gérez les années universitaires.</p>
      </div>
      <Button severity="primary" label="Créer une année universitaire" icon="pi pi-plus" @click="router.push({ name: 'annee-universitaire-new' })"/>
    </div>

    <ErrorView v-if="hasError"/>
    <ListSkeleton v-else-if="isLoadingAnneesUniv" :count="5" />
    <template v-else>
      <Message v-if="!currentAnneeUniv" severity="error" class="mb-4 flex items-center justify-center">
        <i class="pi pi-exclamation-triangle text-red-500 text-3xl mb-2 mr-2"></i>
        <span><strong>Attention !</strong> Aucune année universitaire n'est activée.</span>
      </Message>

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
        <Column header="Actions" :showFilterMenu="false">
          <template #body="slotProps">
            <router-link :to="{ name: 'annee-universitaire-diplomes', params: { id: slotProps.data.id } }">
              <ButtonInfo tooltip="Voir les diplômes" icon="pi pi-book"/>
            </router-link>
            <router-link :to="{ name: 'annee-universitaire-edit', params: { id: slotProps.data.id } }">
              <ButtonEdit tooltip="Modifier l'année universitaire" icon="pi pi-pencil" />
            </router-link>
            <ButtonSave tooltip="Activer l'année universitaire" icon="pi pi-check" @confirm-save="activateAnneeUniv(slotProps.data)" :disabled="slotProps.data.actif" />
            <ButtonDelete tooltip="Supprimer l'année universitaire" icon="pi pi-trash" @confirm-delete="deleteAnneeUniv(slotProps.data)" :disabled="slotProps.data.actif" />
          </template>
        </Column>
      </DataTable>
    </template>
  </div>
</template>

<style scoped>

</style>
