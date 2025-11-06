<script setup>
import {onMounted, ref} from 'vue';
import { getEvaluationService, getPersonnelsService } from '@requests';
import { ValidatedInput, validationRules, ErrorView, PermissionGuard, ListSkeleton } from "@components";

const formValid = ref(true);
const formErrors = ref({});
const evaluation = ref({})
const isLoading = ref(true);
const typesGroupe = ref()
const departementId = ref(null)
const personnels = ref([])

const props = defineProps({
  evaluationId: {
    type: Number,
    required: true
  }
})

onMounted(async () => {
  departementId.value = localStorage.getItem('departement');
  await getEvaluation();
  await getPersonnels();
});

const getEvaluation = async () => {
  try {
    isLoading.value = true;
    evaluation.value = await getEvaluationService(props.evaluationId);
  } catch (error) {
    console.error('Erreur lors du chargement de l\'évaluation:', error);
  } finally {
    isLoading.value = false;
  }
};

const getPersonnels = async () => {
  try {
    isLoading.value = true;
    const params = {
      departement: departementId.value
    };
    personnels.value = await getPersonnelsService(params);
    return personnels;
  } catch (error) {
    console.error('Erreur lors du chargement des personnels:', error);
    return [];
  } finally {
    console.log(personnels.value);
    isLoading.value = false;
  }
};

const handleValidation = (field, result) => {
  formErrors.value = {
    ...formErrors.value,
    [field]: result.isValid ? null : result.errorMessage
  };
  formValid.value = Object.values(formErrors.value).every(error => error === null);
};

const updateEvaluation = () => {
  console.log(evaluation.value);
}

</script>

<template>
  {{props.evaluation}}

  <h2 class="text-2xl font-bold">Initialisation de l'évaluation</h2>
  <p class="text-muted-color mb-4">Renseignez les informations de l'évaluation ci-dessous.</p>

  <ListSkeleton v-if="isLoading" />
  <div>
    <div class="card">
      <div class="text-lg font-bold text-center">
        {{ evaluation.enseignement?.codeEnseignement }} - {{ evaluation.enseignement?.libelle }}
      </div>
    </div>

    <form @submit.prevent="updateEvaluation()" class="flex flex-col">
      <ValidatedInput
          v-model="evaluation.libelle"
          name="libelle"
          label="Libellé"
          :rules="validationRules.required"
          @validation="result => handleValidation('libelle', result)"
          help-text="Entrez le libellé de l'évaluation"
      />

      <ValidatedInput
          class="w-full"
          v-model="evaluation.dateEvaluation"
          name="dateEvaluation"
          label="Date de l'évaluation"
          type="date"
          :rules="validationRules.required"
          @validation="result => handleValidation('dateEvaluation', result)"
          help-text="Sélectionnez la date de l'évaluation"
      />

      <ValidatedInput
          class="w-full"
          v-model="evaluation.coefficient"
          name="coefficient"
          label="Coefficient"
          type="number"
          :rules="['required', validationRules.positiveNumber]"
          @validation="result => handleValidation('coefficient', result)"
          help-text="Entrez le coefficient de l'évaluation"
      />

      <ValidatedInput
          class="w-full"
          v-model="evaluation.commentaire"
          name="commentaire"
          label="Commentaire"
          type="textarea"
          :rules="[]"
          @validation="result => handleValidation('commentaire', result)"
          help-text="Ajoutez un commentaire (optionnel)"
      />

      <div class="flex">
        <ValidatedInput
            class="w-full"
            v-for="typeGroupe in evaluation.typeGroupeChoices"
            v-model="typesGroupe"
            name="typesGroupe"
            :label="`${typeGroupe}`"
            :value="typeGroupe"
            :rules="['required']"
            type="radio"
            @validation="result => handleValidation(`repartition-${typeGroupe}`, result)"
        />
      </div>

      <ValidatedInput
          class="w-full"
          v-model="evaluation.responsableId"
          name="responsableId"
          label="Responsable de l'évaluation"
          type="multiselect"
          :options="personnels.map(personnel => ({ label: `${personnel.nom} ${personnel.prenom}`, value: personnel.id }))"
          :rules="validationRules.required"
          @validation="result => handleValidation('responsableId', result)"
          help-text="Sélectionnez les enseignants autorisés à gérer cette évaluation"
          :filter="true"
      />

      <div class="flex justify-center items-center gap-4">
        <Button label="Mettre à jour l'évaluation" @click="updateEvaluation" :disabled="!formValid" />
        <Button label="Annuler" severity="secondary" @click="updateEvaluation" :disabled="!formValid" />
      </div>
    </form>
  </div>
</template>

<style scoped>
:deep(.p-component) {
  @apply w-full;
}
</style>
