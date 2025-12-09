<script setup>
import {onMounted, ref, watch} from "vue";
import {ErrorView, SimpleSkeleton} from "@components";
import {ValidatedInput} from "@components";
import {useDiplomeStore, useUsersStore} from "@stores";
import {getEnseignementsService} from "@requests/scol_services/enseignementService.js";
import {getPersonnelsService} from "@requests/user_services/personnelService.js";
import {getSallesService} from "@requests/salleService.js";

const usersStore = useUsersStore();
const departement = usersStore.departementDefaut;
const hasError = ref(false);
const minDate = ref();
const maxDate = ref();
const periode = ref(null);
const diplomes = ref([]);
const diplomeStore = useDiplomeStore();
const isLoadingDiplomes = ref(true);
const selectedDiplome = ref(null);
const selectedAnnee = ref(null);
const selectedSemestre = ref(null);
const selectedAnneeId = ref(null);
const selectedSemestreId = ref(null);
const selectedEnseignantId = ref(null);
const salleId = ref(null);
const selectedEnseignementId = ref(null);
const enseignements = ref([]);
const isLoadingEnseignements = ref(false);
const enseignants = ref([]);
const isLoadingEnseignants = ref(true);
const hasErrorEnseignants = ref(false);
const salles = ref([]);
const isLoadingSalles = ref(true);
const selectedSalleId = ref(null);



onMounted( async() => {
  await calcPeriode();
  await getDiplomes();
  await getEnseignements();
  await getEnseignants();
  await getSalles();
})

const calcPeriode = () => {
  const today = new Date();
  const priorDate = new Date();
  priorDate.setDate(today.getDate() - 30);
  minDate.value = priorDate;
  maxDate.value = today;
  // valeur par défaut pour la plage de dates
  periode.value = [minDate.value, maxDate.value];
}

const getDiplomes = async () => {
  isLoadingDiplomes.value = true;
  try {
    diplomes.value = await diplomeStore.diplomes;
    // retirer les diplomes inactifs
    diplomes.value = (diplomes.value || []).filter(diplome => diplome.actif);
  } catch (error) {
    hasError.value = true;
    console.error('Error fetching diplomes:', error);
  } finally {
    selectedDiplome.value = diplomes.value?.[0] || null;
    selectedAnnee.value = selectedDiplome.value?.annees?.[0] || null;
    selectedSemestre.value = selectedAnnee.value?.semestres?.[0] || null;
    selectedAnneeId.value = selectedAnnee.value?.id ?? null;
    selectedSemestreId.value = selectedSemestre.value?.id ?? null;
    isLoadingDiplomes.value = false;
  }
};

const getEnseignements = async () => {
  isLoadingEnseignements.value = true;
  try {
    const params = {
      semestre: selectedSemestre.value ? selectedSemestre.value.id : null,
      departement: selectedSemestre.value ? null : departement.id,
      actif: true,
    };
    enseignements.value = await getEnseignementsService(params);

    // reconstruire le libelle pour inclure le code_enseignement
    enseignements.value = enseignements.value.map(enseignement => ({
      ...enseignement,
      libelle: `${enseignement.codeEnseignement} - ${enseignement.libelle}`
    }));
  } catch (error) {
    console.error('Erreur lors du chargement des enseignements :', error);
  } finally {
    isLoadingEnseignements.value = false;
  }
};

const getEnseignants = async () => {
  isLoadingEnseignants.value = true;
  try {
    const params = {
      departement: departement.id,
    };
    enseignants.value = await getPersonnelsService(params);
    console.log(enseignants.value?.[0] ?? null);
  } catch (error) {
    hasErrorEnseignants.value = true;
    console.error('Erreur lors du chargement des enseignants :', error);
  } finally {
    isLoadingEnseignants.value = false;
  }
};

const getSalles = async () => {
  try {
    salles.value = await getSallesService();
  } catch (error) {
    console.error('Erreur lors du chargement des salles :', error);
  } finally {
    isLoadingSalles.value = false;
  }
};

watch(selectedAnneeId, (newId) => {
  if (!selectedDiplome.value) return;
  selectedAnnee.value = (selectedDiplome.value.annees || []).find(a => a.id === newId) || null;
  // réinitialiser semestre si année change
  selectedSemestre.value = selectedAnnee.value?.semestres?.[0] || null;
  selectedSemestreId.value = selectedSemestre.value?.id ?? null;
});

watch(selectedSemestreId, async (newId) => {
  if (!selectedAnnee.value) return;
  selectedSemestre.value = (selectedAnnee.value.semestres || []).find(s => s.id === newId) || null;
  await getEnseignements();
});
const setDiplome = (diplome) => {
  selectedDiplome.value = diplome;
  selectedAnnee.value = diplome?.annees?.[0] || null;
  selectedAnneeId.value = selectedAnnee.value?.id ?? null;
  selectedSemestre.value = selectedAnnee.value?.semestres?.[0] || null;
  selectedSemestreId.value = selectedSemestre.value?.id ?? null;
};

