<script setup>
import {ref, onMounted, watch} from 'vue';
import { getEvaluationsService, getEnseignementsService } from '@requests';
import { useUsersStore, useAnneeStore, useDiplomeStore } from '@stores';
import { SimpleSkeleton } from '@components';
import { ErrorView, PermissionGuard } from "@components";

const usersStore = useUsersStore();
const hasError = ref(false);
const diplomeStore = useDiplomeStore();
const selectedAnneeUniversitaire = ref(null);
const departementId = ref(null);
const diplomes = ref({});
const isLoadingDiplomes = ref(true);
const selectedDiplome = ref({});
const selectedAnnee = ref({});
const selectedSemestre = ref({});
const enseignements = ref([]);
const isLoadingEnseignements = ref(true);
const evaluations = ref([]);
const isLoadingEvaluations = ref(true);

onMounted(() => {
  departementId.value = usersStore.departementDefaut.id;
  selectedAnneeUniversitaire.value = JSON.parse(localStorage.getItem('selectedAnneeUniv'))
  getDiplomes();
});

watch(selectedDiplome, () => {
  selectedAnnee.value = selectedDiplome.value.annees[0];
});
watch(selectedAnnee, () => {
  selectedSemestre.value = selectedAnnee.value.semestres[0];
})
watch(selectedSemestre, () => {
  getEnseignements();
})

const getDiplomes = async () => {
  isLoadingDiplomes.value = true;
  try {
    diplomes.value = await diplomeStore.diplomes;
    // retirer les diplomes inactifs
    diplomes.value = diplomes.value.filter(diplome => diplome.actif);
  } catch (error) {
    hasError.value = true;
    console.error('Error fetching diplomes:', error);
  } finally {
    selectedDiplome.value = diplomes.value[0];
    selectedAnnee.value = selectedDiplome.value.annees[0];
    selectedSemestre.value = selectedAnnee.value.semestres[0];
    isLoadingDiplomes.value = false;
  }
};

const getEnseignements = async () => {
  isLoadingEnseignements.value = true;
  try {
    const params = {
      semestre: selectedSemestre.value.id,
    };
    enseignements.value = await getEnseignementsService(params);
    for (const enseignement of enseignements.value) {
      await getEvaluations(enseignement.id);
      enseignement.evaluations = evaluations.value;
    }
  } catch (error) {
    hasError.value = true;
    console.error('Error fetching enseignements:', error);
  } finally {
    isLoadingEnseignements.value = false;
  }
};

const getEvaluations = async (enseignement) => {
  isLoadingEvaluations.value = true;
  try {
    const params = {
      enseignement: enseignement,
    };
    evaluations.value = await getEvaluationsService(params);
    return evaluations.value;
  } catch (error) {
    hasError.value = true;
    console.error('Error fetching evaluations:', error);
  } finally {
    isLoadingEvaluations.value = false;
  }
};
</script>

<template>
  <div class="card">
    <ErrorView v-if="hasError"></ErrorView>
    <div v-else>
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
      <div v-else class="my-8 flex items-center gap-4 w-1/2">
        <Select v-if="selectedDiplome" v-model="selectedAnnee" :options="selectedDiplome.annees" option-label="libelle" placeholder="Sélectionner une année" class="w-1/2"/>
        <Select v-if="selectedAnnee" v-model="selectedSemestre" :options="selectedAnnee.semestres" option-label="libelle" placeholder="Sélectionner un semestre" class="w-1/2"/>
      </div>
      <div>
        <Accordion v-if="selectedSemestre" value="0" class="mt-4">
          <AccordionPanel v-for="enseignement in enseignements" :value="enseignement.id" :key="enseignement.id">
            <AccordionHeader>
              <div class="flex justify-between items-center w-full">
                <div class="flex items-center justify-start gap-4">
                  <div class="bg-primary-700 p-3 rounded-md">
                    <i class="pi pi-book text-white w-5 h-5 text-center"></i>
                  </div>
                  <div class="text-lg font-bold">{{enseignement.codeEnseignement}} - {{enseignement.libelle}}</div>
                </div>
              </div>
              <div class="text-sm text-muted-color w-full text-right mr-4">
                {{enseignement.evaluations.length}} évaluations
              </div>
            </AccordionHeader>
            <AccordionContent>
              <div v-for="evaluation in enseignement.evaluations">
                {{evaluation.libelle}}
              </div>
            </AccordionContent>
          </AccordionPanel>
        </Accordion>

      </div>
    </div>
  </div>
</template>

<style scoped>

</style>
