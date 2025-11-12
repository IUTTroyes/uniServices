<script setup>
  import { onMounted, ref } from 'vue';
  import { ValidatedInput, validationRules, ListSkeleton } from '@components';
  import { useUsersStore } from '@stores/user_stores/userStore.js';
  import { getPersonnelsService, updateEvaluationService } from '@requests';
  import DataTable from 'primevue/datatable';
  import Column from 'primevue/column';

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

  const handleValidation = (evaluationId, field, result) => {

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
      enseignement.personnels = [];
    }
  };

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
          evaluation.date = parseApiDate(evaluation.date);
        }

        departementId.value = userStore.departementDefaut?.id ?? null;
        // Charger les personnels pour chaque enseignement
        const promises = (props.enseignements || []).map((ens) => getPersonnelForEnseignement(ens));
        await Promise.all(promises);
      } finally {
        isLoading.value = false;
      }
    });

  const saveEvaluation = async (evaluation, enseignement) => {

  };
  </script>

  <template>
    <ListSkeleton v-if="isLoading" />
    <div v-else class="pt-4">
      <Message severity="info" icon="pi pi-info-circle" class="mb-4">
        Les listes d'enseignant sont filtrées en fonction des données du prévisionnel pour chaque matière, SAE ou ressource.
      </Message>

      <div v-for="enseignement in enseignements" :key="enseignement.id" class="p-mb-4 p-p-4 p-border-1 p-border-round p-shadow-2">
        <div class="p-2 font-bold bg-neutral-100 dark:bg-neutral-800 text-lg">{{ enseignement.codeEnseignement }} - {{ enseignement.libelle }}</div>

        <div v-if="!enseignement.evaluations || enseignement.evaluations.length === 0" class="text-sm text-neutral-500">
          Aucune évaluation.
        </div>

        <div v-else>
          <DataTable :value="enseignement.evaluations" dataKey="id" class="w-full" responsiveLayout="scroll">
            <Column header="Évaluation">
              <template #body="{ data: evaluation }">
                <ValidatedInput
                  class="w-full"
                  v-model="evaluation.libelle"
                  :name="`libelle_${evaluation.id}`"
                  label="Évaluation"
                  type="text"
                  :rules="[]"
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
                  label="Coefficient"
                  type="number"
                  :rules="[]"
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
              </template>
            </Column>

            <Column header="Intervenants">
              <template #body="{ data: evaluation }">
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
              </template>
            </Column>

            <Column header="Date" style="width:140px">
              <template #body="{ data: evaluation }">
                <ValidatedInput
                  class="w-full"
                  v-model="evaluation.date"
                  :name="`date_${evaluation.id}`"
                  label="Date"
                  type="date"
                  :rules="[]"
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
                  label="Commentaire"
                  type="text"
                  :rules="[]"
                  @validation="result => handleValidation(evaluation.id, 'commentaire', result)"
                />
              </template>
            </Column>
          </DataTable>
        </div>
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
