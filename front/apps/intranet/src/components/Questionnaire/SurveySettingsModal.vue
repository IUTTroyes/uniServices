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
                    v-model="localSurvey.openingDate"
                    type="datetime-local"
                    class="input-field"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Date de fin
                </label>
                <input
                    v-model="localSurvey.closingDate"
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

        <!-- Message d'introduction -->
        <div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Message d'introduction/présentation</h3>
          <ValidatedInput
              v-model="localSurvey.startText"
              name="startText"
              label="Message d'introduction/présentation"
              type="textarea"
              :rules="[]"
              placeholder="Ce questionnaire ..."
          />
        </div>

        <!-- Thank You Message -->
        <div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Message de remerciement</h3>
          <ValidatedInput
              v-model="localSurvey.endText"
              name="endText"
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
import {onMounted, ref, watch } from 'vue';
import type {Survey, SurveySettings} from '@types';

interface Props {
  survey: Survey | null;
}

interface Emits {
  close: [];
  update: [settings: SurveySettings];
  updateSurvey: [survey: Survey | null];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const localSurvey = ref<Survey>();
const localSettings = ref<SurveySettings>({
  anonymous: false,
  autoSave: true,
  allowBack: true,
  showProgress: true,
  requireCompletion: false
});

watch(
    () => props.survey,
    (s) => {
      if (s) {
        // clone profond simple (compatible navigateur / Node)
        try {
          // prefer structuredClone si dispo
          // @ts-ignore
          localSurvey.value = typeof structuredClone === 'function' ? structuredClone(s) : JSON.parse(JSON.stringify(s));
        } catch {
          localSurvey.value = JSON.parse(JSON.stringify(s));
        }

        // remplir localSettings à partir des opt ou des champs racine si présents
        localSettings.value = {
          ...(s.opt ?? {})
        };
      } else {
        localSurvey.value = null;
        localSettings.value = {
          anonymous: false,
          autoSave: true,
          allowBack: true,
          showProgress: true,
          requireCompletion: false
        };
      }
    },
    { immediate: true }
);

function saveSettings() {
  emit('update', localSettings.value);
}
</script>
