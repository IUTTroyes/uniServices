<script setup>
import { onMounted, ref, toRaw } from 'vue';
import { ValidatedInput, validationRules, ListSkeleton } from '@components';
import { useUsersStore } from '@stores/user_stores/userStore.js';
import { getPersonnelsService, updateEvaluationService } from '@requests';
import {ErrorView} from "@components";
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import {useToast} from "primevue/usetoast";

const toast = useToast();
const emit = defineEmits(['saved', 'close']);
const isLoadingEvaluations = ref(true);
const hasError = ref(false);
const formValid = ref(true);
const formErrors = ref({});
const userStore = useUsersStore();
const departementId = ref(null);
const rows = ref([]);

const props = defineProps({
  enseignements: { type: Array, required: true },
  semestreId: { type: Number, required: false }
});

const parseApiDate = (dateStr) => {
  if (!dateStr) return null;
  if (dateStr instanceof Date) return dateStr;
  const parts = typeof dateStr === 'string' ? dateStr.split('-') : [];
  if (parts.length !== 3) return new Date(dateStr);
  const year = parseInt(parts[0], 10);
  const month = parseInt(parts[1], 10) - 1;
  const day = parseInt(parts[2], 10);
  return new Date(year, month, day);
};

onMounted(async () => {
  try {
    for (let evaluation of props.enseignements.flatMap(e => e.evaluations || [])) {
      if (evaluation.date) evaluation.date = parseApiDate(evaluation.date);
    }
    departementId.value = userStore.departementDefaut.id ?? null;
    for (let enseignement of props.enseignements) {
      await getPersonnelEnseignement(enseignement);
    }

    rows.value = props.enseignements.map(enseignement => ({
      id: enseignement.id,
      codeEnseignement: enseignement.codeEnseignement,
      libelle: enseignement.libelle,
      personnels: enseignement.personnels || [],
      evaluations: (enseignement.evaluations || []).map(evaluation => ({
        ...evaluation,
        enseignementId: enseignement.id,
        personnelAutorise: normalizePersonnelAutorise(evaluation.personnelAutorise)
      }))
    }));
  } finally {
    isLoadingEvaluations.value = false;
  }
});

// helper : normalise une valeur de personnelAutorise en tableau d'URI
const normalizePersonnelAutorise = (val) => {
  if (!val) return [];
  // si déjà des strings (uris), on les laisse, sinon on construit les uris à partir de l'objet personne
  return Array.isArray(val)
      ? val.map(p => (typeof p === 'string' ? p : `/api/personnels/${p.id}`))
      : [typeof val === 'string' ? val : `/api/personnels/${val.id}`];
};

const arraysEqual = (a, b) => {
  const arrA = Array.isArray(a) ? [...a] : [];
  const arrB = Array.isArray(b) ? [...b] : [];
  if (arrA.length !== arrB.length) return false;
  // sort to make comparison order-independent
  arrA.sort();
  arrB.sort();
  return arrA.every((v, i) => v === arrB[i]);
};

