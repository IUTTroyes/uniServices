<script setup>
import {ref, onMounted, watch} from 'vue';
import { getSemestresService } from '@requests';
import { useUsersStore, useAnneeStore, useDiplomeStore } from '@stores';
import { SimpleSkeleton } from '@components';
import { ErrorView, PermissionGuard } from "@components";

const usersStore = useUsersStore();
const anneeStore = useAnneeStore();
const diplomeStore = useDiplomeStore();
const selectedAnneeUniversitaire = ref(null);
const departementId = ref(null);
const diplomes = ref({});
const isLoadingDiplomes = ref(false);
const selectedDiplome = ref({});
const selectedAnnee = ref({});
const semestres = ref({});
const selectedSemestre = ref({});

onMounted(() => {
  departementId.value = usersStore.departementDefaut.id;
  selectedAnneeUniversitaire.value = JSON.parse(localStorage.getItem('selectedAnneeUniv'))
  getDiplomes();
});

const getDiplomes = async () => {
  isLoadingDiplomes.value = true;
  try {
    diplomes.value = await diplomeStore.diplomes;
    // retirer les diplomes inactifs
    diplomes.value = diplomes.value.filter(diplome => diplome.actif);
  } catch (error) {
    console.error('Error fetching diplomes:', error);
  } finally {
    selectedDiplome.value = diplomes.value[0];
    selectedAnnee.value = selectedDiplome.value.annees[0];
    console.log(selectedDiplome.value);
    isLoadingDiplomes.value = false;
  }
};

watch(selectedDiplome, () => {
  selectedAnnee.value = selectedDiplome.value.annees[0];
});
watch(selectedAnnee, () => {
  selectedSemestre.value = selectedAnnee.value.semestres[0];
})
</script>

<template>
  <div class="card">
    <SimpleSkeleton v-if="isLoadingDiplomes" class="w-full"/>
    <Tabs v-else :value="selectedDiplome.id" scrollable>
      <TabList>
        <Tab v-for="diplome in diplomes" :key="diplome.libelle" :value="diplome.id" @click="selectedDiplome = diplome">
        <span>
          <span>{{ diplome.typeDiplome.sigle }}</span> | <span>{{ diplome.sigle }}</span> <Tag v-if="!diplome.actif" severity="danger">Inactif</Tag>
        </span>
        </Tab>
      </TabList>
    </Tabs>
    <div v-if="isLoadingDiplomes" class="flex items-center gap-4 w-1/2">
      <SimpleSkeleton class="w-1/2"/>
      <SimpleSkeleton class="w-1/2"/>
    </div>
    <div v-else class="mt-8 flex items-center gap-4 w-1/2">
      <Select v-if="selectedDiplome" v-model="selectedAnnee" :options="selectedDiplome.annees" option-label="libelle" placeholder="Sélectionner une année" class="w-1/2"/>
      <Select v-if="selectedAnnee" v-model="selectedSemestre" :options="selectedAnnee.semestres" option-label="libelle" placeholder="Sélectionner un semestre" class="w-1/2"/>
    </div>
  </div>
</template>

<style scoped>

</style>
