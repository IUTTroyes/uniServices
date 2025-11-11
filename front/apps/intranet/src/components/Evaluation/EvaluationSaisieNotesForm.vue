<script setup>
import {onMounted, ref, watch} from 'vue';
import {ErrorView, ListSkeleton, ValidatedInput, validationRules} from "@components";
import {
  createEtudiantNoteService,
  updateEtudiantNoteService,
  getEtudiantNotesService,
  getEtudiantScolariteSemestresService,
  getEtudiantsService,
  getEvaluationService,
  getGroupesService
} from "@requests";
import {v4 as uuidv4} from 'uuid';
import { useToast } from "primevue/usetoast";
const toast = useToast();

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

    for (const etudiant of etudiants.value) {
      const notes = await getEtudiantNotes(etudiant.id);
      if (notes.length > 0) {
        const note = notes[0];
        etudiant.note = note.note;
        etudiant.absenceJustifiee = note.absenceJustifiee;
        etudiant.commentaire = note.commentaire;
        etudiant.noteId = note.id;
      }
    }
    rows.value = etudiants.value.map(etudiant => ({
      etudiantId: etudiant.id,
      display: etudiant.display || 'Étudiant inconnu',
      noteId: etudiant.noteId || null,
      note: etudiant.note !== undefined ? etudiant.note : null,
      absenceJustifiee: etudiant.absenceJustifiee !== undefined ? etudiant.absenceJustifiee : false,
      commentaire: etudiant.commentaire || '',
    }));
  } catch (error) {
    console.error('Erreur lors du chargement des étudiants:', error);
  } finally {
    isLoadingEtudiants.value = false;
  }
};

const getEtudiantNotes = async (etudiantId) => {
  try {
    const params = {
      evaluation: props.evaluationId,
      etudiant: etudiantId,
    };
    return await getEtudiantNotesService(params);
  } catch (error) {
    console.error('Erreur lors du chargement des notes des étudiants:', error);
    return [];
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
  isLoadingEtudiants.value = true;
  try {
    for (const row of rows.value) {
      const scolariteId = await getScolariteSemestre(row.etudiantId);
      if (!scolariteId) {
        console.error(`Pas de scolarité semestre pour l'étudiant ${row.etudiantId}, saut.`);
        continue;
      }

      // préparer le payload sans champs locaux
      const payload = {
        evaluation: `/api/scol_evaluations/${props.evaluationId}`,
        scolariteSemestre: `/api/etudiant_scolarite_semestres/${scolariteId}`,
        note: row.absenceJustifiee ? -0.01 : row.note,
        absenceJustifiee: row.absenceJustifiee,
        commentaire: row.commentaire || '',
        uuid: row.noteId ? undefined : uuidv4() // nouveau uuid seulement pour création
      };

      // supprimer uuid si undefined
      if (payload.uuid === undefined) {
        delete payload.uuid;
      }

      if (row.note) {
        if (row.noteId) {
          const etudiant = etudiants.value.find(e => e.id === row.etudiantId);
          // mise à jour
          if (etudiant.note !== payload.note || etudiant.absenceJustifiee !== payload.absenceJustifiee || etudiant.commentaire !== payload.commentaire) {
            await updateEtudiantNoteService(row.noteId, payload);

            // si il n'y a pas eu d'erreur, afficher un message de succès
            if (!hasError.value) {
              toast.add(
                  { severity: 'success', summary: 'Succès', detail: 'Les notes ont été enregistrées avec succès.', life: 5000 }
              );
            }
          }
        } else {
          // création
          await createEtudiantNoteService(payload);
        }
      }
    }
  } catch (error) {
    hasError.value = true;
    toast.add(
        { severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors de l\'enregistrement des notes.', life: 5000 }
    );
  } finally {
    isLoadingEtudiants.value = false;
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
          <li class="list-disc">-0.01 indique une note non comptabilisée.</li>
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
    <div class="flex justify-center items-center gap-4 mt-4">
      <Button class="w-1/2" label="Enregistrer les notes" @click="submitNotes" :disabled="!formValid" />
      <Button class="w-1/2" label="Annuler" severity="secondary" @click="" :disabled="!formValid" />
    </div>
  </div>
</template>

<style scoped>

</style>
