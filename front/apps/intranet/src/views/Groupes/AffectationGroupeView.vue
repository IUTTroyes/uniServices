<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import {ErrorView, SimpleSkeleton} from "@components/index.js";
import Loader from '@components/loader/GlobalLoader.vue'
import { typesGroupes } from '@config/uniServices.js';
import {useSemestreStore} from "@stores";
import { getSemestresService, getSemestreService, getGroupesService } from "@requests";
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
// computed list of types (keys) pour itération dans le template
const typesList = computed(() => Object.keys(groupes.value));

onMounted(async () => {
  await getSemestre();
  await getSemestres();
  await getGroupes();
});

// watcher pour initialiser semestre depuis le store
watch(() => semestreStore.semestre, (newSemestre) => {
  semestre.value = newSemestre;
});

const getSemestre = async () => {
  isLoadingSemestre.value = true;
  hasError.value = false;
  // Récupération de l'id du semestre dans l'url
  try {
    const semestreId = route.params.semestreId;
    semestre.value = await getSemestreService(semestreId);
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
      annee: semestre.value.annee.id,
    };
    semestres.value = await getSemestresService(params);
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

// watcher pour mettre selectedGroupe quand les clés changent (au cas où)
watch(groupes, (newVal) => {
  const types = Object.keys(newVal);
  if (types.length > 0 && !selectedGroupe.value) {
    selectedGroupe.value = types[0];
  }
});
</script>

<template>
  <div class="card min-h-full">
    <div class="flex justify-between items-end w-full">
      <div>
        <h2 class="text-2xl font-bold flex items-end gap-2">Composition des groupes du <SimpleSkeleton v-if="isLoadingSemestre" class="!w-32"></SimpleSkeleton><span v-else>{{semestre.libelle}}</span></h2>
        <em>Répartir les étudiants dans les groupes</em>
      </div>
      <Select v-if="!isLoadingSemestres" class="w-60" v-model="semestre" option-label="libelle" :options="semestres" placeholder="Sélectionner un semestre"/>
    </div>
    <Divider/>
    <ErrorView v-if="hasError"></ErrorView>
    <div v-else>
      <Loader v-if="isLoadingGroupes"></Loader>
      <div v-else>
        <Tabs :value="selectedGroupe" scrollable>
          <TabList>
            <Tab v-for="type in typesList" :key="type" :value="type" @click="selectedGroupe = type">
              <span>{{ type }}</span>
            </Tab>
          </TabList>
        </Tabs>

        <div v-if="selectedGroupe && groupes[selectedGroupe]" class="mt-4">
          <Message severity="info" class="mb-4" icon="pi pi-info-circle">
            Vous pouvez ne remplir que le groupe de plus bas niveau (TP) et synchroniser pour remplir automatiquement les groupes parents. Si les groupes sont saisis dans Apogée, vous pouvez aussi les synchroniser (il faut attendre 24h entre la saisie dans Apogée et la possibilité de synchroniser).

          </Message>
          <ul>
            <li v-for="g in groupes[selectedGroupe]" :key="g.id">
              {{ g.libelle }}
            </li>
          </ul>
        </div>

        <!-- message si aucun groupe -->
        <div v-else-if="!isLoadingGroupes">
          Aucun groupe pour le type sélectionné.
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>

</style>
