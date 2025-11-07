```vue
      <script setup>
      import {onMounted, ref} from 'vue';
      import { getEvaluationService, getPersonnelsService, updateEvaluationService } from '@requests';
      import { ValidatedInput, validationRules, ErrorView, PermissionGuard, ListSkeleton } from "@components";
      import { useUsersStore } from "@stores/user_stores/userStore.js";

      const formValid = ref(true);
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
        departementId.value = userStore.departementDefaut.id;
        await getEvaluation();
        await getPersonnels();
      });

      const getEvaluation = async () => {
        try {
          isLoading.value = true;
          const resp = await getEvaluationService(props.evaluationId);
          // convert API date string to Date object for PrimeVue DatePicker
          if (resp && resp.date) {
            resp.date = parseApiDate(resp.date);
          }
          evaluation.value = resp;
        } catch (error) {
          console.error('Erreur lors du chargement de l\'évaluation:', error);
        } finally {
          console.log(evaluation.value);
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

      const updateEvaluation = async () => {
        try {
          if (!formValid.value) {
            console.log('Le formulaire contient des erreurs de validation.');
            return;
          }
          // préparer payload : transformer relations et la date
          const payload = { ...evaluation.value };
          if (payload.enseignement && payload.enseignement.id) {
            payload.enseignement = `/api/scol_enseignements/${payload.enseignement.id}`;
          }
          // transformer Date en "YYYY-MM-DD" attendu par l'API
          payload.date = formatDateForApi(payload.date);

          await updateEvaluationService(payload.id, payload, '', true);
          console.log('Évaluation mise à jour avec succès:', payload);
          await getEvaluation();
        } catch (error) {
          console.error('Erreur lors de la mise à jour de l\'évaluation:', error);
        }
      }

      </script>

      <template>

        <ListSkeleton v-if="isLoading" />
        <div>
          <div class="card bg-neutral-50 rounded-md border border-neutral-300 dark:border-neutral-600 dark:bg-neutral-900">
            <div class="text-lg font-bold text-center">
              {{ evaluation.enseignement?.codeEnseignement }} - {{ evaluation.enseignement?.libelle }}
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

            <ValidatedInput
                class="w-full"
                v-model="evaluation.date"
                name="date"
                label="Date de l'évaluation"
                type="date"
                :rules="[validationRules.required]"
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

            <div>
              <div class="">Type de groupe</div>
              <div class="flex w-full justify-between px-8">
                <ValidatedInput
                    v-for="typeGroupeChoice in evaluation.typeGroupeChoices"
                    v-model="evaluation.typeGroupe"
                    name="typeGroupe"
                    :label="`${typeGroupeChoice}`"
                    :value="typeGroupeChoice"
                    :rules="[]"
                    type="radio"
                    @validation="result => handleValidation('typeGroupe', result)"
                />
              </div>
            </div>

            <ValidatedInput
                class="w-full"
                v-model="evaluation.personnelAutorise"
                name="personnelAutorise"
                label="Responsable de l'évaluation"
                type="multiselect"
                :options="personnels.map(personnel => ({ label: `${personnel.nom} ${personnel.prenom}`, value: `/api/personnels/${personnel.id}` }))"
                :rules="[validationRules.required]"
                @validation="result => handleValidation('personnelAutorise', result)"
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