const formatDateYMD = (val) => {
  if (!val) return null;
  if (val instanceof Date) {
    const y = val.getFullYear();
    const m = String(val.getMonth() + 1).padStart(2, '0');
    const d = String(val.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
  }
  if (typeof val === 'string') {
    // Accept full ISO and keep only date part
    if (val.length >= 10) return val.slice(0, 10);
    return val;
  }
  // fallback: try to build Date
  const dt = new Date(val);
  if (!isNaN(dt)) return formatDateYMD(dt);
  return null;
};

const normalizeEvalForCompare = (e) => {
  // e can be reactive (proxy) or plain
  const raw = toRaw(e) ?? e;
  const perso = normalizePersonnelAutorise(raw.personnelAutorise);
  return {
    libelle: raw.libelle ?? null,
    coeff: raw.coeff ?? null,
    typeGroupe: raw.typeGroupe ?? null,
    personnelAutorise: perso,
    date: formatDateYMD(raw.date),
    commentaire: raw.commentaire ?? null,
  };
};

const shallowEqualEval = (a, b) => {
  return (
      (a.libelle ?? null) === (b.libelle ?? null) &&
      (a.coeff ?? null) === (b.coeff ?? null) &&
      (a.typeGroupe ?? null) === (b.typeGroupe ?? null) &&
      arraysEqual(a.personnelAutorise, b.personnelAutorise) &&
      (a.date ?? null) === (b.date ?? null) &&
      (a.commentaire ?? null) === (b.commentaire ?? null)
  );
};

const getPersonnelEnseignement = async (enseignement) => {
  try {
    const params = { enseignement: enseignement.id };
    enseignement.personnels = await getPersonnelsService(params);
  } catch (e) {
    enseignement.personnels = [];
  }
};

const handleValidation = (evaluationId, field, result) => {
  if (!formErrors.value[evaluationId]) {
    formErrors.value[evaluationId] = {};
  }
  formErrors.value[evaluationId][field] = result.valid ? null : result.message;

  // Update overall form validity
  formValid.value = result.valid
      ? formValid.value
      : false;
};

const isEvaluationValid = (evaluationId) => {
  const fields = formErrors.value[evaluationId] || {};
  return Object.values(fields).every((e) => e === null);
};

const submitEval = async () => {
  isLoadingEvaluations.value = true;
  try {
    const originalEvaluations = props.enseignements.reduce((acc, enseignement) => {
      (enseignement.evaluations || []).forEach(originalEval => acc[originalEval.id] = originalEval);
      return acc;
    }, {});
    // Build a normalized snapshot of original evaluations for reliable comparison
    const originalEvaluationsNormalized = Object.fromEntries(
        Object.entries(originalEvaluations).map(([id, evalObj]) => [id, normalizeEvalForCompare(evalObj)])
    );

    for (const row of rows.value) {
      for (const evaluation of row.evaluations) {
        if (!isEvaluationValid(evaluation.id)) continue;

        // Build normalized payload (plain values) for reliable compare and API
        const normalizedPayload = normalizeEvalForCompare(evaluation);
        const originalEvaluationNorm = originalEvaluationsNormalized[evaluation.id];
        const hasChanges = !shallowEqualEval(normalizedPayload, originalEvaluationNorm);
        if (hasChanges) {

          await updateEvaluationService(evaluation.id, normalizedPayload);
        }
      }
    }
  } catch (e) {
    console.error('Erreur lors de la soumission des évaluations:', e);
    hasError.value = true;
  } finally {
    if (!hasError.value) {
      toast.add({
        severity: 'success',
        summary: 'Succès',
        detail: 'Les évaluations ont été mises à jour avec succès.',
        life: 3000,
      });
    }
    emit('saved');
  }
};
</script>

<template>
  <ErrorView v-if="hasError" />
  <div v-else>
    <ListSkeleton v-if="isLoadingEvaluations" />
    <div v-else class="pt-4">
      <Message severity="info" icon="pi pi-info-circle" class="mb-4">
        Les listes d'enseignant sont filtrées en fonction des données du prévisionnel pour chaque enseignement.
      </Message>

      <div v-for="enseignement in rows" :key="enseignement.id" class="p-mb-4 p-p-4 p-border-1 p-border-round p-shadow-2">
        <div class="p-2 font-bold bg-primary-100 dark:bg-primary-800 text-lg">{{ enseignement.codeEnseignement }} - {{ enseignement.libelle }}</div>

        <Message v-if="!enseignement.evaluations || enseignement.evaluations.length === 0" severity="error" icon="pi pi-exclamation-triangle" class="my-6">
          Aucune évaluation.
        </Message>

        <div v-else>
          <DataTable :value="enseignement.evaluations" dataKey="id" class="w-full" responsiveLayout="scroll">
            <Column header="Évaluation">
              <template #body="{ data: evaluation }">
                <ValidatedInput
                    class="w-full"
                    v-model="evaluation.libelle"
                    :name="`libelle_${evaluation.id}`"
                    label=""
                    type="text"
                    @validation="result => handleValidation(evaluation.id, 'libelle', result)"
                />
              </template>
            </Column>

            <Column header="Coefficient" style="width:120px">
              <template #body="{ data: evaluation }">
                <ValidatedInput
                    class="w-full"
                    v-model="evaluation.coeff"
                    :name="`coeff_${evaluation.id}`"
                    label=""
                    type="number"
                    @validation="result => handleValidation(evaluation.id, 'coeff', result)"
                    inputId="minmax" :min="0" :max="100"
                />
              </template>
            </Column>

            <Column header="Type de groupe" style="width:160px">
              <template #body="{ data: evaluation }">
                <div class="w-full">
                  <ValidatedInput
                      v-if="evaluation.typeGroupeChoices && evaluation.typeGroupeChoices.length > 0"
                      v-model="evaluation.typeGroupe"
                      :name="`typeGroupe_${evaluation.id}`"
                      label=""
                      type="select"
                      :options="(evaluation.typeGroupeChoices || []).map(c => ({ label: c, value: c }))"
                      @validation="result => handleValidation(evaluation.id, 'typeGroupe', result)"
                  />
                  <div v-else class="text-xs text-neutral-500">
                    Aucun type de groupe disponible.
                  </div>
                </div>
              </template>
            </Column>

            <Column header="Intervenants">
              <template #body="{ data: evaluation }">
                <ValidatedInput
                    class="w-full"
                    v-model="evaluation.personnelAutorise"
                    :name="`personnelAutorise_${evaluation.id}`"
                    label=""
                    type="multiselect"
                    :options="(enseignement.personnels || []).map(p => ({ label: p.display || `${p.nom} ${p.prenom}`, value: `/api/personnels/${p.id}` }))"
                    @validation="result => handleValidation(evaluation.id, 'personnelAutorise', result)"
                    :filter="true"
                />
              </template>
            </Column>

            <Column header="Date" style="width:140px">
              <template #body="{ data: evaluation }">
                <ValidatedInput
                    class="w-full"
                    v-model="evaluation.date"
                    :name="`date_${evaluation.id}`"
                    label=""
                    type="date"
                    @validation="result => handleValidation(evaluation.id, 'date', result)"
                />
              </template>
            </Column>

            <Column header="Commentaire">
              <template #body="{ data: evaluation }">
                <ValidatedInput
                    class="w-full"
                    v-model="evaluation.commentaire"
                    :name="`commentaire_${evaluation.id}`"
                    label=""
                    type="text"
                    @validation="result => handleValidation(evaluation.id, 'commentaire', result)"
                />
              </template>
            </Column>
          </DataTable>
        </div>
      </div>
      <div class="flex justify-center items-center gap-4 mt-4">
        <Button class="w-1/2" label="Initialiser les évaluations" @click="submitEval" :disabled="!formValid" />
        <Button class="w-1/2" label="Annuler" severity="secondary" @click="() => emit('close')" />
      </div>
    </div>
  </div>
</template>

<style scoped>
:deep(.p-component) {
  width: 100%;
}
</style>
