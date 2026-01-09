<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import {ErrorView, SimpleSkeleton} from "@components/index.js";
import Loader from '@components/loader/GlobalLoader.vue'
import { typesGroupes } from '@config/uniServices.js';
import {useSemestreStore, useUsersStore} from "@stores";
import { getSemestresService, getSemestreService, getGroupesService, getEtudiantScolariteSemestresService } from "@requests";
import {useRoute} from "vue-router";

const route = useRoute();
const hasError = ref(false);
const semestre = ref({});
const isLoadingSemestre = ref(true);
const groupes = ref({});
const isLoadingGroupes = ref(true);
const selectedGroupe = ref(null);
const semestreStore = useSemestreStore();
const semestres = ref(semestreStore.semestres);
const isLoadingSemestres = ref(true);
const typesList = computed(() => Object.keys(groupes.value));
const usersStore = useUsersStore();
const departementId = usersStore.departementDefaut.id;
const etudiants = ref([]);
const isLoadingEtudiants = ref(true);
const anneeUniv = localStorage.getItem('selectedAnneeUniv') ? JSON.parse(localStorage.getItem('selectedAnneeUniv')) : { id: null };

onMounted(async () => {
  await getSemestre();
  await getSemestres();
  await getGroupes();
  await getEtudiants();
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

watch(groupes, (newVal) => {
  const types = Object.keys(newVal);
  if (types.length > 0 && !selectedGroupe.value) {
    selectedGroupe.value = types[0];
  }
});

const getSemestre = async () => {
  isLoadingSemestre.value = true;
  hasError.value = false;
  // Récupération de l'id du semestre dans l'url
  try {
    const semestreId = route.params.semestreId;
    semestre.value = await getSemestreService(semestreId, '/mini');
    console.log(semestre.value);
  } catch (error) {
    hasError.value = true;
    console.error("Erreur lors de la récupération du semestre :", error);
  } finally {
    isLoadingSemestre.value = false;
    console.log(semestre.value);
  }
};

const getSemestres = async () => {
  isLoadingSemestres.value = true;
  hasError.value = false;
  console.log(semestre.value)
  try {
    const params = {
      departement: departementId,
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
    const rawGroupes = await getGroupesService(params, '/mini');

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

    // initialiser selectedGroupe si nécessaire
    const types = Object.keys(groupes.value);
    if (types.length > 0 && !selectedGroupe.value) {
      selectedGroupe.value = types[0];
    }
  } catch (error) {
    hasError.value = true;
    console.error("Erreur lors de la récupération des groupes :", error);
  } finally {
    isLoadingGroupes.value = false;
  }
};

const getEtudiants = async () => {
  isLoadingEtudiants.value = true;
  hasError.value = false;
  try {
    const params = {
      anneeUniversitaire: anneeUniv.id,
      semestre: semestre.value.id,
    };
    etudiants.value = await getEtudiantScolariteSemestresService(params, '/manage-groupes');
  } catch (error) {
    hasError.value = true;
    console.error("Erreur lors de la récupération des étudiants :", error);
  } finally {
    isLoadingEtudiants.value = false;

    console.log(etudiants.value);
  }
};
</script>

<template>
  <div class="card min-h-full">
    <div class="flex justify-between items-end w-full">
      <div>
        <h2 class="text-2xl font-bold flex items-end gap-2">Composition des groupes du <SimpleSkeleton v-if="isLoadingSemestre" class="!w-32"></SimpleSkeleton><span v-else>{{semestre.libelle}}</span></h2>
        <em>Répartir les étudiants dans les groupes</em>
      </div>
      <SimpleSkeleton v-if="isLoadingSemestres" class="!w-60 !h-10"></SimpleSkeleton>
      <Select v-else class="w-60" v-model="semestre" option-label="libelle" :options="semestres" placeholder="Sélectionner un semestre">
        <template #value>
          Changer de semestre
        </template>
      </Select>
    </div>
    <Divider/>
    <ErrorView v-if="hasError"></ErrorView>
    <div v-else>
      <Message severity="info" class="mb-4" icon="pi pi-info-circle">
        Vous pouvez ne remplir que le groupe de plus bas niveau (TP) et synchroniser pour remplir automatiquement les groupes parents. Si les groupes sont saisis dans Apogée, vous pouvez aussi les synchroniser (il faut attendre 24h entre la saisie dans Apogée et la possibilité de synchroniser).

      </Message>
      <Loader v-if="isLoadingGroupes" class="my-12"></Loader>
      <div v-else class="flex flex-col gap-4">
        <Tabs :value="selectedGroupe" scrollable>
          <TabList>
            <Tab v-for="type in typesList" :key="type" :value="type" @click="selectedGroupe = type">
              <span>{{ type }}</span>
            </Tab>
          </TabList>
        </Tabs>
        <div v-if="selectedGroupe && groupes[selectedGroupe]">
          <ul>
            <li v-for="g in groupes[selectedGroupe]" :key="g.id">
              {{ g.libelle }}
            </li>
          </ul>
        </div>

        <table v-if="selectedGroupe && groupes[selectedGroupe]" class="w-full border-collapse table-auto">
          <thead>
            <tr class="bg-gray-200">
              <th class="border p-2 text-left">Étudiant</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="etudiant in etudiants" :key="etudiant.id ">
              <td class="border p-2">{{ etudiant.nom }} {{ etudiant.prenom }}</td>
            </tr>
          </tbody>
        </table>

        <div v-else-if="!isLoadingGroupes" class="flex items-center justify-center gap-2">
          <Message severity="warn" class="w-fit" icon="pi pi-exclamation-triangle">
            Aucun groupe pour le semestre ou le type sélectionné.
          </Message>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>

</style>
