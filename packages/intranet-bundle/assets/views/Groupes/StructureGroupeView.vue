<script setup>
import { onMounted, ref, computed, watch } from "vue";
import {getAnneeService, getGroupesService, getSemestresService} from "@requests";
import { ErrorView, SimpleSkeleton, GlobalLoader } from "@components";
import { useUsersStore, useSemestreStore, useAnneeStore } from "@stores";
import {typesGroupes} from "@config/uniServices.js";
import {useRoute} from "vue-router";

const route = useRoute();
const userStore = useUsersStore();
const semestreStore = useSemestreStore();
const anneeStore = useAnneeStore();
const semestres = ref([]);
const semestre = ref({});
const annees = ref([]);
const annee = ref({});
const groupes = ref({});
const hasError = ref(false);
const isLoadingAnnee = ref(true);
const isLoadingSemestres = ref(true);
const selectedGroupe = ref(null);
const isLoadingGroupes = ref(true);
const departementId = userStore.departementDefaut.id;

onMounted(async () => {
  await getAnnees();
  await getAnnee();
  await getSemestres();
  // Sélectionner le premier semestre de l'année par défaut
  if (semestres.value.length > 0 && !semestre.value.id) {
    semestre.value = semestres.value[0];
  }
});

watch(() => semestreStore.semestre, (newSemestre) => {
  semestre.value = newSemestre;
});

// watcher pour relancer getGroupes quand semestre change
watch(semestre, async (newSemestre, oldSemestre) => {
  if (newSemestre.id !== oldSemestre.id) {
    await getGroupes();
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


watch(groupes, (newVal) => {
  const types = Object.keys(newVal);
  if (types.length > 0 && !selectedGroupe.value) {
    selectedGroupe.value = types[0];
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

const getGroupes = async () => {
  isLoadingGroupes.value = true;
  hasError.value = false;
  try {
    const params = {
      semestre: semestre.value.id,
    };
    const rawGroupes = await getGroupesService(params, '/structure');

    // Trier les groupes par type dans des tableaux séparés
    const groupesParType = {};
    typesGroupes.forEach(type => {
      groupesParType[type.value] = rawGroupes.filter(groupe => groupe.type === type.value);
    });
    // si un type n'a pas de groupe, on le supprime
    for (const type in groupesParType) {
      if (groupesParType[type].length === 0) {
        delete groupesParType[type];
      }
    }
    groupes.value = groupesParType;
  } catch (error) {
    hasError.value = true;
    console.error("Erreur lors de la récupération des groupes :", error);
  } finally {
    isLoadingGroupes.value = false;
    console.log(groupes.value)
  }
};

const synchroApogee = async () => {
  isLoadingGroupes.value = true;
  hasError.value = false;
  try {

  } catch (error) {
    hasError.value = true;
    console.error("Erreur lors de la synchronisation des groupes :", error);
  } finally {
    isLoadingGroupes.value = false;
  }
};
</script>

<template>
  <div class="card min-h-full">
    <div class="flex justify-between items-end w-full">
      <div>
        <h2 class="text-2xl! mb-0!font-bold flex items-end gap-2">
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
        <Button
            @click="synchroApogee()"
            label="Synchronisation depuis Apogée"
            icon="pi pi-refresh"/>
      </div>
    </div>
    <Divider />
    <ErrorView v-if="hasError" />
    <div v-else class="flex items-start justify-center gap-2">
      <GlobalLoader v-if="isLoadingGroupes" class="w-full h-64" />
      <div v-else v-for="semestre in semestres" :key="semestre.id" class="p-4 w-full card">
        <h3 class="text-xl! font-black mb-4">Semestre {{ semestre.libelle }}</h3>
        <div v-for="(groupesType, type) in groupes" :key="type" class="mb-4 bg-primary-300/20 rounded-md p-2">
          <h4 class="text-lg! font-bold mb-2">Type de groupe {{ type }}</h4>
          <div class="flex flex-wrap gap-4">
            <DataTable
                :value="groupesType"
                :empty-message="'Aucun groupe de type ' + type + ' pour ce semestre.'"
                striped-rows
                class="w-full">
              <Column field="ordre" header="Ordre">
                <template #body="slotProps">
                  <span class="text-muted-color">{{ slotProps.data.ordre !== null ? slotProps.data.ordre : '-' }}</span>
                </template>
              </Column>
              <Column field="libelle" header="Libellé" class="font-black"/>
              <Column field="codeApogee" header="Code Apogée" class="font-bold"/>
              <Column field="parent.libelle" header="Parent" class="text-muted-color"/>
              <Column field="parcours.libelle" header="Parcours" />
            </DataTable>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped></style>
