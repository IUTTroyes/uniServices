<script setup>
import {computed, onMounted, ref, watch} from 'vue';
import { useRoute } from 'vue-router';
import {
  getAnneeService,
  getEnseignementsService,
  getEtudiantsService,
  getEvaluationsService,
  getGroupesService,
  getSemestresService,
  updateEvaluationService
} from '@requests';
import {useUsersStore, useAnneeStore, useSemestreStore} from '@stores';
import {ErrorView, PermissionGuard, SimpleSkeleton, ListSkeleton} from '@components';
import EvaluationForm from "@/components/Evaluation/EvaluationForm.vue";
import EvaluationSaisieNotesForm from "@/components/Evaluation/EvaluationSaisieNotesForm.vue";
import EvaluationListeInitForm from "../../components/Evaluation/EvaluationListeInitForm.vue";
import {useToast} from "primevue/usetoast";
import EvaluationStatistiques from "../../components/Evaluation/EvaluationStatistiques.vue";
import EvaluationCard from "@/components/Evaluation/EvaluationCard.vue";

const toast = useToast();
const route = useRoute();
const usersStore = useUsersStore();
const anneeStore = useAnneeStore();
const semestreStore = useSemestreStore();
const hasError = ref(false);
const selectedAnneeUniversitaire = JSON.parse(localStorage.getItem('selectedAnneeUniv'));
const departementId = ref(null);

// Années et semestres
const annees = ref([]);
const annee = ref({});
const semestres = ref([]);
const semestre = ref({});
const isLoadingAnnees = ref(true);
const isLoadingSemestres = ref(true);

const enseignements = ref([]);
const isLoadingEnseignements = ref(true);
const evaluations = ref([]);
const isLoadingEvaluations = ref(true);
const showDialog = ref(false);
const dialogMode = ref(''); // 'init' | 'edit' | 'saisie'
const dialogHeader = ref('');
const selectedEvaluation = ref(null);

// Cache: expected total notes per (semestreId, typeGroupe)
const expectedTotalsByType = ref({});

onMounted(async () => {
  departementId.value = usersStore.departementDefaut.id;
  await getAnnees();
  await getAnnee();
  await getSemestres();
  // Sélectionner le premier semestre de l'année par défaut
  if (semestres.value.length > 0 && !semestre.value.id) {
    semestre.value = semestres.value[0];
  }
});

// watcher pour relancer getEnseignements quand semestre change
watch(semestre, async (newSemestre, oldSemestre) => {
  if (newSemestre?.id && newSemestre.id !== oldSemestre?.id) {
    await getEnseignements();
  }
});

// watcher pour relancer getSemestres quand annee change
watch(annee, async (newAnnee, oldAnnee) => {
  if (newAnnee?.id && newAnnee.id !== oldAnnee?.id) {
    await getSemestres();
    // si le semestre sélectionné n'est pas dans la nouvelle liste, on sélectionne le premier de la liste
    if (!semestres.value.some(s => s.id === semestre.value.id)) {
      semestre.value = semestres.value[0] || {};
    }
    await anneeStore.setSelectedAnnee(newAnnee);
  }
});

const getAnnees = async () => {
  isLoadingAnnees.value = true;
  if (anneeStore.annees && Array.isArray(anneeStore.annees) && anneeStore.annees.length > 0) {
    annees.value = anneeStore.annees;
  } else {
    try {
      const params = {
        departement: departementId.value,
        actif: true,
      };
      await anneeStore.getAnneesDepartement(params);
      annees.value = Array.isArray(anneeStore.annees) ? anneeStore.annees : [];
    } catch (error) {
      console.error("Erreur lors de la récupération des années :", error);
      hasError.value = true;
    }
  }
  isLoadingAnnees.value = false;
};

const getAnnee = async () => {
  isLoadingAnnees.value = true;
  hasError.value = false;
  try {
    // Récupération de l'id de l'année via l'URL ou sélection de la première année disponible
    const anneeId = route.params.anneeId;
    if (anneeId) {
      annee.value = await getAnneeService(anneeId);
    } else if (annees.value.length > 0) {
      annee.value = annees.value[0];
    }
    await anneeStore.setSelectedAnnee(annee.value);
  } catch (error) {
    hasError.value = true;
    console.error("Erreur lors de la récupération de l'année :", error);
  } finally {
    isLoadingAnnees.value = false;
  }
};