const clearFilters = () => {
  selectedDiplome.value = diplomes.value?.[0] || null;
  selectedAnnee.value = selectedDiplome.value?.annees?.[0] || null;
  selectedAnneeId.value = selectedAnnee.value?.id ?? null;
  selectedSemestre.value = selectedAnnee.value?.semestres?.[0] || null;
  selectedSemestreId.value = selectedSemestre.value?.id ?? null;
  periode.value = null;
  selectedEnseignantId.value = null;
  selectedEnseignementId.value = null;
  selectedSalleId.value = null;
};
</script>

<template>
  <ErrorView v-if="hasError"></ErrorView>
  <div v-else class="border border-gray-300 dark:border-gray-700 rounded-lg p-6 mb-6 w-full bg-neutral-100/20">
    <div class="text-lg font-bold mb-4">Filtres</div>

    <div>
      <SimpleSkeleton v-if="isLoadingDiplomes" class="w-full"/>
      <Tabs v-else :value="selectedDiplome ? selectedDiplome.id : null" scrollable>
        <TabList>
          <Tab v-for="diplome in diplomes" :key="diplome.libelle" :value="diplome.id" @click="setDiplome(diplome)">
        <span>
          <span>{{ diplome.typeDiplome.sigle }}</span> | <span>{{ diplome.sigle }}</span> <Tag v-if="!diplome.actif" severity="danger">Inactif</Tag>
        </span>
          </Tab>
        </TabList>
      </Tabs>
      <div v-if="isLoadingDiplomes" class="flex items-center gap-4 w-full">
        <SimpleSkeleton class="w-1/2"/>
        <SimpleSkeleton class="w-1/2"/>
      </div>
      <div v-else class="mt-8 flex items-center gap-4 w-full">
              <ValidatedInput
                  v-model="selectedAnneeId"
                  :options="(selectedDiplome?.annees || []).map(annee => ({...annee, label: annee.libelle, value: annee.id}))"
                  name="annee"
                  label="Années"
                  type="select"
                  :rules="[]"
                  class="w-full"
              />
              <ValidatedInput
                  v-model="selectedSemestreId"
                  :options="(selectedAnnee?.semestres || []).map(semestre => ({...semestre, label: semestre.libelle, value: semestre.id}))"
                  name="semestre"
                  label="Semestres"
                  type="select"
                  :rules="[]"
                  class="w-full"
              />
            </div>
    </div>
    <Divider></Divider>
    <div class="flex items-start gap-4">
        <ValidatedInput
            v-model="periode"
            name="date"
            label="Période"
            type="date"
            :rules="[]"
            selectionMode="range"
            :manualInput="false"
            :minDate="minDate"
            :maxDate="maxDate"
            placeholder="Sélectionner une période"
        />

      <div v-if="isLoadingEnseignements" class="w-full">
        <div>Enseignements</div>
        <SimpleSkeleton class="w-full"/>
      </div>
      <ValidatedInput
          v-else
          v-model="selectedEnseignementId"
          :options="enseignements.map(enseignement => ({...enseignement, label: enseignement.libelle, value: enseignement.id}))"
          name="enseignement"
          label="Enseignements"
          type="select"
          :rules="[]"
          class="w-full"
          placeholder="Sélectionner un enseignement"
          :show-clear="true"
      />

      <div v-if="isLoadingEnseignants" class="w-full">
        <div>Enseignants</div>
        <SimpleSkeleton class="w-full"/>
      </div>
      <ValidatedInput
          v-else
          v-model="selectedEnseignantId"
          :options="enseignants.map(enseignant => ({...enseignant, label: enseignant.display, value: enseignant.id}))"
          name="enseignant"
          label="Enseignants"
          type="select"
          :rules="[]"
          class="w-full"
          placeholder="Sélectionner un enseignant"
          :show-clear="true"
      />


      <div v-if="isLoadingSalles" class="w-full">
        <div>Salles</div>
        <SimpleSkeleton class="w-full"/>
      </div>
      <ValidatedInput
          v-else
          v-model="selectedSalleId"
          :options="salles.map(salle => ({...salle, label: salle.libelle, value: salle.id}))"
          name="salle"
          label="Salles"
          type="select"
          :rules="[]"
          class="w-full"
          placeholder="Sélectionner une salle"
          :showClear="true"
      />
    </div>

    <div class="flex items-center justify-end gap-4 w-full">
      <Button label="Réinitialiser les filtres" icon="pi pi-filter" severity="secondary" @click="clearFilters"/>
    </div>
  </div>

  <div class="flex items-center gap-4">
    <div class="border border-gray-300 dark:border-gray-700 rounded-lg p-6 w-full">
      <div class="text-lg font-bold mb-4">Nombre d'heures programmées</div>
    </div>
    <div class="border border-gray-300 dark:border-gray-700 rounded-lg p-6 w-full">
      <div class="text-lg font-bold mb-4">Répartition des types d'activités</div>
    </div>
  </div>
</template>

<style scoped>
#date {
  width: 100% !important;
}
</style>
