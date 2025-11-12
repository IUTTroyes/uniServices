<script setup>
import { onMounted, ref } from 'vue';
import { ValidatedInput, validationRules, ListSkeleton } from '@components';
import { useUsersStore } from '@stores/user_stores/userStore.js';
import { getPersonnelsService, updateEvaluationService } from '@requests';

const emit = defineEmits(['saved', 'close']);

const isLoading = ref(true);
const hasError = ref(false);
const formValid = ref(true);
const formErrors = ref({});

const userStore = useUsersStore();
const departementId = ref(null);

const props = defineProps({
  enseignements: { type: Array, required: true },
  semestreId: { type: Number, required: false }
});

const ensureFormState = (evaluationId) => {
  if (!formErrors.value[evaluationId]) formErrors.value[evaluationId] = {};
  if (savingMap.value[evaluationId] === undefined) savingMap.value[evaluationId] = false;
  if (errorMap.value[evaluationId] === undefined) errorMap.value[evaluationId] = null;
};

const handleValidation = (evaluationId, field, result) => {
  ensureFormState(evaluationId);
  formErrors.value[evaluationId] = {
    ...formErrors.value[evaluationId],
    [field]: result.isValid ? null : result.errorMessage,
  };
};

const isEvaluationValid = (evaluationId) => {
  const fields = formErrors.value[evaluationId] || {};
  return Object.values(fields).every((e) => e === null);
};

const getPersonnelForEnseignement = async (enseignement) => {
  try {
    const params = { enseignement: enseignement.id };
    enseignement.personnels = await getPersonnelsService(params);
  } catch (e) {
    // keep empty on error
    enseignement.personnels = [];
  }
};

onMounted(async () => {
  try {
    departementId.value = userStore.departementDefaut?.id ?? null;
    // Charger les personnels pour chaque enseignement
    const promises = (props.enseignements || []).map((ens) => getPersonnelForEnseignement(ens));
    await Promise.all(promises);
  } finally {
    isLoading.value = false;
  }
});

const saveEvaluation = async (evaluation, enseignement) => {
  ensureFormState(evaluation.id);
  errorMap.value[evaluation.id] = null;

  // Validation simple: coeff required, typeGroupe required, personnelAutorise required
  const hasCoeff = evaluation.coeff !== null && evaluation.coeff !== undefined && evaluation.coeff !== '';
  const hasType = !!evaluation.typeGroupe;
  const hasIntervenants = Array.isArray(evaluation.personnelAutorise) && evaluation.personnelAutorise.length > 0;

  if (!hasCoeff || !hasType || !hasIntervenants || !isEvaluationValid(evaluation.id)) {
    errorMap.value[evaluation.id] = "Veuillez remplir tous les champs obligatoires (coeff, type de groupe, intervenants).";
    return;
  }

  const payload = {
    coeff: evaluation.coeff,
    typeGroupe: evaluation.typeGroupe,
    personnelAutorise: evaluation.personnelAutorise,
  };

  try {
    savingMap.value[evaluation.id] = true;
    await updateEvaluationService(evaluation.id, payload, '', true);
    emit('saved');
  } catch (e) {
    errorMap.value[evaluation.id] = "Erreur lors de l'enregistrement de l'évaluation.";
    // eslint-disable-next-line no-console
    console.error(e);
  } finally {
    savingMap.value[evaluation.id] = false;
  }
};
</script>

<template>
  <ListSkeleton v-if="isLoading" />
  <div v-else class="pt-4">
    <Message severity="info" icon="pi pi-info-circle" class="mb-4">
      Les listes d'enseignant sont filtrées en fonction des données du prévisionnel pour chaque matière, SAE ou ressource.
    </Message>

    <div v-for="enseignement in enseignements" :key="enseignement.id" class="p-mb-4 p-p-4 p-border-1 p-border-round p-shadow-2">
      <div class="p-2 font-bold bg-neutral-100 dark:bg-neutral-800">{{ enseignement.libelle }}</div>

      <div v-if="!enseignement.evaluations || enseignement.evaluations.length === 0" class="text-sm text-neutral-500">
        Aucune évaluation.
      </div>

      <div v-else>
        <table class="w-full border-collapse">
          <thead>
          <tr class="text-left">
            <th class="p-2 border-b">Évaluation</th>
            <th class="p-2 border-b">Coefficient</th>
            <th class="p-2 border-b">Type de groupe</th>
            <th class="p-2 border-b">Intervenants</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="evaluation in enseignement.evaluations" :key="evaluation.id">
            <td class="p-2 align-top">
              <ValidatedInput
                class="w-full"
                v-model="evaluation.libelle"
                :name="`libelle_${evaluation.id}`"
                label="Évaluation"
                type="text"
                :rules="[]"
                @validation="result => handleValidation(evaluation.id, 'libelle', result)"
              />
            </td>
            <td class="p-2 align-top">
              <ValidatedInput
                class="w-full"
                v-model="evaluation.coeff"
                :name="`coeff_${evaluation.id}`"
                label="Coefficient"
                type="number"
                :rules="[]"
                @validation="result => handleValidation(evaluation.id, 'coeff', result)"
                inputId="minmax" :min="0" :max="100"
              />
            </td>
            <td class="p-2 align-top">
              <div class="w-full">
                <ValidatedInput
                  v-if="evaluation.typeGroupeChoices && evaluation.typeGroupeChoices.length > 0"
                  v-model="evaluation.typeGroupe"
                  :name="`typeGroupe_${evaluation.id}`"
                  label="Type de groupe"
                  type="select"
                  :options="(evaluation.typeGroupeChoices || []).map(c => ({ label: c, value: c }))"
                  :rules="[]"
                  @validation="result => handleValidation(evaluation.id, 'typeGroupe', result)"
                />
                <div v-else class="text-xs text-neutral-500">
                  Aucun type de groupe disponible.
                </div>
              </div>
            </td>
            <td class="p-2 align-top">
              <ValidatedInput
                class="w-full"
                v-model="evaluation.personnelAutorise"
                :name="`personnelAutorise_${evaluation.id}`"
                label="Intervenants"
                type="multiselect"
                :options="(enseignement.personnels || []).map(p => ({ label: p.display || `${p.nom} ${p.prenom}`, value: `/api/personnels/${p.id}` }))"
                :rules="[]"
                @validation="result => handleValidation(evaluation.id, 'personnelAutorise', result)"
                :filter="true"
              />
            </td>
          </tr>
          </tbody>
        </table>
      </div>
      <Divider></Divider>
    </div>
    <div class="flex justify-center items-center gap-4 mt-4">
      <Button class="w-1/2" label="Initialiser les évaluations" @click="" :disabled="!formValid" />
      <Button class="w-1/2" label="Annuler" severity="secondary" @click="" :disabled="!formValid" />
    </div>
  </div>
</template>

<style scoped>
:deep(.p-component) {
  width: 100%;
}
</style>
