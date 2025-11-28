<script setup>
import {onMounted, ref, watch, computed} from 'vue';
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
const emit = defineEmits(['close']);
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
const tableRows = ref([]);
const page = ref(0);
const rowOptions = [30, 60, 120];
const limit = ref(rowOptions[0]);
const offset = computed(() => limit.value * page.value);

const props = defineProps({
  evaluationId: {
    type: Number,
    required: true
  },
  semestreId: {
    type: Number,
    required: false
  },
});

onMounted(async () => {
  await getEvaluation();
  await getGroupes();
});

watch(selectedGroupe, async () => {
  if (selectedGroupe.value) {
    page.value = 0;
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
      itemsPerPage: limit.value,
      page: parseInt(page.value) + 1,
    };
    etudiants.value = await getEtudiantsService(params);

    for (const etudiant of etudiants.value) {
      const notesList = await getEtudiantNotes(etudiant.id);
      if (notesList && notesList.length > 0) {
        const note = notesList[0];
        etudiant.note = note.note;
        // nouvelle propriété: presenceStatut
        // fallback: si non fourni (anciennes données), déduire uniquement depuis la valeur de note
        let presenceStatut = note.presenceStatut;
        if (!presenceStatut) {
          const n = note.note;
          if (n === -0.01) {
            presenceStatut = 'absent_justifie'; // impossible de distinguer 'dispense' des anciennes données
          } else if (n === 0) {
            presenceStatut = 'absent_injustifie';
          } else {
            presenceStatut = 'present';
          }
        }
        etudiant.presenceStatut = presenceStatut;
        etudiant.commentaire = note.commentaire;
        etudiant.noteId = note.id;
      }
    }
    tableRows.value = etudiants.value.map(etudiant => {
      // déterminer le statut d'absence (valeur UI) à partir de presenceStatut
      let absenceStatus = 'present';
      switch (etudiant.presenceStatut) {
        case 'absent_injustifie':
          absenceStatus = 'abs_injustifiee';
          break;
        case 'absent_justifie':
          absenceStatus = 'abs_justifiee';
          break;
        case 'dispense':
          absenceStatus = 'dispense';
          break;
        case 'present':
        default:
          absenceStatus = 'present';
      }
      return {
        etudiantId: etudiant.id,
        display: etudiant.display || 'Étudiant inconnu',
        noteId: etudiant.noteId || null,
        note: etudiant.note !== undefined ? etudiant.note : null,
        presenceStatut: etudiant.presenceStatut || 'present',
        absenceStatus,
        commentaire: etudiant.commentaire || '',
      };
    });
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

const onAbsenceChange = (row, status) => {
  row.absenceStatus = status;
  // map UI status to API presenceStatut
  switch (status) {
    case 'abs_injustifiee':
      row.presenceStatut = 'absent_injustifie';
      row.note = 0;
      break;
    case 'abs_justifiee':
      row.presenceStatut = 'absent_justifie';
      row.note = -0.01;
      break;
    case 'dispense':
      row.presenceStatut = 'dispense';
      row.note = -0.01;
      break;
    case 'present':
    default:
      row.presenceStatut = 'present';
      // garder la note saisie par l'utilisateur (ne pas forcer)
      break;
  }
};

const submitNotes = async () => {
  isLoadingEtudiants.value = true;
  try {
    for (const row of tableRows.value) {
      const scolariteId = await getScolariteSemestre(row.etudiantId);
      if (!scolariteId) {
        console.error(`Pas de scolarité semestre pour l'étudiant ${row.etudiantId}, saut.`);
        continue;
      }

      // déterminer la note en fonction du statut de présence
      let computedNote = row.note;
      const computedPresenceStatut = row.presenceStatut || 'present';
      switch (computedPresenceStatut) {
        case 'absent_injustifie':
          computedNote = 0;
          break;
        case 'absent_justifie':
        case 'dispense':
          computedNote = -0.01;
          break;
        case 'present':
        default:
          // garder la note telle quelle
          break;
      }

      // synchroniser les champs conservés
      row.note = computedNote;

      // préparer le payload
      const payload = {
        evaluation: `/api/scol_evaluations/${props.evaluationId}`,
        scolariteSemestre: `/api/etudiant_scolarite_semestres/${scolariteId}`,
        note: computedNote,
        presenceStatut: computedPresenceStatut,
        commentaire: row.commentaire || '',
        uuid: row.noteId ? undefined : uuidv4() // nouveau uuid seulement pour création
      };

      // supprimer uuid si undefined
      if (payload.uuid === undefined) {
        delete payload.uuid;
      }

      // accepter 0 et -0.01 ; vérifier présence explicite de la valeur
      if (row.note !== null && row.note !== undefined) {
        if (row.noteId) {
          const etudiant = etudiants.value.find(e => e.id === row.etudiantId);
          // mise à jour
          if (etudiant.note !== payload.note || etudiant.presenceStatut !== payload.presenceStatut || etudiant.commentaire !== payload.commentaire) {
            await updateEtudiantNoteService(row.noteId, payload);
          }
        } else {
          // création
          await createEtudiantNoteService(payload);
        }
      }
    }
    // si il n'y a pas eu d'erreur, afficher un message de succès
    if (!hasError.value) {
      toast.add(
          { severity: 'success', summary: 'Succès', detail: 'Les notes ont été enregistrées avec succès.', life: 5000 }
      );
    }
  } catch (error) {
    hasError.value = true;
    toast.add(
        { severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors de l\'enregistrement des notes.', life: 5000 }
    );
  } finally {
    emit('saved');
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

const onPageChange = async event => {
  limit.value = event.rows;
  page.value = event.page;
  await getEtudiants();
};

const onRowsChange = async rows => {
  limit.value = rows;
  page.value = 0;
  await getEtudiants();
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
    <div v-else>
      <Message class="mt-4" severity="error" icon="pi pi-exclamation-triangle" :sticky="true">
        Pensez à enregistrer les notes avant de changer de groupe !
      </Message>
      <Message class="mt-4" severity="info" icon="pi pi-info-circle" :sticky="true">
        <ul class="ml-8">
          <li class="list-disc">Un étudiant noté comme absent non justifié recevra automatiquement un 0.</li>
          <li class="list-disc"><span class="flex flex-col">Un étudiant noté comme absent justifié ne sera pas pénalisé. <span class="text-xs text-muted-color">Si vous ne savez pas encore si l'absence d'un étudiant est justifiée, ne rien saisir.</span></span></li>
          <li class="list-disc">-0.01 indique une note non comptabilisée.</li>
        </ul>
      </Message>

      <DataTable
          :value="tableRows"
          class="mt-4"
          striped-rows
          responsive-layout="scroll"
          lazy
          :paginator="selectedGroupe.type!=='TP'"
          :first="offset"
          :rows="limit"
          :rowsPerPageOptions="rowOptions"
          :totalRecords="etudiants.totalItems"
          @page="onPageChange($event)"
          @update:rows="onRowsChange($event)"
          data-key="etudiantId"
      >
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
                :rules="[]"
                inputId="minmax" :min="-0.01" :max="20"
                :minfractiondigits="2" :maxfractiondigits="2"
                :disabled="slotProps.data.absenceStatus !== 'present'"
                @validation="result => handleValidation('note', result)"
            />
          </template>
        </Column>

        <Column header="Absence" style="width:220px">
          <template #body="slotProps">
            <ValidatedInput
                class="!mb-0"
                v-model="slotProps.data.absenceStatus"
                type="select"
                placeholder="Présent"
                :options="[
                  { label: 'Présent', value: 'present' },
                  { label: 'Absence injustifiée', value: 'abs_injustifiee' },
                  { label: 'Absence justifiée', value: 'abs_justifiee' },
                  { label: 'Dispensé', value: 'dispense' }
                ]"
                name="absenceStatus"
                :rules="[]"
                @update:modelValue="val => onAbsenceChange(slotProps.data, val)"
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
        <template #footer> {{ etudiants.totalItems }} résultat(s).</template>
      </DataTable>
    </div>
    <div class="flex justify-center items-center gap-4 mt-4">
      <Button class="w-1/2" label="Enregistrer les notes" @click="submitNotes"/>
      <Button class="w-1/2" label="Annuler" severity="secondary" @click="() => emit('close')" />
    </div>
  </div>
</template>

<style scoped>

</style>
