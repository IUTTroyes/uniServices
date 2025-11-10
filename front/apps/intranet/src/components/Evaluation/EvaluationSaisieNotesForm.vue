<script setup>
import {onMounted, ref, watch} from 'vue';
import {ErrorView, ListSkeleton, ValidatedInput, validationRules} from "@components";
import {
  createEtudiantNoteService,
  getEtudiantScolariteSemestresService,
  getEtudiantsService,
  getEvaluationService,
  getGroupesService
} from "@requests";
import { v4 as uuidv4 } from 'uuid';

const hasError = ref(false);
const formValid = ref(true);
const formErrors = ref({});
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
      etudiantId: e.id,
      display: `${e.prenom} ${e.nom}`,
      note: -0.01,
      absenceJustifiee: false, // Présent par défaut
      commentaire: '',
      evaluation: props.evaluationId,
      scolariteSemestre: null,
    }));
  } catch (error) {
    console.error('Erreur lors du chargement des étudiants:', error);
  } finally {
    console.log(etudiants.value);
    isLoadingEtudiants.value = false;
  }
};

const handleValidation = (field, result) => {
  formErrors.value = {
    ...formErrors.value,
    [field]: result.isValid ? null : result.errorMessage
  };
  formValid.value = Object.values(formErrors.value).every(error => error === null);
};

const submitNotes = async () => {
  try {
    for (const row of rows.value) {
      row.scolariteSemestre = await getScolariteSemestre(row.etudiantId);

      // retirer etudiantId et display avant enregistrement
      delete row.etudiantId;
      delete row.display;

      // transformer evaluation en IRI
      row.evaluation = `/api/scol_evaluations/${props.evaluationId}`;
      // transformer scolariteSemestre en IRI
      row.scolariteSemestre = `/api/etudiant_scolarite_semestres/${row.scolariteSemestre}`;
      row.uuid = uuidv4();
      // si absenceJustifiee est à true, on passe la note à -0.01
      if (row.absenceJustifiee) {
        row.note = -0.01;
      }

      await createEtudiantNoteService(row)
    }
  } catch (error) {
    console.error('Erreur lors de la soumission des notes:', error);
  } finally {
    toast.add(
      { severity: 'success', summary: 'Succès', detail: 'Les notes ont été enregistrées avec succès.', life: 3000 }
    )
  }

};

const getScolariteSemestre = async (etudiantId) => {
  try {
    const params = {
      etudiant: etudiantId,
      semestre: props.semestreId,
    };
    const response = await getEtudiantScolariteSemestresService(params);
    if (response && response.length > 0) {
      return response[0].id;
    }
    return null;
  } catch (error) {
    console.error('Erreur lors du chargement de la scolarité semestre:', error);
    return null;
  }
}
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
    <div v-else>
      <Message class="mt-4" severity="info" icon="pi pi-info-circle" :sticky="true">
        <ul class="ml-8">
          <li class="list-disc">Un étudiant noté comme absent non justifié recevra automatiquement un 0.</li>
          <li class="list-disc"><span class="flex flex-col">Un étudiant noté comme absent justifié ne sera pas pénalisé. <span class="text-xs text-muted-color">Si vous ne savez pas encore si l'absence d'un étudiant est justifiée, ne rien saisir.</span></span></li>
          <li class="list-disc">-0.01 indique une note non saisie.</li>
        </ul>
      </Message>
      <DataTable :value="rows" class="mt-4" responsive-layout="scroll">
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
                inputId="minmax" :min="-0.01" :max="20"
                @validation="result => handleValidation('note', result)"
            />
          </template>
        </Column>

        <Column header="Absence" style="width:220px">
          <template #body="slotProps">
            <ValidatedInput
                class="!mb-0"
                v-model="slotProps.data.absenceJustifiee"
                type="select"
                placeholder="Présent"
                :options="[{ label: 'Présent', value: false }, { label: 'Absence justifiée', value: true }]"
                name="absenceJustifiee"
                :rules="[validationRules.required]"
                @validation="result => handleValidation('absence', result)"
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
                @validation="result => handleValidation('commentaire', result)"
            />
          </template>
        </Column>
      </DataTable>
    </div>
    <div class="flex justify-center items-center gap-4">
      <Button label="Enregistrer les notes" @click="submitNotes" :disabled="!formValid" />
      <Button label="Annuler" severity="secondary" @click="" :disabled="!formValid" />
    </div>
  </div>
</template>

<style scoped>

</style>
