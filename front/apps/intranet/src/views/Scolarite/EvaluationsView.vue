<script setup>
import {ref, onMounted, watch} from 'vue';
import { getEvaluationsService, getEnseignementsService, updateEvaluationService } from '@requests';
import { useUsersStore, useDiplomeStore } from '@stores';
import { SimpleSkeleton } from '@components';
import { ErrorView, PermissionGuard } from "@components";
import EvaluationForm from "@/components/Evaluation/EvaluationForm.vue";

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
const showDialog = ref(false);
const selectedEvaluation = ref(null);

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

const updateEvaluationVisibility = async (evaluation) => {
  try {
    await updateEvaluationService(evaluation.id, { visible: evaluation.visible }, '', true);
  } catch (error) {
    hasError.value = true;
    console.error('Error updating evaluation visibility:', error);
  }
}

const openEvaluationDialog = (evaluation) => {
  selectedEvaluation.value = evaluation;
  showDialog.value = true;
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
          <Button label="Initialiser les évaluations" icon="pi pi-plus-circle" severity="primary" size="small"/>
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
                    <div class="flex justify-between items-center gap-4">
                      <div class="text-sm flex items-center gap-1"><i class="pi pi-users"></i>Notes saisies</div>
                      <div class="text-sm flex items-center gap-1"><span class="font-bold">20/100</span> (20%)</div>
                    </div>
                    <ProgressBar :value="20" class="!h-3"></ProgressBar>
                    <div class="flex items-center justify-between">
                      <div class="text-sm"></div>
                      <div class="text-sm">0/2 évaluations complètes</div>
                    </div>
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
                        <Message severity="info" size="small">
                          {{evaluation.type ?? ' - Type inconnu'}}
                        </Message>
                      </div>
                      <div>
                        <Message
                            :severity="evaluation.etat === 'non_initialisee' ? 'error' : evaluation.etat === 'planifiee' ? 'warn' : 'success'"
                            :icon="evaluation.etat === 'non_initialisee' ? 'pi pi-exclamation-triangle' : evaluation.etat === 'planifiee' ? 'pi pi-clock' : 'pi pi-check-circle'"
                            size="small">
                          {{ evaluation.etat === 'non_initialisee' ? 'À compléter' : evaluation.etat === 'planifiee' ? 'À saisir' : 'Complet' }}
                        </Message>
                      </div>
                    </div>

                    <div>
                      <div class="flex justify-between items-center gap-4">
                        <div class="text-sm flex items-center gap-1"><i class="pi pi-users"></i>Notes saisies</div>
                        <div class="text-sm flex items-center gap-1"><span class="font-bold">0/25</span> (0%)</div>
                      </div>
                      <ProgressBar :value="evaluation.notes?.length" class="!h-3"></ProgressBar>
                    </div>
                  </div>
                  <Divider/>
                  <div class="flex justify-between items-center gap-4">
                    <div class="flex items-center justify-start gap-2">
                      <Button v-if="evaluation.etat !== 'non_initialisee' " label="Saisir les notes" icon="pi pi-file-edit" outlined severity="primary" size="small"/>
                      <Button v-if="evaluation.etat !== 'non_initialisee' " label="Modifier" icon="pi pi-pencil" outlined severity="warn" size="small"/>
                      <Button v-if="evaluation.etat === 'non_initialisee' " label="Initialiser" icon="pi pi-plus" outlined severity="primary" size="small" @click="openEvaluationDialog(evaluation.id)"/>
                      <Button label="Supprimer" icon="pi pi-trash" outlined severity="danger" size="small"/>
                    </div>
                    <div class="flex items-center justify-end gap-4">
                      <div class="flex items-center justify-end gap-1">
                        <i :class="evaluation.visible ? 'pi pi-eye text-green-500' : 'pi pi-eye-slash text-gray-400'"></i>
                        <span class="text-sm">{{ evaluation.visible ? 'Visible' : 'Masquée' }}</span>
                        <ToggleSwitch v-model="evaluation.visible" @change="updateEvaluationVisibility(evaluation)"/>
                      </div>
                      <PermissionGuard :permission="['isChefDepartement']">
                        <div class="flex items-center justify-end gap-1">
                          <i :class="evaluation.modifiable ? 'pi pi-lock-open text-green-500' : 'pi pi-lock text-gray-400'"></i>
                          <span class="text-sm">{{ evaluation.modifiable ? 'Modifiable' : 'Non-modifiable' }}</span>
                          <ToggleSwitch v-model="evaluation.modifiable" @change="updateEvaluationVisibility(evaluation)"/>
                        </div>
                      </PermissionGuard>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else class="flex justify-center">
                <Message severity="warn" class="w-fit p-4" icon="pi pi-exclamation-triangle">
                  Aucune évaluation trouvée.
                </Message>
              </div>
              <!--              <div class="flex justify-center mt-4">-->
              <!--                <Button label="Ajouter une évaluation" icon="pi pi-plus-circle" severity="primary" size="small"/>-->
              <!--              </div>-->
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

  <Dialog header="" :visible="showDialog" modal :closable="true" :dismissable-mask="true" :style="{ width: '50vw' }">
    <EvaluationForm :evaluationId="selectedEvaluation"/>
  </Dialog>
</template>

<style scoped>
:deep(.p-accordionpanel) {
  @apply border border-neutral-300 dark:border-neutral-600 rounded-md mb-4;
}
:deep(.p-accordioncontent-content) {
  @apply bg-neutral-200 bg-opacity-20 p-4;
}
:deep(.p-message-content) {
  @apply !py-0 !px-2;
}
</style>
