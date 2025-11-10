<script setup>
import {onMounted, ref, watch} from 'vue';
import {ErrorView, ListSkeleton, SimpleSkeleton, ArticleSkeleton, ValidatedInput, validationRules} from "@components";
import {getEvaluationService, getGroupesService, getEtudiantsService} from "@requests";

const hasError = ref(false);
const isLoadingEvaluation = ref(true);
const evaluation = ref({});
const isLoadingGroupes = ref(true);
const groupes = ref([]);
const selectedGroupe = ref(null);
const isLoadingEtudiants = ref(true);
const etudiants = ref([]);
const rows = ref([]);

const props = defineProps({
  evaluationId: {
    type: Number,
    required: true
  },
  semestreId: {
    type: Number,
    required: false
  }
});

onMounted(async () => {
  await getEvaluation();
  await getGroupes();
});

watch(selectedGroupe, async () => {
  if (selectedGroupe.value) {
    await getEtudiants();
  }
})

const getEvaluation = async () => {
  try {
    isLoadingEvaluation.value = true;
    evaluation.value = await getEvaluationService(props.evaluationId);
  } catch (error) {
    console.error('Erreur lors du chargement de l\'évaluation:', error);
  } finally {
    console.log(evaluation.value);
    isLoadingEvaluation.value = false;
  }
};

const getGroupes = async () => {
  try {
    isLoadingGroupes.value = true;
    const params = {
      semestre: props.semestreId,
      type: evaluation.value.typeGroupe,
    };
    groupes.value = await getGroupesService(params, '/mini');
    if (groupes.value.length > 0) {
      selectedGroupe.value = groupes.value[0];
    }
  } catch (error) {
    console.error('Erreur lors du chargement des groupes:', error);
    hasError.value = true;
  } finally {
    isLoadingGroupes.value = false;
  }
};

const getEtudiants = async () => {
  try {
    isLoadingEtudiants.value = true;
    const params = {
      groupe: selectedGroupe.value.id,
    };
    etudiants.value = await getEtudiantsService(params);

    // dans rows, on prépare les données pour la table
    rows.value = etudiants.value.map(e => ({
      id: e.id,
      display: `${e.prenom} ${e.nom}`,
      note: 0,
      absence: 1, // Présent par défaut
      commentaire: '',
    }));
  } catch (error) {
    console.error('Erreur lors du chargement des étudiants:', error);
  } finally {
    console.log(etudiants.value);
    isLoadingEtudiants.value = false;
  }
};
</script>

<template>
  <ErrorView v-if="hasError"></ErrorView>
  <div v-else>
    <ListSkeleton v-if="isLoadingGroupes" class="flex items-center gap-4 w-1/2"></ListSkeleton>
    <Tabs v-else :value="selectedGroupe.id" scrollable>
      <TabList>
        <Tab v-for="groupe in groupes" :key="groupe.libelle" :value="groupe.id" @click="selectedGroupe = groupe">
          {{ groupe.libelle }}
        </Tab>
      </TabList>
    </Tabs>
    <ListSkeleton v-if="isLoadingEtudiants"></ListSkeleton>
    <DataTable v-else :value="rows" class="mt-4" responsive-layout="scroll">
      <Column header="Etudiant">
        <template #body="slotProps">
          {{ slotProps.data.display }}
        </template>
      </Column>

      <Column header="Note">
        <template #body="slotProps">
          <ValidatedInput
              class="!mb-0"
              name="note"
              type="number"
              v-model="slotProps.data.note"
              :rules="[validationRules.required]"
              inputId="minmax" :min="0" :max="20"
          />
        </template>
      </Column>

      <Column header="Absence" style="width:220px">
        <template #body="slotProps">
          <ValidatedInput
              class="!mb-0"
              v-model="slotProps.data.absence"
              type="select"
              placeholder="Présent"
              :options="[{ label: 'Présent', value: 1 }, { label: 'Absent', value: 2 }, { label: 'Absence justifiée', value: 3 }]"
              name="absence"
              :rules="[validationRules.required]"
          >
          </ValidatedInput>
        </template>
      </Column>

      <Column header="Commentaire">
        <template #body="slotProps">
          <ValidatedInput
              class="!mb-0"
              name="commentaire"
              type="text"
              v-model="slotProps.data.commentaire"
              :rules="[]"
          />
        </template>
      </Column>
    </DataTable>
    <div class="mt-4 flex justify-end">
      <Button label="Enregistrer les notes" />
    </div>
  </div>
</template>

<style scoped>

</style>
