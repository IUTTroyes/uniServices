<script setup>
import { onMounted, ref, watch } from "vue";
import {SimpleSkeleton, ErrorView, validationRules, ValidatedInput, ListSkeleton} from "@components";
import {useAnneeStore, useSemestreStore, useUsersStore} from "@stores";
import {getAnneeService, getSemestresService, getGroupesService, getEnseignementsService} from "@requests";
import {useRoute} from "vue-router";
import {Button} from "primevue";

const route = useRoute();
const hasError = ref(false);
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
const groupes = ref([]);
const isLoadingGroupes = ref(true);
const selectedGroupe = ref([]);
const typesGroupes = ref([]);
const isLoadingTypesGroupes = ref(true);
const selectedTypeGroupe = ref(null);
const enseignements = ref([]);
const isLoadingEnseignements = ref(false);
const date = new Date();
const heure = ref(null);
const heures = ref([]);


onMounted(async () => {
  await getAnnees();
  await getAnnee();
  await getSemestres();
  // Sélectionner le semestre actif par défaut
  if (semestres.value.length > 0 && !semestre.value.id) {
    semestre.value = semestres.value.find(s => s.actif) || semestres.value[0];
  }
  await constructHeures();
});

const constructHeures = () => {
  // construire un tableau qui va de 8h00 à 19h45 avec une incrémentation de 15min
  const startHour = 8;
  const endHour = 20;
  const endMinute = 30;
  for (let hour = startHour; hour <= endHour; hour++) {
    for (let minute = 0; minute < endMinute; minute += 15) {
      const heureStr = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
      heures.value.push(heureStr);
    }
  }
}

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

// watcher pour relancer getTypesGroupes quand semestre change
watch(semestre, async (newSemestre, oldSemestre) => {
  if (newSemestre.id !== oldSemestre.id) {
    await getEnseignements();
    await getTypesGroupes();
  }
});

// watcher pour relancer getSemestres quand annee change
watch(annee, async (newAnnee, oldAnnee) => {
  if (newAnnee.id !== oldAnnee.id) {
    await getSemestres();
    // si le semestre sélectionné n'est pas dans la nouvelle liste, on sélectionne le premier de la liste
    if (!semestres.value.some(s => s.id === semestre.value.id)) {
      semestre.value = semestres.value.find(s => s.actif) || semestres.value[0];
    }
    await anneeStore.setSelectedAnnee(newAnnee)
  }
});

watch(selectedTypeGroupe, async (newTypeGroupe, oldTypeGroupe) => {
  if (newTypeGroupe !== oldTypeGroupe) {
    await getGroupes();
  }
})

const getTypesGroupes = async () => {
  try {
    isLoadingTypesGroupes.value = true;
    typesGroupes.value = semestre.value.typesGroupe;
  } catch (error) {
    console.error('Erreur lors du chargement des groupes:', error);
    hasError.value = true;
  } finally {
    selectedTypeGroupe.value = typesGroupes.value[0];
    isLoadingTypesGroupes.value = false;
  }
};

const getGroupes = async () => {
  try {
    isLoadingGroupes.value = true;
    console.log(selectedTypeGroupe.value);
    const params = {
      semestre: semestre.value.id,
      type: selectedTypeGroupe.value
    }
    groupes.value = await getGroupesService(params);
  } catch (error) {
    console.error('Erreur lors du chargement des groupes:', error);
    hasError.value = true;
  } finally {
    isLoadingGroupes.value = false;
  }
}

const getEnseignements = async () => {
  try {
    isLoadingEnseignements.value = true;
    const params = {
      semestre: semestre.value.id,
    }
    enseignements.value = await getEnseignementsService(params);
    console.log(enseignements.value)
  } catch (error) {
    console.error('Erreur lors du chargement des enseignements:', error);
    hasError.value = true;
  } finally {
    isLoadingEnseignements.value = false;
  }
}
</script>

<template>
  <div class="card min-h-full">
    <div class="flex justify-between items-center w-full mb-6">
      <div>
        <h2 class="text-2xl! mb-0! font-bold flex items-end gap-2">
          Saisir des absences
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

    <div>
      <ErrorView v-if="hasError"/>
      <div v-else>
        <div class="card">
          <div class="flex flex-row gap-4">
            <ValidatedInput
                name="libelle"
                label="Ressource"
                type="select"
                :options="enseignements.map(e => ({ label: e.display, value: e.id }))"
                @validation=""
                help-text="Sélectionnez la ressource"
                class="w-full"
            />
            <ValidatedInput
                name="libelle"
                label="Date"
                type="date"
                @validation=""
                help-text="Sélectionnez la date"
                class="w-full"
            />
            <ValidatedInput
                name="libelle"
                label="Heure"
                type="select"
                :options="heures.map(h => ({ label: h, value: h }))"
                @validation=""
                help-text="Sélectionnez l'heure de début du créneau"
                class="w-full"
            />
          </div>
          <ListSkeleton v-if="isLoadingTypesGroupes" class="flex items-center gap-4 w-1/2"></ListSkeleton>
          <Tabs v-else :value="selectedTypeGroupe" scrollable>
            <TabList>
              <Tab v-for="typeGroupe in typesGroupes" :key="typeGroupe" :value="typeGroupe" @click="selectedTypeGroupe = typeGroupe">
                {{ typeGroupe }}
              </Tab>
            </TabList>
          </Tabs>
          <div>
            <SimpleSkeleton v-if="isLoadingGroupes" class="!w-60 !h-10 my-2"></SimpleSkeleton>
            <div v-else class="flex gap-4 my-2">
              <Button v-for="groupe in groupes">
                {{ groupe.libelle }}
              </Button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>

</style>
