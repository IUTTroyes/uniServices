<script setup>
import { onMounted, ref, watch } from "vue";
import {SimpleSkeleton, ErrorView} from "@components";
import {useAnneeStore, useAnneeUnivStore, useSemestreStore, useUsersStore} from "@stores";
import {getAnneeService, getSemestresService, getEtudiantAbsencesService} from "@requests";
import {useRoute} from "vue-router";
import {Button} from "primevue";

const route = useRoute();
const hasError = ref(false);
const anneeUniv = localStorage.getItem('selectedAnneeUniv') ? JSON.parse(localStorage.getItem('selectedAnneeUniv')) : { id: null };
const usersStore = useUsersStore();
const departementId = usersStore.departementDefaut.id;
const semestreStore = useSemestreStore();
const anneeStore = useAnneeStore();
const semestres = ref([]);
const isLoadingSemestres = ref(true);
const semestre = ref({});
const annees = ref([]);
const annee = ref({});
const isLoadingAnnee = ref(true);

const absences = ref([]);
const isLoadingAbsences = ref(true);

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
    } finally {
      console.log(annees.value)
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

watch(() => semestreStore.semestre, (newSemestre) => {
  semestre.value = newSemestre;
});

// watcher pour relancer getGroupes quand semestre change
watch(semestre, async (newSemestre, oldSemestre) => {
  if (newSemestre.id !== oldSemestre.id) {
    await getAbsences();
  }
});

// watcher pour relancer getSemestres quand annee change
watch(annee, async (newAnnee, oldAnnee) => {
  if (newAnnee.id !== oldAnnee.id) {
    await getSemestres();
    // si le semestre sélectionné n'est pas dans la nouvelle liste, on sélectionne le premier de la liste
    if (!semestres.value.some(s => s.id === semestre.value.id)) {
      semestre.value = semestres.value[0] || {};
    }
    await anneeStore.setSelectedAnnee(newAnnee)
  }
});

const getAbsences = async () => {
  try {
    const params = {
      semestre: semestre.value.id,
      anneeUniversitaire: anneeUniv.id,
    }
    await getEtudiantAbsencesService(params, '/administration');
  } catch (error) {
    hasError.value = true;
    console.error("Erreur lors de la récupération des absences :", error);
  } finally {
    isLoadingAbsences.value = false;
  }
}
</script>

<template>
  <div class="card min-h-full">
    <div class="mb-6">
      <div class="flex justify-between items-end w-full mb-6">
        <div>
          <h2 class="text-2xl! mb-0! font-bold flex items-end gap-2">
            Liste des absences
          </h2>
        </div>
        <SimpleSkeleton v-if="isLoadingSemestres" class="!w-60 !h-10"></SimpleSkeleton>
        <div v-else class="flex gap-4">
          <Select class="w-60" v-model="annee" option-label="libelle" :options="annees">
            <template #value>
              {{ annee?.libelle || "Changer d'année" }}
            </template>
          </Select>
          <Select class="w-60" v-model="semestre" option-label="libelle" :options="semestres">
            <template #value>
              {{ semestre?.libelle || "Changer de semestre" }}
            </template>
          </Select>
        </div>
      </div>
      <div class="w-full flex justify-end items-center">
        <Button label="Créer une absence" icon="pi pi-plus" @click="getAbsences()" severity="primary"/>
      </div>
    </div>

    <Message severity="info" icon="pi pi-info-circle" class="w-full flex justify-center" v-if="!isLoadingAbsences && (!absences || absences.length === 0)">
      Aucune absence trouvée pour ce semestre.
    </Message>
    <DataTable
        v-else
        :value="absences"
        striped-rows
        class="w-full"
    >
      <Column field="id" header="id" />
    </DataTable>
  </div>
</template>

<style scoped>

</style>
