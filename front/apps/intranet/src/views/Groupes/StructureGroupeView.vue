<script setup>
import { onMounted, ref, watch } from 'vue';
import { ErrorView, SimpleSkeleton } from "@components";
import { useAnneeStore, useUsersStore } from "@stores";
import { getAnneeService, getSemestresService } from "@requests";
import { useRoute } from "vue-router";

const route = useRoute();
const hasError = ref(false);
const anneeStore = useAnneeStore();
const usersStore = useUsersStore();
const departementId = usersStore.departementDefaut.id;
const annee = ref({});
const annees = ref([]);
const semestres = ref([]);
const semestre = ref({});
const isLoadingAnnee = ref(true);
const isLoadingSemestres = ref(true);

onMounted(async () => {
  await getAnnees();
  await getAnnee();
  await getSemestres();
  // Sélectionner le premier semestre de l'année par défaut
  if (semestres.value.length > 0 && !semestre.value.id) {
    semestre.value = semestres.value[0];
  }
});

const getAnnees = async () => {
  if (anneeStore.annees && Array.isArray(anneeStore.annees) && anneeStore.annees.length > 0) {
    annees.value = anneeStore.annees;
  } else {
    try {
      const params = {
        departement: departementId,
        actif: true,
      };
      await anneeStore.getAnneesDepartement(params);
      annees.value = Array.isArray(anneeStore.annees) ? anneeStore.annees : [];
    } catch (error) {
      console.error("Erreur lors de la récupération des années :", error);
      hasError.value = true;
    }
  }
};

const getAnnee = async () => {
  isLoadingAnnee.value = true;
  hasError.value = false;
  // Récupération de l'id de l'année via l'URL
  try {
    const anneeId = route.params.anneeId;
    annee.value = await getAnneeService(anneeId);
    await anneeStore.setSelectedAnnee(annee.value);
  } catch (error) {
    hasError.value = true;
    console.error("Erreur lors de la récupération de l'année :", error);
  } finally {
    isLoadingAnnee.value = false;
  }
};

const getSemestres = async () => {
  isLoadingSemestres.value = true;
  hasError.value = false;
  try {
    const params = {
      annee: annee.value.id,
    };
    semestres.value = await getSemestresService(params, '/mini');
  } catch (error) {
    hasError.value = true;
    console.error("Erreur lors de la récupération des semestres :", error);
  } finally {
    isLoadingSemestres.value = false;
  }
};

// watcher pour relancer getSemestres quand annee change
watch(annee, async (newAnnee, oldAnnee) => {
  if (newAnnee.id !== oldAnnee.id) {
    await getSemestres();
    // si le semestre sélectionné n'est pas dans la nouvelle liste, on sélectionne le premier de la liste
    if (!semestres.value.some(s => s.id === semestre.value.id)) {
      semestre.value = semestres.value[0] || {};
    }
  }
});
</script>

<template>
  <div class="card min-h-full">
    <div class="flex justify-between items-end w-full">
      <div>
        <h2 class="text-2xl font-bold flex items-end gap-2">
          Structure des groupes du
          <SimpleSkeleton v-if="isLoadingSemestres" class="!w-32"></SimpleSkeleton>
          <span v-else>{{ semestre.libelle }}</span>
        </h2>
        <em>Configurer la structure des groupes</em>
      </div>
      <SimpleSkeleton v-if="isLoadingSemestres" class="!w-60 !h-10"></SimpleSkeleton>
      <div v-else class="flex gap-4">
        <Select class="w-60" v-model="annee" option-label="libelle" :options="annees">
          <template #value>
            Changer d'année
          </template>
        </Select>
        <Select class="w-60" v-model="semestre" option-label="libelle" :options="semestres">
          <template #value>
            Changer de semestre
          </template>
        </Select>
      </div>
    </div>
    <Divider />
    <ErrorView v-if="hasError" />
    <div v-else>
    </div>
  </div>
</template>

<style scoped></style>
