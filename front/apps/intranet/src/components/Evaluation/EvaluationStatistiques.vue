<script setup>
import {computed, onMounted, ref, watch} from 'vue';
import {ErrorView, ListSkeleton, SimpleSkeleton} from "@components";
import {
  getEvaluationService,
  updateEvaluationService,
  getGroupesService,
  getEtudiantsService,
  getEtudiantNotesService
} from "@requests";
import EvaluationNotesRepartitionChart from "./EvaluationNotesRepartitionChart.vue";
import EvaluationCard from "@/components/Evaluation/EvaluationCard.vue";

const hasError = ref(false);
const isLoading = ref(true);
const evaluation = ref({});
const groupes = ref([]);
const isLoadingGroupes = ref(false);
const selectedGroupe = ref(null);
const etudiants = ref([]);
const isLoadingEtudiants = ref(false);
const tableRows = ref([]);
const page = ref(0);
const rowOptions = [30, 60, 120];
const limit = ref(rowOptions[0]);
const offset = computed(() => limit.value * page.value);
const emit = defineEmits(['saved', 'close']);

const props = defineProps({
  evaluationId: {
    type: Number,
    required: true
  },
  semestreId: {
    type: Number,
    required: false
  }
})

onMounted(async () => {
  console.log(hasError.value);
  await getEvaluation();
  await getGroupes();
});

const getEvaluation = async () => {
  try {
    isLoading.value = true;
    evaluation.value = await getEvaluationService(props.evaluationId);
    await calcEvaluationProgress(evaluation.value);
  } catch (error) {
    console.error('Erreur lors du chargement de l\'évaluation:', error);
  } finally {
    isLoading.value = false;
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
    console.log(groupes.value)
    isLoadingGroupes.value = false;
  }
};

watch(selectedGroupe, async () => {
  if (selectedGroupe.value) {
    page.value = 0;
    await getEtudiants();
  }
})

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

const onPageChange = async event => {
  limit.value = event.rows;
  page.value = event.page;
  await getEtudiants();
};

const calcEvaluationProgress = (evaluation) => {
  // Ne compter que les notes existantes et dont la propriété `note` n'est pas null
  const notesExistantes = Array.isArray(evaluation?.notes) ? evaluation.notes.filter(n => n != null) : [];
  evaluation.total = notesExistantes.length;
  evaluation.entered = notesExistantes.filter(n => n.note !== null && n.note !== undefined).length;
  evaluation.percent = evaluation.total > 0 ? Math.round((evaluation.entered / evaluation.total) * 100) : 0;
  if (evaluation.percent === 100) {
    evaluation.etat = 'complet';
  }
};

const updateEvaluationVisibility = async (evaluation) => {
  try {
    await updateEvaluationService(evaluation.id, { visible: evaluation.visible }, true);
  } catch (error) {
    hasError.value = true;
    console.error('Error updating evaluation visibility:', error);
  } finally {
    // prévenir le parent de rafraîchir les données en arrière-plan
    emit('saved');
  }
}

const updateEvaluationEdit = async (evaluation) => {
  try {
    await updateEvaluationService(evaluation.id, { modifiable: evaluation.modifiable }, true);
  } catch (error) {
    hasError.value = true;
    console.error('Error updating evaluation modifiable:', error);
  } finally {
    emit('saved');
  }
};

// Quand un enfant sauvegarde (ex: Saisie des notes depuis la modal Statistiques),
// recharger l'évaluation locale puis prévenir le parent pour rafraîchir en arrière-plan
const onChildSaved = async () => {
  await getEvaluation();
  emit('saved');
};
</script>

<template>
  <ErrorView v-if="hasError"></ErrorView>
  <div v-else>
    <SimpleSkeleton v-if="isLoading" :width="'100%'" :height="'400px'"/>
    <div v-else>
      <EvaluationCard class="mb-8"
                      :evaluation="evaluation"
                      :semestreId="props.semestreId"
                      :useLocalDialog="true"
                      :inStatsContext="true"
                      @saved="onChildSaved"
                      @update-visibility="updateEvaluationVisibility"
                      @update-edit="updateEvaluationEdit"
      />
    </div>

    <Divider></Divider>

    <div class="mx-12 flex flex-col gap-8">
      <div class="flex items-center justify-between gap-4">
        <div class="w-full">
          <div class="text-xl font-bold mb-4">
            Résultats
          </div>
          <div class="flex flex-col gap-2">
            <div class="flex items-center gap-2 h-full w-full">
              <div v-for="(entry, idx) in Object.entries(evaluation.stats || {}).slice(0,4)"
                   :key="'stat-first-'+entry[0]"
                   class="bg-neutral-300 bg-opacity-20 p-4 rounded-lg w-1/4 min-w-48 flex flex-col items-center justify-center">
                <div class="first-letter:uppercase">
                  {{ entry[0] }}
                </div>
                <div class="text-lg font-bold">
                  {{ entry[1] }}
                </div>
              </div>
            </div>
            <div class="flex items-center gap-2 h-full w-full">
              <div v-for="(entry, idx) in Object.entries(evaluation.stats || {}).slice(4,7)"
                   :key="'stat-next-'+entry[0]"
                   class="border border-neutral-300 p-4 rounded-lg w-1/3 min-w-48 flex flex-col items-center justify-center">
                <div class="first-letter:uppercase">
                  {{ entry[0] }}
                </div>
                <div class="text-lg font-bold">
                  {{ entry[1] }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="w-full">
        <div class="text-xl font-bold mb-4">
          Répartition des notes
        </div>
        <EvaluationNotesRepartitionChart :notes="evaluation.notes" class="w-full"/>
      </div>


      <ListSkeleton v-if="isLoadingGroupes" class="flex items-center gap-4 w-1/2"></ListSkeleton>
      <div v-else>
        <div class="text-xl font-bold mb-4">
          Résultats par groupe
        </div>
        <Tabs :value="selectedGroupe?.id" scrollable>
          <TabList>
            <Tab v-for="groupe in groupes" :key="groupe.libelle" :value="groupe.id" @click="selectedGroupe = groupe">
              {{ groupe.libelle }}
            </Tab>
          </TabList>
        </Tabs>

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
          <Column field="display" header="Étudiant" :sortable="true" />
          <Column field="note" header="Note" :sortable="true">
            <template #body="slotProps">
              <div v-if="slotProps.data.note !== null && slotProps.data.note !== undefined">
                {{ slotProps.data.note.toFixed(2) }}
              </div>
              <div v-else class="text-neutral-500">
                N/A
              </div>
            </template>
          </Column>
          <Column field="absenceStatus" header="Statut d'absence" :sortable="true">
            <template #body="slotProps">
              <div v-if="slotProps.data.absenceStatus === 'present'">
                Présent
              </div>
              <div v-else-if="slotProps.data.absenceStatus === 'abs_injustifiee'">
                Absent injustifié
              </div>
              <div v-else-if="slotProps.data.absenceStatus === 'abs_justifiee'">
                Absent justifié
              </div>
              <div v-else-if="slotProps.data.absenceStatus === 'dispense'">
                Dispense
              </div>
            </template>
          </Column>
          <Column field="commentaire" header="Commentaire" :sortable="false" />
          <template #footer> {{ etudiants.totalItems }} résultat(s).</template>
        </DataTable>
      </div>
    </div>
  </div>
</template>

<style scoped>

</style>
