<script setup>
import {computed, onMounted, ref, watch} from 'vue';
import {
  getEnseignementsService,
  getEtudiantsService,
  getEvaluationsService,
  getGroupesService,
  updateEvaluationService
} from '@requests/index.js';
import {useDiplomeStore, useUsersStore} from '@stores/index.js';
import {ErrorView, PermissionGuard, SimpleSkeleton} from '@components/index.js';
import EvaluationForm from "@/components/Evaluation/EvaluationForm.vue";
import EvaluationSaisieNotesForm from "@/components/Evaluation/EvaluationSaisieNotesForm.vue";
import EvaluationListeInitForm from "../../components/Evaluation/EvaluationListeInitForm.vue";
import {useToast} from "primevue/usetoast";

const toast = useToast();
const usersStore = useUsersStore();
const hasError = ref(false);
const diplomeStore = useDiplomeStore();
const selectedAnneeUniversitaire = JSON.parse(localStorage.getItem('selectedAnneeUniv'));
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
const showDialog = ref(false);
const dialogMode = ref(''); // 'init' | 'edit' | 'saisie'
const dialogHeader = ref('');
const selectedEvaluation = ref(null);

// Cache: expected total notes per (semestreId, typeGroupe)
const expectedTotalsByType = ref({});

onMounted(() => {
  departementId.value = usersStore.departementDefaut.id;
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
    if (!selectedSemestre.value) {
      enseignements.value = [];
      return;
    }
    const params = {
      semestre: selectedSemestre.value.id,
    };
    enseignements.value = await getEnseignementsService(params);
    for (const enseignement of enseignements.value) {
      await getEvaluations(enseignement.id);
      enseignement.evaluations = evaluations.value;
    }
    // Preload expected totals per typeGroupe for the semestre based on loaded evaluations
    await preloadExpectedTotalsForSemestre(selectedSemestre.value.id, enseignements.value);
    // Calculer la progression pour chaque enseignement
    for (const enseignement of enseignements.value) {
      calcEnseignementProgress(enseignement);
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
    console.log(params);
    evaluations.value = await getEvaluationsService(params);
    // Calculer la progression pour chaque évaluation
    for (const evaluation of evaluations.value) {
      calcEvaluationProgress(evaluation);
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
    await updateEvaluationService(evaluation.id, { visible: evaluation.visible }, '', true);

    if (!hasError.value) {
      toast.add({ severity: 'success', summary: 'Succès', detail: 'Visibilité de l\'évaluation mise à jour.', life: 3000 });
    }
  } catch (error) {
    hasError.value = true;
    console.error('Error updating evaluation visibility:', error);
  }
}

const updateEvaluationEdit = async (evaluation) => {
  try {
    await updateEvaluationService(evaluation.id, { modifiable: evaluation.modifiable }, '', true);

    if (!hasError.value) {
      toast.add({ severity: 'success', summary: 'Succès', detail: 'Paramètre de modification de l\'évaluation mis à jour.', life: 3000 });
    }
  } catch (error) {
    hasError.value = true;
    console.error('Error updating evaluation modifiable:', error);
  }
};

// Choix du composant selon le mode
const dialogComponent = computed(() => {
  return dialogMode.value === 'saisie' ? EvaluationSaisieNotesForm : dialogMode.value === 'edit' ? EvaluationForm : dialogMode.value === 'initAll' ? EvaluationListeInitForm : null;
});

// Ouvre le dialog en passant l'id de l'évaluation et le mode ('init'|'edit'|'saisie')
const openEvaluationDialog = (evaluationId, mode = 'edit', header) => {
  if (evaluationId) {
    selectedEvaluation.value = evaluationId;
  }
  dialogMode.value = mode;
  dialogHeader.value = header || (mode === 'saisie' ? 'Saisie des notes' : mode === 'init' ? 'Initialiser l\'évaluation' : 'Modifier l\'évaluation');
  showDialog.value = true;
};

const getSeverity = (type) => {
  switch (type) {
    case 'Examen':
      return 'error';
    case 'Travaux Pratiques':
      return 'info';
    case 'Projet':
      return 'warn';
    default:
      return 'secondary';
  }
};

const onEvaluationClosed = async () => {
  showDialog.value = false;
  selectedEvaluation.value = null;
  // rafraîchir la liste des enseignements/évaluations pour le semestre courant
  await getEnseignements();
};
const onEvaluationSaved = async () => {
  // rafraîchir la liste des enseignements/évaluations pour le semestre courant
  await getEnseignements();
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
        <div class="flex justify-end gap-4">
          <Button label="Initialiser toutes les évaluations" icon="pi pi-plus-circle" severity="primary" size="small" @click="openEvaluationDialog('', 'initAll', 'Initialisation des évaluations')"/>
        </div>
        <Accordion v-if="selectedSemestre && enseignements.length !== 0" value="0" class="mt-4">
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
                    </div>
                  </div>
                  <div class="text-sm text-muted-color mr-4">
                    {{enseignement.evaluations.length}} évaluations
                  </div>
                </div>
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
              <div v-if="enseignement.evaluations && enseignement.evaluations.length !== 0" class="flex flex-col gap-2">
                <div v-for="evaluation in enseignement.evaluations" class="card m-0 py-2 px-4">
                  <div class="flex flex-col gap-4">
                    <div class="flex justify-between items-center gap-4">
                      <div class="flex items-center gap-2">
                        <div class="text-lg font-bold">
                          {{ evaluation.typeIcon }} {{evaluation.libelle}}
                        </div>
                        <Message v-if="evaluation.type" :severity="getSeverity(evaluation.type)" size="small">
                          {{evaluation.type}}
                        </Message>
                        <Message v-if="evaluation.typeGroupe" severity="secondary" size="small">
                          {{evaluation.typeGroupe}}
                        </Message>
                      </div>
                      <div>
                        <Message
                            :severity="evaluation.etat === 'non_initialisee' ? 'error' : evaluation.etat === 'initialisee' ? 'info' : evaluation.etat === 'planifiee' ? 'warn' : evaluation.etat === 'complet' ? 'success' : 'error'"
                            :icon="evaluation.etat === 'non_initialisee' ? 'pi pi-exclamation-triangle' : evaluation.etat === 'initialisee' ? 'pi pi-info-circle' : evaluation.etat === 'planifiee' ? 'pi pi-clock'  : evaluation.etat === 'complet' ? 'pi pi-check-circle' : 'pi pi-exclamation-triangle'"
                            size="small">
                          {{ evaluation.etat === 'non_initialisee' ? 'À initialiser' : evaluation.etat === 'initialisee' ? 'Initialisée' : evaluation.etat === 'planifiee' ? 'À saisir' : evaluation.etat === 'complet' ? 'Complet' : 'Erreur' }}
                        </Message>
                      </div>
                    </div>

                    <div>
                      <div class="flex justify-between items-center gap-4">
                        <div class="text-sm flex items-center gap-1"><i class="pi pi-users"></i>Notes saisies</div>
                        <div class="text-sm flex items-center gap-1">
                          <span class="font-bold">{{ evaluation.entered }}/{{ evaluation.total }}</span>
                          ({{ evaluation.percent }}%)
                        </div>
                      </div>
                      <ProgressBar :value="evaluation.percent" class="!h-3"></ProgressBar>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                      <div>Saisie autorisée :</div>
                      <div v-if="evaluation.personnelAutorise?.length > 0" v-for="personnel in evaluation.personnelAutorise" class="border border-neutral-200 dark:border-neutral-600 rounded-md px-3 py-1 text-sm bg-neutral-100 dark:bg-neutral-800 flex items-center gap-2">
                        {{personnel.display}}
                      </div>
                      <div v-else class="border border-neutral-200 dark:border-neutral-600 rounded-md px-3 py-1 text-sm bg-neutral-100 dark:bg-neutral-800 flex items-center gap-2">
                        Aucun personnel autorisé
                      </div>
                    </div>
                  </div>
                  <PermissionGuard :permission="{ permission: 'canManageEvaluation', context: { evaluation } }">
                    <Divider/>
                    <div class="flex justify-between items-center gap-4">
                      <div class="flex items-center justify-start gap-2">
                        <Button v-if="evaluation.etat !== 'non_initialisee'" label="Saisir les notes" icon="pi pi-file-edit" outlined severity="primary" size="small" @click="openEvaluationDialog(evaluation.id, 'saisie', 'Saisie des notes')"/>
                        <Button v-if="evaluation.etat !== 'non_initialisee' " label="Modifier" icon="pi pi-pencil" outlined severity="warn" size="small" @click="openEvaluationDialog(evaluation.id, 'edit', 'Édition de l\'évaluation')"/>
                        <Button v-if="evaluation.etat === 'non_initialisee' " label="Initialiser" icon="pi pi-plus" outlined severity="primary" size="small" @click="openEvaluationDialog(evaluation.id, 'edit', 'Initialiser l\'évaluation')"/>
                      </div>
                      <div class="flex items-center justify-end gap-4">
                        <div class="flex items-center justify-end gap-1">
                          <i :class="evaluation.visible ? 'pi pi-eye text-green-500' : 'pi pi-eye-slash text-gray-400'"></i>
                          <span class="text-sm">{{ evaluation.visible ? 'Visible' : 'Masquée' }}</span>
                          <ToggleSwitch v-model="evaluation.visible" @change="updateEvaluationVisibility(evaluation)"/>
                        </div>
                        <div class="flex items-center justify-end gap-1">
                          <i :class="evaluation.modifiable ? 'pi pi-lock-open text-green-500' : 'pi pi-lock text-gray-400'"></i>
                          <span class="text-sm">{{ evaluation.modifiable ? 'Modifiable' : 'Non-modifiable' }}</span>
                          <ToggleSwitch v-model="evaluation.modifiable" @change="updateEvaluationEdit(evaluation)"/>
                        </div>
                      </div>
                    </div>
                  </PermissionGuard>
                </div>
              </div>
              <div v-else class="flex justify-center">
                <Message severity="warn" class="w-fit p-4" icon="pi pi-exclamation-triangle">
                  Aucune évaluation trouvée.
                </Message>
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
    <component v-if="showDialog" :is="dialogComponent" :evaluationId="selectedEvaluation" :enseignements="enseignements" :semestreId="selectedSemestre.id" @saved="onEvaluationSaved" @close="onEvaluationClosed"/>
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
