<script setup>
import {onMounted, ref} from 'vue';
import { getEvaluationService, getPersonnelsService, updateEvaluationService } from '@requests';
import { ValidatedInput, validationRules, ErrorView, PermissionGuard, ListSkeleton } from "@components";
import { useUsersStore } from "@stores/user_stores/userStore.js";

const emit = defineEmits(['saved', 'close']);

const formValid = ref(false);
const formErrors = ref({});
const evaluation = ref({})
const isLoading = ref(true);
const typesGroupe = ref()
const departementId = ref(null)
const personnels = ref([])
const userStore = useUsersStore();

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

// helper: parse "YYYY-MM-DD" to Date (avoids timezone shift)
const parseApiDate = (dateStr) => {
  if (!dateStr) return null;
  if (dateStr instanceof Date) return dateStr;
  const parts = dateStr.split('-');
  if (parts.length !== 3) return new Date(dateStr);
  const year = parseInt(parts[0], 10);
  const month = parseInt(parts[1], 10) - 1;
  const day = parseInt(parts[2], 10);
  return new Date(year, month, day);
};

// helper: format Date to "YYYY-MM-DD" for API
const pad = (n) => n.toString().padStart(2, '0');
const formatDateForApi = (date) => {
  if (!date) return null;
  if (!(date instanceof Date)) return date;
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
};

onMounted(async () => {
  departementId.value = userStore.departementDefaut.id ?? null;
  await getEvaluation();
  await getPersonnels();
  await getPersonnelEnseignement();
});

const getEvaluation = async () => {
  try {
    isLoading.value = true;
    const response = await getEvaluationService(props.evaluationId);
    // convert API date string to Date object for PrimeVue DatePicker
    if (response && response.date) {
      response.date = parseApiDate(response.date);
    }
    evaluation.value = response;
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
    isLoading.value = false;
  }
};

const getPersonnelEnseignement = async () => {
  try {
    isLoading.value = true;
    const params = {
      enseignement: evaluation.value.enseignement.id
    };
    evaluation.value.enseignement.personnels = await getPersonnelsService(params);
  } catch (error) {
    console.error('Erreur lors du chargement des personnels:', error);
  } finally {
    isLoading.value = false;
  }
}

const handleValidation = (field, result) => {
  formErrors.value = {
    ...formErrors.value,
    [field]: result.isValid ? null : result.errorMessage
  };
  formValid.value = Object.values(formErrors.value).every(error => error === null);
};

const updateEvaluation = async () => {
  try {
    if (!formValid.value) {
      toast.add({severity: 'error', summary: 'Erreur de validation', detail: 'Veuillez corriger les erreurs de validation', life: 5000});
      return;
    }
    // préparer payload : transformer relations et la date
    const payload = { ...evaluation.value };
    if (payload.enseignement && payload.enseignement.id) {
      payload.enseignement = `/api/scol_enseignements/${payload.enseignement.id}`;
      payload.semestre = `/api/structure_semestres/${props.semestreId}`;
    }
    payload.notes = Array.isArray(payload.notes)
      ? payload.notes.map(note => `/api/etudiant_notes/${note.id}`)
      : [];
    // transformer Date en "YYYY-MM-DD" attendu par l'API
    payload.date = formatDateForApi(payload.date);
    if (evaluation.value.etat === 'non_initialisee') {
      console.log('ok')
    }
    await updateEvaluationService(payload.id, payload, '', true);
    await getEvaluation();
  } catch (error) {
    console.error('Erreur lors de la mise à jour de l\'évaluation:', error);
  } finally {
    emit('saved');
  }
}

</script>

<template>

  <ListSkeleton v-if="isLoading" />
  <div v-else>
    <div class="card bg-neutral-50 rounded-md border border-neutral-300 dark:border-neutral-600 dark:bg-neutral-900">
      <div class="text-lg font-bold text-center">
        {{ evaluation.enseignement?.codeEnseignement }} - {{ evaluation.enseignement?.libelle }}
      </div>
      <div class="text-center">
        Enseignant(s) de la {{evaluation.enseignement?.type}} :
      </div>
      <div class="flex items-center justify-center gap-4">
        <div v-if="evaluation.enseignement?.personnels?.length > 0" v-for="personnel in evaluation.enseignement?.personnels" :key="personnel.id" class="text-center px-3 py-1 bg-primary-100 text-primary-800 rounded-full dark:bg-primary-900 dark:text-primary-300">
          {{ personnel.display }}
        </div>
        <div v-else class="text-center px-3 py-1 bg-primary-100 text-primary-800 rounded-full dark:bg-primary-900 dark:text-primary-300">
          Aucun enseignant
        </div>
      </div>
    </div>

    <form @submit.prevent="updateEvaluation()" class="flex flex-col">
      <ValidatedInput
          v-model="evaluation.libelle"
          name="libelle"
          label="Libellé"
          type="text"
          :rules="[validationRules.required]"
          @validation="result => handleValidation('libelle', result)"
          help-text="Entrez le libellé de l'évaluation"
      />

      <div>
        <div class="">Type d'évaluation</div>
        <div class="flex w-full justify-between px-8">
          <ValidatedInput
              v-for="typeChoice in evaluation.typeChoices"
              v-model="evaluation.type"
              name="type"
              :label="`${typeChoice}`"
              :value="typeChoice"
              :rules="[]"
              type="radio"
              @validation="result => handleValidation('type', result)"
          />
        </div>
      </div>

      <ValidatedInput
          class="w-full"
          v-model="evaluation.date"
          name="date"
          label="Date de l'évaluation"
          type="date"
          :rules="[]"
          @validation="result => handleValidation('dateEvaluation', result)"
          help-text="Sélectionnez la date de l'évaluation"
      />

      <ValidatedInput
          class="w-full"
          v-model="evaluation.coeff"
          name="coeff"
          label="Coefficient"
          type="number"
          :rules="[validationRules.required]"
          @validation="result => handleValidation('coeff', result)"
          help-text="Entrez le coefficient de l'évaluation"
          inputId="minmax" :min="0" :max="100"
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

      <ValidatedInput
          class="w-full"
          v-model="evaluation.typeGroupe"
          name="typeGroupe"
          label="Type de groupe"
          type="select"
          :options="(evaluation.typeGroupeChoices || []).map(c => ({ label: c, value: c }))"
          :rules="[validationRules.required]"
          @validation="result => handleValidation('typeGroupe', result)"
      />

      <ValidatedInput
          class="w-full"
          v-model="evaluation.personnelAutorise"
          name="personnelAutorise"
          label="Gestionnaires de l'évaluation"
          type="multiselect"
          :options="personnels.map(personnel => ({ label: personnel.display || `${personnel.nom} ${personnel.prenom}`, value: `/api/personnels/${personnel.id}` }))"
          :rules="[validationRules.required]"
          @validation="result => handleValidation('personnelAutorise', result)"
          help-text="Sélectionnez les enseignants autorisés à gérer cette évaluation"
          :filter="true"
      />

      <div class="flex justify-center items-center gap-4">
        <Button label="Mettre à jour l'évaluation" @click="updateEvaluation" :disabled="!formValid" />
        <Button label="Annuler" severity="secondary" @click="() => emit('close')" />
      </div>
    </form>
  </div>
</template>

<style scoped>
:deep(.p-component) {
  @apply w-full;
}
</style>
