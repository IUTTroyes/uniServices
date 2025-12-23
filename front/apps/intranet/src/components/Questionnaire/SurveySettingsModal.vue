<template>
  <Dialog header="Paramètres du questionnaire"
          :style="{ width: '80vw' }"
          :visible="true"
          @update:visible="$emit('close')"
          :modal="true" :closable="true">

    <div
        class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full mx-4 max-h-[80vh] overflow-y-auto"
        @click.stop
    >
      <form @submit.prevent="saveSettings" class="space-y-6">
        <!-- Publication Settings -->
        <div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Publication</h3>
          <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Date de début
                </label>
                <input
                    v-model="localSettings.startDate"
                    type="datetime-local"
                    class="input-field"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Date de fin
                </label>
                <input
                    v-model="localSettings.endDate"
                    type="datetime-local"
                    class="input-field"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Privacy Settings -->
        <div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Confidentialité</h3>
          <div class="space-y-4">
            <div class="flex items-center">
              <input
                  v-model="localSettings.anonymous"
                  type="checkbox"
                  class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
              />
              <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                Questionnaire anonyme
              </label>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              Les réponses seront collectées de manière anonyme, sans identifier les participants.
            </p>
          </div>
        </div>

        <!-- User Experience Settings -->
        <div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Expérience utilisateur</h3>
          <div class="space-y-4">
            <div class="flex items-center">
              <input
                  v-model="localSettings.autoSave"
                  type="checkbox"
                  class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
              />
              <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                Sauvegarde automatique
              </label>
            </div>

            <div class="flex items-center">
              <input
                  v-model="localSettings.allowBack"
                  type="checkbox"
                  class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
              />
              <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                Autoriser le retour en arrière
              </label>
            </div>

            <div class="flex items-center">
              <input
                  v-model="localSettings.showProgress"
                  type="checkbox"
                  class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
              />
              <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                Afficher la barre de progression
              </label>
            </div>

            <div class="flex items-center">
              <input
                  v-model="localSettings.requireCompletion"
                  type="checkbox"
                  class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
              />
              <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                Validation finale obligatoire
              </label>
            </div>
          </div>
        </div>

        <!-- Thank You Message -->
        <div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Message de remerciement</h3>
          <ValidatedInput
              v-model="localSettings.thankYouMessage"
              name="thankYouMessage"
              label="Message de remerciement"
              type="textarea"
              :rules="[]"
              placeholder="Merci d'avoir participé à cette enquête..."
          />
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end space-x-3 pt-6">
          <Button
              severity="secondary"
              type="button" @click="$emit('close')">
            Annuler
          </Button>
          <Button type="submit">
            Enregistrer
          </Button>
        </div>
      </form>
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import { ValidatedInput, validationRules } from '@components';
import {onMounted, ref} from 'vue';
import {XMarkIcon} from '@heroicons/vue/24/outline';
import type {Survey, SurveySettings} from '@/types/survey';

interface Props {
  survey: Survey | null;
}

interface Emits {
  close: [];
  update: [settings: SurveySettings];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const localSettings = ref<SurveySettings>({
  anonymous: false,
  autoSave: true,
  allowBack: true,
  showProgress: true,
  requireCompletion: false,
  startDate: undefined,
  endDate: undefined,
  thankYouMessage: 'Merci d\'avoir participé à cette enquête !'
});

function saveSettings() {
  emit('update', localSettings.value);
}

onMounted(() => {
  if (props.survey?.settings) {
    localSettings.value = {...props.survey.settings};

    // Convert dates for datetime-local inputs
    if (localSettings.value.startDate) {
      localSettings.value.startDate = new Date(localSettings.value.startDate).toISOString().slice(0, 16) as any;
    }
    if (localSettings.value.endDate) {
      localSettings.value.endDate = new Date(localSettings.value.endDate).toISOString().slice(0, 16) as any;
    }
  }
});
</script>
