<template>
  <Dialog header="Aperçu du questionnaire"
          :style="{ width: '80vw' }"
          :visible="true"
          @update:visible="$emit('close')"
          :modal="true" :closable="true">
    <div
        class="bg-white dark:bg-gray-800 rounded-xl w-full mx-4 max-h-[90vh] overflow-hidden flex flex-col"
        @click.stop
    >

      <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
        Voici comment votre questionnaire apparaîtra aux participants
      </p>
      <!-- Preview Content -->
      <div class="flex-1 overflow-y-auto p-6">
        <div class="mx-auto">
          <div v-if="survey">
            <!-- Survey Header -->
            <div class="text-center mb-8">
              <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                {{ survey.title }}
              </h1>
              <p v-if="survey.description" class="text-gray-600 dark:text-gray-400">
                {{ survey.description }}
              </p>
            </div>

            <!-- Progress Bar -->
            <div v-if="survey.opt.showProgress" class="mb-8">
              <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
                <span>Progression</span>
                <span>{{ Math.round((currentSectionIndex + 1) / survey.sections.length * 100) }}%</span>
              </div>
              <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div
                    class="bg-primary-600 h-2 rounded-full transition-all duration-300"
                    :style="{ width: `${(currentSectionIndex + 1) / survey.sections.length * 100}%` }"
                />
              </div>
            </div>

            <!-- Current Section -->
            <ListSkeleton v-if="isLoadingSection" />
            <template v-else>
              <div v-if="currentSection" class="space-y-6">
                <div>
                  <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                    {{ currentSection.title }}
                  </h2>
                  <div v-if="currentSection.typeSection === 'configurable'" class="mb-2">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                      <Cog6ToothIcon class="w-3 h-3 mr-1" />
                      Section configurable
                    </span>
                  </div>
                  <p v-if="currentSection.description" class="text-gray-600 dark:text-gray-400 mb-6">
                    {{ currentSection.description }}
                  </p>
                </div>

                <!-- Questions -->
                <div class="space-y-6">
                  <div
                      v-for="(question, questionIndex) in currentSection.questions"
                      :key="question.id"
                      class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4"
                  >

                    <div class="mb-3">
                      <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ questionIndex + 1 }}. {{ question.label }}
                        <span v-if="question.required" class="text-red-500">*</span>
                      </h3>
                      <p v-if="question.help" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ question.help }}
                      </p>
                    </div>

                    <!-- Question Preview -->
                    <div class="mt-3">
                      <!-- Single Choice -->
                      <div v-if="question.typeQuestion === 'single_choice'" class="space-y-2">
                        <div
                            v-for="option in question.choices"
                            :key="option.id"
                            class="flex items-center space-x-2"
                        >
                          <input type="radio" :name="`question_${question.id}`" class="text-primary-600" disabled
                          :value="option.value"
                          />
                          <label class="text-gray-700 dark:text-gray-300">{{ option.text }}</label>
                        </div>
                      </div>

                      <!-- Multiple Choice -->
                      <div v-else-if="question.typeQuestion === 'multiple_choice'" class="space-y-2">
                        <div
                            v-for="option in question.choices"
                            :key="option.id"
                            class="flex items-center space-x-2"
                        >
                          <input type="checkbox" class="text-primary-600 rounded"
                                 :value="option.value"
                                 disabled />
                          <label class="text-gray-700 dark:text-gray-300">{{ option.text }}</label>
                        </div>
                      </div>

                      <!-- Text Short -->
                      <div v-else-if="question.typeQuestion === 'text_short'">
                        <input
                            type="text"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800"
                            placeholder="Votre réponse..."
                            disabled
                        />
                      </div>

                      <!-- Text Long -->
                      <div v-else-if="question.typeQuestion === 'text_long'">
                        <textarea
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 resize-none"
                            rows="4"
                            placeholder="Votre réponse..."
                            disabled
                        />
                      </div>

                      <!-- Scale -->
                      <div v-else-if="question.typeQuestion === 'scale'">
                        <div class="flex items-center justify-between">
                          <span class="text-sm text-gray-600 dark:text-gray-400">
                            {{ question.validation?.min || 1 }}
                          </span>
                          <div class="flex space-x-2">
                            <button
                                v-for="n in ((question.validation?.max || 10) - (question.validation?.min || 1) + 1)"
                                :key="n"
                                class="w-8 h-8 rounded-full border-2 border-gray-300 dark:border-gray-600 flex items-center justify-center text-sm hover:border-primary-500 disabled:opacity-50"
                                disabled
                            >
                              {{ (question.validation?.min || 1) + n - 1 }}
                            </button>
                          </div>
                          <span class="text-sm text-gray-600 dark:text-gray-400">
                            {{ question.validation?.max || 10 }}
                          </span>
                        </div>
                      </div>

                      <!-- Matrix -->
                      <div v-else-if="question.typeQuestion === 'matrix'">
                        <div class="overflow-x-auto">
                          <table class="w-full border border-gray-300 dark:border-gray-600 rounded-lg">
                            <thead class="bg-gray-100 dark:bg-gray-700">
                            <tr>
                              <th class="p-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300"></th>
                              <th
                                  v-for="col in ['Pas du tout', 'Peu', 'Moyennement', 'Beaucoup', 'Énormément']"
                                  :key="col"
                                  class="p-3 text-center text-sm font-medium text-gray-700 dark:text-gray-300"
                              >
                                {{ col }}
                              </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr
                                v-for="row in ['Critère 1', 'Critère 2']"
                                :key="row"
                                class="border-t border-gray-300 dark:border-gray-600"
                            >
                              <td class="p-3 text-sm text-gray-700 dark:text-gray-300 font-medium">{{ row }}</td>
                              <td v-for="n in 5" :key="n" class="p-3 text-center">
                                <input type="radio" :name="`matrix_${row}`" disabled />
                              </td>
                            </tr>
                            </tbody>
                          </table>
                        </div>
                      </div>

                      <!-- Ranking -->
                      <div v-else-if="question.typeQuestion === 'ranking'">
                        <div class="space-y-2">
                          <div
                              v-for="(option, index) in question.opt"
                              :key="option.id"
                              class="flex items-center space-x-3 p-3 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg"
                          >
                            <div class="flex items-center justify-center w-6 h-6 bg-gray-200 dark:bg-gray-600 rounded text-sm font-medium">
                              {{ index + 1 }}
                            </div>
                            <span class="text-gray-700 dark:text-gray-300">{{ option.text }}</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Navigation -->
              <div class="flex items-center justify-between mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                <Button
                    severity="secondary"
                    v-if="survey.opt.allowBack && currentSectionIndex > 0"
                    @click="prevSection()"
                >
                  <ChevronLeftIcon class="w-4 h-4" />
                  Précédent
                </Button>
                <div v-else></div>

                <div class="flex items-center space-x-2">
                  <Button
                      v-if="currentSectionIndex < survey.sections.length - 1"
                      @click="nextSection()"
                  >
                    Suivant
                    <ChevronRightIcon class="w-4 h-4" />
                  </Button>
                  <Button
                      v-else
                  >
                    Terminer
                    <CheckIcon class="w-4 h-4" />
                  </Button>
                </div>
              </div>
            </template>
          </div>
        </div>
      </div>
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import {ref, onMounted} from 'vue';
import {
  ChevronLeftIcon,
  ChevronRightIcon,
  CheckIcon,
  Cog6ToothIcon
} from '@heroicons/vue/24/outline';
import type {Section, Survey} from '@types';
import {getPreviewQuestionnaire, getPreviewQuestionnaireSection} from '@requests'
import {ListSkeleton } from "@components";