const getSemestres = async () => {
  isLoadingSemestres.value = true;
  hasError.value = false;
  try {
    const params = {
      annee: annee.value.id,
    };
    semestres.value = await getSemestresService(params, '/mini');
  } catch (error) {
    hasError.value = true;
    console.error("Erreur lors de la récupération des semestres :", error);
  } finally {
    isLoadingSemestres.value = false;
  }
};

const getEnseignements = async () => {
  isLoadingEnseignements.value = true;
  try {
    if (!semestre.value) {
      enseignements.value = [];
      return;
    }
    const params = {
      semestre: semestre.value.id,
    };
    enseignements.value = await getEnseignementsService(params);
    for (const enseignement of enseignements.value) {
      await getEvaluations(enseignement.id);
      enseignement.evaluations = evaluations.value;
    }
    // Preload expected totals per typeGroupe for the semestre based on loaded evaluations
    await preloadExpectedTotalsForSemestre(semestre.value.id, enseignements.value);
    // Calculer la progression pour chaque enseignement
    for (const enseignement of enseignements.value) {
      await calcEnseignementProgress(enseignement);
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
      anneeUniversitaire: selectedAnneeUniversitaire.id
    };
    evaluations.value = await getEvaluationsService(params);
    // Calculer la progression pour chaque évaluation
    for (const evaluation of evaluations.value) {
      await calcEvaluationProgress(evaluation);
    }
    return evaluations.value;
  } catch (error) {
    hasError.value = true;
    console.error('Error fetching evaluations:', error);
  } finally {
    isLoadingEvaluations.value = false;
  }
};

// Compute expected totals for notes per typeGroupe in a semestre and cache them
const makeTypeKey = (semestreId, typeGroupe) => `${semestreId || 'na'}::${typeGroupe || 'na'}`;

const getExpectedTotalForType = async (semestreId, typeGroupe) => {
  const key = makeTypeKey(semestreId, typeGroupe);
  if (expectedTotalsByType.value[key] !== undefined) return expectedTotalsByType.value[key];
  if (!semestreId || !typeGroupe) {
    expectedTotalsByType.value[key] = 0;
    return 0;
  }
  try {
    // Fetch groups for semestre and type
    const groupes = await getGroupesService({ semestre: semestreId, type: typeGroupe }, '/mini');
    let total = 0;
    // For each group, fetch students and sum
    try {
      for (const g of Array.isArray(groupes) ? groupes : []) {
        const students = await getEtudiantsService({ groupe: g.id });
        total += Array.isArray(students) ? students.length : 0;
      }
    } catch (e) {
      // ignore errors for all groups
    }
    expectedTotalsByType.value[key] = total;
    return total;
  } catch (e) {
    expectedTotalsByType.value[key] = 0;
    return 0;
  }
};