interface Props {
  uuid: string;
}

const survey = ref<Survey>();
const currentSection = ref<Section>();
const isLoadingSection = ref<boolean>(true)

interface Emits {
  close: [];
}
const currentSectionIndex = ref(0);

onMounted(async () => {
  //on charge le mode preview pour calculer les sections configurables
  survey.value = await getPreviewQuestionnaire(props.uuid)
  currentSection.value = await getPreviewQuestionnaireSection(props.uuid, survey.value.sections[currentSectionIndex.value].key)
  isLoadingSection.value = false
});

const props = defineProps<Props>();
const emit = defineEmits<Emits>();



const nextSection = (async() => {
  if (currentSectionIndex.value < survey.value.sections.length - 1) {
    isLoadingSection.value = true
    currentSectionIndex.value ++
    currentSection.value = await getPreviewQuestionnaireSection(props.uuid, survey.value.sections[currentSectionIndex.value].key)
    isLoadingSection.value = false
  }
})

const prevSection = (async() => {
  if (currentSectionIndex.value > 0) {
    isLoadingSection.value = true
    currentSectionIndex.value --
    currentSection.value = await getPreviewQuestionnaireSection(props.uuid, survey.value.sections[currentSectionIndex.value].key)
    isLoadingSection.value = false
  }
})

// const currentSection = computed(() => {
//   if (!survey.value || !survey.value.sections.length) return null;
//   return survey.value.sections[currentSectionIndex.value];
// });
</script>