const preloadExpectedTotalsForSemestre = async (semestreId, enseignementsList) => {
  // Collect unique types from all evaluations
  const types = new Set();
  for (const ens of enseignementsList || []) {
    for (const ev of ens.evaluations || []) {
      if (ev?.typeGroupe) types.add(ev.typeGroupe);
    }
  }
  const promises = Array.from(types).map(t => getExpectedTotalForType(semestreId, t));
  await Promise.all(promises);
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

const calcEnseignementProgress = (enseignement) => {
  const evals = Array.isArray(enseignement?.evaluations) ? enseignement.evaluations : [];

  // Comptes globaux basés uniquement sur les notes existantes
  enseignement.enteredTotal = 0;
  enseignement.expectedTotal = 0;

  for (const e of evals) {
    const notesExistantes = Array.isArray(e?.notes) ? e.notes.filter(n => n != null) : [];
    const entered = notesExistantes.filter(n => n.note !== null && n.note !== undefined).length;
    enseignement.enteredTotal += entered;
    enseignement.expectedTotal += notesExistantes.length;
  }

  // Le pourcentage global n'est calculé que si TOUTES les évaluations n'ont pas pour état 'non_initialisee'
  const allHaveNotes = evals.length > 0 && evals.every(e => e.etat !== 'non_initialisee');
  enseignement.hasIncompleteEvaluations = !allHaveNotes;

  if (allHaveNotes && enseignement.expectedTotal > 0) {
    enseignement.percent = Math.round((enseignement.enteredTotal / enseignement.expectedTotal) * 100);
  } else {
    // pas de pourcentage affiché si toutes les évaluations n'ont pas de notes
    enseignement.percent = null;
  }
}

const updateEvaluationVisibility = async (evaluation) => {
  try {
    await updateEvaluationService(evaluation.id, { visible: evaluation.visible }, true);
  } catch (error) {
    hasError.value = true;
    console.error('Error updating evaluation visibility:', error);
  }
}

const updateEvaluationEdit = async (evaluation) => {
  try {
    await updateEvaluationService(evaluation.id, { modifiable: evaluation.modifiable }, true);
  } catch (error) {
    hasError.value = true;
    console.error('Error updating evaluation modifiable:', error);
  }
};

// Choix du composant selon le mode
const dialogComponent = computed(() => {
  return dialogMode.value === 'saisie' ? EvaluationSaisieNotesForm : dialogMode.value === 'edit' ? EvaluationForm : dialogMode.value === 'initAll' ? EvaluationListeInitForm : dialogMode.value === 'stat' ? EvaluationStatistiques : null;
});

// Ouvre le dialog en passant l'id de l'évaluation et le mode ('init'|'edit'|'saisie'|'stat')
const openEvaluationDialog = (evaluationId, mode = 'edit', header) => {
  if (evaluationId) {
    selectedEvaluation.value = evaluationId;
  }
  dialogMode.value = mode;
  dialogHeader.value = header || (mode === 'saisie' ? 'Saisie des notes' : mode === 'init' ? 'Initialiser l\'évaluation' : 'Modifier l\'évaluation');
  showDialog.value = true;
};

const onEvaluationClosed = async () => {
  showDialog.value = false;
  selectedEvaluation.value = null;
  // rafraîchir la liste des enseignements/évaluations pour le semestre courant
  // await getEnseignements();
};
const onEvaluationSaved = async () => {
  // rafraîchir la liste des enseignements/évaluations pour le semestre courant
  // await getEnseignements();
};
</script>

<template>
  <div class="card">
    <ErrorView v-if="hasError"></ErrorView>
    <div v-else>
      <div class="flex justify-between items-end w-full">
        <div>
          <h2 class="text-2xl font-bold flex items-end gap-2">
            Évaluations du
            <SimpleSkeleton v-if="isLoadingSemestres" class="!w-32"></SimpleSkeleton>
            <span v-else>{{ semestre.libelle }}</span>
          </h2>
          <em>Gérer les évaluations et la saisie des notes</em>
        </div>
        <SimpleSkeleton v-if="isLoadingAnnees || isLoadingSemestres" class="!w-60 !h-10"></SimpleSkeleton>
        <div v-else class="flex gap-4">
          <Select class="w-60" v-model="annee" option-label="libelle" :options="annees" placeholder="Changer d'année"/>
          <Select class="w-60" v-model="semestre" option-label="libelle" :options="semestres" placeholder="Changer de semestre"/>
        </div>
      </div>
      <Divider />

      <ListSkeleton v-if="isLoadingEnseignements" class="w-1/2"/>
      <div v-else>
        <div class="flex justify-end gap-4">
          <Button label="Initialiser toutes les évaluations" icon="pi pi-plus-circle" severity="primary" size="small" @click="openEvaluationDialog('', 'initAll', 'Initialisation des évaluations')"/>
        </div>
        <Accordion v-if="semestre && enseignements.length !== 0" value="0" class="mt-4">
          <AccordionPanel v-for="enseignement in enseignements" :value="enseignement.id" :key="enseignement.id">
            <AccordionHeader>
              <div class="flex flex-col gap-2 w-full">
                <div class="flex justify-between items-center w-full">
                  <div class="flex justify-between items-center">
                    <div class="flex items-center justify-start gap-4">
                      <div class="bg-primary-700 p-3 rounded-md">
                        <i class="pi pi-book text-white w-5 h-5 text-center"></i>
                      </div>
                      <div class="text-xl font-bold">{{enseignement.codeEnseignement}} - {{enseignement.libelle}}</div>
                      <div class="flex items-center gap-2">
                        <Button label="" icon="pi pi-eye" outlined severity="info" size="small" @click="" v-tooltip.top="`Voir les détails de l'enseignement`"/>
                        <Button label="" icon="pi pi-user" outlined severity="warn" size="small" @click="" v-tooltip.top="`Voir le prévisionnel de l'enseignement`"/>
                      </div>
                    </div>
                  </div>
                  <div class="text-sm text-muted-color mr-4">
                    {{enseignement.evaluations.length}} évaluations
                  </div>
                </div>
                <SimpleSkeleton v-if="isLoadingEvaluations" class="w-full"/>
                <div class="mr-4">
                  <div class="p-2 w-full bg-neutral-50 rounded-md border border-neutral-300 dark:border-neutral-600 dark:bg-neutral-900 flex flex-col gap-2">
                    <div class="flex justify-between items-center gap-4">
                      <div class="flex items-center gap-1 font-bold"><i class="pi pi-check-circle text-primary"></i>Progression Globale</div>
                    </div>
                    <Message v-if="enseignement.hasIncompleteEvaluations" severity="warn" size="small" icon="pi pi-exclamation-triangle">
                      Les pourcentages ne sont pas affichés car toutes les évaluations de cet enseignement ne sont pas initialisées.
                    </Message>
                    <div class="flex justify-between items-center gap-4">
                      <div class="text-sm flex items-center gap-1"><i class="pi pi-users"></i>Notes saisies</div>
                      <div v-if="enseignement.hasIncompleteEvaluations">
                        <span class="font-bold">—/—</span>
                        (—)
                      </div>
                      <div v-else class="text-sm flex items-center gap-1">
                        <span class="font-bold">{{ enseignement.enteredTotal }}/{{ enseignement.expectedTotal }}</span>
                        ({{ enseignement.percent === null ? '—' : enseignement.percent + '%' }})
                      </div>
                    </div>
                    <ProgressBar :value="enseignement.percent ?? 0" class="!h-3"></ProgressBar>
                  </div>
                </div>
              </div>
            </AccordionHeader>
            <AccordionContent>
              <SimpleSkeleton v-if="isLoadingEvaluations" class="w-full"/>
              <div v-else>
                <div v-if="enseignement.evaluations && enseignement.evaluations.length !== 0" class="flex flex-col gap-2">
                  <EvaluationCard
                    v-for="evaluation in enseignement.evaluations"
                    :key="evaluation.id"
                    :evaluation="evaluation"
                    :semestreId="semestre.id"
                    @open-dialog="openEvaluationDialog"
                    @update-visibility="updateEvaluationVisibility"
                    @update-edit="updateEvaluationEdit"
                  />
                </div>
                <div v-else class="flex justify-center">
                  <Message severity="warn" class="w-fit p-4" icon="pi pi-exclamation-triangle">
                    Aucune évaluation trouvée.
                  </Message>
                </div>
              </div>
            </AccordionContent>
          </AccordionPanel>
        </Accordion>
        <div v-else class="flex justify-center">
          <Message severity="warn" class="w-fit p-4" icon="pi pi-exclamation-triangle">
            Aucun enseignement trouvé.
          </Message>
        </div>
      </div>
    </div>
  </div>

  <Dialog :header="dialogHeader" v-model:visible="showDialog" modal :style="{ width: '70vw' }" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">
    <component v-if="showDialog" :is="dialogComponent" :evaluationId="selectedEvaluation" :enseignements="enseignements" :semestreId="semestre.id" @saved="onEvaluationSaved" @close="onEvaluationClosed"/>
  </Dialog>
</template>

<style scoped>
:deep(.p-accordionpanel) {
  @apply border border-neutral-300 dark:border-neutral-600 rounded-md mb-4;
}
:deep(.p-accordioncontent-content) {
  @apply bg-neutral-200 bg-opacity-20 p-4;
}

</style>
