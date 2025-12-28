<template>
  <div class="flex  dark:bg-gray-900">
    <!-- Left Panel - Survey Structure -->
    <div class="w-80 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col me-3">
        <!-- Survey Info -->
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center">
          <div class="flex-1">
            <input
                v-model="surveyTitle"
                @blur="updateSurveyTitle"
                class="text-lg font-semibold w-full bg-transparent border-none focus:outline-none focus:ring-2 focus:ring-primary-500 rounded px-2 py-1"
                placeholder="Titre du questionnaire"
            />
            <textarea
                v-model="surveyDescription"
                @blur="updateSurveyDescription"
                class="text-sm text-gray-600 dark:text-gray-400 w-full bg-transparent border-none focus:outline-none focus:ring-2 focus:ring-primary-500 rounded px-2 py-1 mt-2 resize-none"
                placeholder="Description (optionnelle)"
                rows="2"
            />
          </div>
<!--          <Button-->
<!--              severity="primary"-->
<!--              class="ml-2"-->
<!--              @click="showSettings = true"-->
<!--          >-->
<!--            <Cog6ToothIcon class="w-4 h-4" />-->
<!--          </Button>-->
        </div>



        <!-- Sections List -->
      <div class="flex-1 overflow-y-auto">
        <div class="p-4">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Sections</h3>
            <Button @click="showSectionModal = true"
                    severity="primary"
                    class="text-xs">
              <PlusIcon class="w-3 h-3" />
            </Button>
          </div>

          <draggable
              v-model="sections"
              @end="onSectionReorder"
              handle=".section-handle"
              class="space-y-2"
          >
            <div
                v-for="(section, index) in sections"
                :key="section.uuid"
                :class="[
                'border rounded-lg p-3 cursor-pointer transition-colors',
                currentSection?.uuid === section.uuid
                  ? 'border-primary-300 bg-primary-50 dark:border-primary-600 dark:bg-primary-900'
                  : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
              ]"
                @click="selectSection(section)"
            >
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2 flex-1">
                  <div class="section-handle drag-handle">
                    <Bars3Icon class="w-4 h-4" />
                  </div>
                  <div class="flex-1">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                      {{ section.title }}
                    </h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                      {{ section.questions.length }} question{{ section.questions.length > 1 ? 's' : '' }}
                    </p>
                  </div>
                </div>

                <ActionDropdown
                    :iconOnly="true"
                    :actions="actionsSection"
                    :contextValue="section"
                    button-label=""
                    button-icon="pi pi-cog"
                    button-severity="secondary"
                />
              </div>

              <!-- Section Type Indicator -->
              <div v-if="section.typeSection === 'configurable'" class="mt-2 flex items-center space-x-1">
                <Cog6ToothIcon class="w-3 h-3 text-blue-500" />
                <span class="text-xs text-blue-600 dark:text-blue-400">
                  {{ section.configurable?.elements.length || 0 }} {{ section.configurable?.sourceLabel.toLowerCase() }}
                </span>
              </div>
            </div>
          </draggable>
        </div>
      </div>

      <!-- Survey Settings -->
      <div class="p-4 border-t border-gray-200 dark:border-gray-700">
        <Button
            severity="secondary"
            @click="showSettings = true"
            class="w-full text-sm"
        >
          <Cog6ToothIcon class="w-4 h-4" />
          Paramètres du questionnaire
        </Button>
        <Button
            v-if="currentSurvey && currentSurvey.status === 'draft'"
            @click="publishSurvey"
            severity="primary"
            class="w-full mt-2 text-sm"
        >
          <RocketLaunchIcon class="w-4 h-4" />
          <span class="hidden sm:inline">Publier</span>
        </Button>
      </div>
    </div>

    <!-- Main Content - Question Builder -->
    <div class="flex-1 flex flex-col">
      <!-- Toolbar -->
      <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
              {{ currentSection?.title || 'Sélectionnez une section' }}
            </h2>
            <span class="text-sm text-gray-500 dark:text-gray-400">
              {{ currentSection?.questions.length || 0 }} question{{ (currentSection?.questions.length || 0) > 1 ? 's' : '' }}
            </span>
          </div>
          <div class="flex items-center space-x-2">
            <Button @click="showPreview = true"
                    severity="info">
              <EyeIcon class="w-4 h-4" />
              Aperçu
            </Button>
            <ActionDropdown
                :actions="questionTypes"
                button-label="Ajouter une question"
                button-icon="pi pi-plus"
                button-severity="primary"
            />
          </div>
        </div>
      </div>

      <!-- Questions Area -->
      <div class="flex-1 overflow-y-auto mt-3">
        <div v-if="!currentSection" class="text-center py-12">
          <DocumentTextIcon class="w-16 h-16 text-gray-400 mx-auto mb-4" />
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
            Sélectionnez une section
          </h3>
          <p class="text-gray-600 dark:text-gray-400">
            Choisissez une section dans le panneau de gauche pour commencer à ajouter des questions.
          </p>
        </div>

        <div v-else-if="currentSection.questions.length === 0" class="text-center py-12">
          <QuestionMarkCircleIcon class="w-16 h-16 text-gray-400 mx-auto mb-4" />
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
            Aucune question
          </h3>
          <p class="text-gray-600 dark:text-gray-400 mb-4">
            Commencez par ajouter votre première question à cette section.
          </p>
          <ActionDropdown
              :actions="questionTypes"
              button-label="Ajouter une question"
              button-icon="pi pi-plus"
              button-severity="primary"
              size="small"
          />
        </div>

        <div v-else>
          <draggable
              v-model="currentSectionQuestions"
              @end="onQuestionReorder"
              handle=".question-handle"
              class="space-y-4"
          >
            <QuestionEditor
                v-for="(question, index) in currentSectionQuestions"
                :key="question.uuid"
                :question="question"
                :section-id="currentSection.uuid"
                :index="index"
                :all-questions="allQuestions"
                :all-sections="sections"
                @update="updateQuestion"
                @delete="deleteQuestion"
                @duplicate="duplicateQuestion"
            />
          </draggable>
        </div>
      </div>
    </div>
  </div>

  <!-- Survey Settings Modal
  todo: a déclencher sur publication ??
  -->
<!--  <SurveyPublishSettingsModal-->
<!--      v-if="showSettings"-->
<!--      :survey="currentSurvey"-->
<!--      @close="showSettings = false"-->
<!--      @update="updateSurveyPublishSettings"-->
<!--  />-->

  <SurveySettingsModal
      v-if="showSettings"
      :survey="currentSurvey"
      @close="showSettings = false"
      @update="updateSurveySettings"
  />

  <!-- Preview Modal -->
  <SurveyPreviewModal
      v-if="showPreview"
      :uuid="currentSurvey.uuid"
      @close="showPreview = false"
  />

  <!-- Section Configuration Modal -->
  <SectionConfigModal
      v-if="showSectionModal"
      :section="editingSection"
      @close="closeSectionModal"
      @save="saveSection"
  />
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import {
  PlusIcon,
  Bars3Icon,
  Cog6ToothIcon,
  EyeIcon,
  DocumentTextIcon,
  QuestionMarkCircleIcon,
  RocketLaunchIcon
} from '@heroicons/vue/24/outline';
import ActionDropdown from '@components/components/ActionDropdown.vue';
import { VueDraggableNext as draggable } from 'vue-draggable-next';

import { useSurveyStore } from '@/stores/survey';
import { useUIStore } from '@/stores/ui';
import type { Section, Question, QuestionType } from '@types';

// Components (these would be separate files)
import QuestionEditor from '@/components/Questionnaire/QuestionEditor.vue';
import SurveySettingsModal from '@/components/Questionnaire/SurveySettingsModal.vue';
import SurveyPreviewModal from '@/components/Questionnaire/SurveyPreviewModal.vue';
import SectionConfigModal from '@/components/Questionnaire/SectionConfigModal.vue';

const route = useRoute();
const surveyStore = useSurveyStore();
const uiStore = useUIStore();

const showSettings = ref(false);
const showPreview = ref(false);
const showSectionModal = ref(false);
const editingSection = ref<Section | null>(null);

const questionTypes = [
  { value: 'single_choice', label: 'Choix unique', icon: 'pi pi-comment', command: () => addQuestion('single_choice') },
  { value: 'multiple_choice', label: 'Choix multiples', icon: 'pi pi-list', command: () => addQuestion('multiple_choice') },
  { value: 'text_short', label: 'Texte court', icon: 'pi pi-pen-to-square', command: () => addQuestion('text_short') },
  { value: 'text_long', label: 'Texte long', icon: 'pi pi-pencil', command: () => addQuestion('text_long') },
  { value: 'scale', label: 'Échelle', icon: 'pi pi-sliders-h', command: () => addQuestion('scale') },
  { value: 'matrix', label: 'Grille', icon: 'pi pi-table', command: () => addQuestion('matrix') },
  { value: 'ranking', label: 'Classement', icon: 'pi pi-sort-alt-slash', command: () => addQuestion('ranking') },
];

const actionsSection = [
  { label: 'Modifier', icon: 'pi pi-pencil', command: (section: Section) => editSection(section) },
  { label: 'Dupliquer', icon: 'pi pi-clone', command: (section: Section) => duplicateSection(section) },
  { label: 'Supprimer', icon: 'pi pi-trash', command: (section: Section) => deleteSection(section), severity: 'danger' }
];

const currentSurvey = computed(() => surveyStore.currentSurvey);
const currentSection = computed(() => surveyStore.currentSection);
const sections = computed(() => surveyStore.currentSections);

const surveyTitle = ref('');
const surveyDescription = ref('');

const currentSectionQuestions = computed({
  get: () => currentSection.value?.questions || [],
  set: (value) => {
    if (currentSection.value) {
      currentSection.value.questions = value;
      surveyStore.updateSurvey({});
    }
  }
});

const allQuestions = computed(() => {
  if (!currentSurvey.value) return [];
  return sections.value.flatMap(section => section.questions);
});

// Watch for survey changes
watch(currentSurvey, (survey) => {
  if (survey) {
    surveyTitle.value = survey.title;
    surveyDescription.value = survey.description || '';
  }
}, { immediate: true });

// Survey management
function updateSurveyTitle() {
  if (currentSurvey.value && surveyTitle.value.trim()) {
    surveyStore.updateSurvey({ title: surveyTitle.value.trim() });
  }
}

function updateSurveyDescription() {
  if (currentSurvey.value) {
    surveyStore.updateSurvey({ description: surveyDescription.value.trim() });
  }
}

function updateSurveySettings(opt: any) {
  surveyStore.updateSurvey({ opt });
  showSettings.value = false;
}

// Section management
async function saveSection(section: Section) {
  if (editingSection.value) {
    // Update existing section
    await surveyStore.updateSection(section.uuid, section);
  } else {
    // Add new section
    if (section.typeSection === 'configurable' && section.opt) {
      // Create multiple sections for configurable type
      for (const element of section.opt.elements) {
        const index = section.opt.elements.indexOf(element);
        const sectionTitle = section.opt!.titleTemplate.replace('{element}', element.name);
        const newSection = await surveyStore.addSection(sectionTitle, section.description);

        // Store reference to original configurable section and element
        await surveyStore.updateSection(newSection.uuid, {
          typeSection: 'configurable',
          opt: {
            ...section.opt!,
            elements: [element] // Each generated section has only one element
          }
        });
      }
    } else {
      // Normal section
      const newSection = await surveyStore.addSection(section.title, section.description);
      await surveyStore.updateSection(newSection.uuid, { typeSection: 'normal' });
    }
  }

  closeSectionModal();
  uiStore.addNotification('success', 'Section sauvegardée', 'La section a été sauvegardée avec succès.');
}

function closeSectionModal() {
  showSectionModal.value = false;
  editingSection.value = null;
}

function selectSection(section: Section) {
  surveyStore.selectSection(section.uuid);
}

function editSection(section: Section) {
  editingSection.value = section;
  showSectionModal.value = true;
}

async function duplicateSection(section: Section) {
  // Implementation for duplicating section
  const newSection = await surveyStore.addSection(`${section.title} (copie)`);
  // Copy questions
  for (const question of section.questions) {
    const newQuestion = { ...question, uuid: undefined }; // Reset UUID for new question
    await surveyStore.addQuestion(newSection.uuid, question.typeQuestion, newQuestion);
  }
  uiStore.addNotification('success', 'Section dupliquée', 'La section a été dupliquée avec succès.');
}

function deleteSection(section: Section) {
  if (confirm('Êtes-vous sûr de vouloir supprimer cette section ?')) {
    surveyStore.deleteSection(section.uuid);
    uiStore.addNotification('success', 'Section supprimée', 'La section a été supprimée avec succès.');
  }
}

function onSectionReorder(event: any) {
  surveyStore.reorderSections(event.oldIndex, event.newIndex);
}

// Question management
function addQuestion(type: QuestionType) {
  if (!currentSection.value) return;
  surveyStore.addQuestion(currentSection.value.uuid, type);
}

function updateQuestion(questionId: string, updates: Partial<Question>) {
  if (!currentSection.value) return;
  surveyStore.updateQuestion(currentSection.value.uuid, questionId, updates);
}

function deleteQuestion(question: Question) {
  if (!currentSection.value) return;
  if (confirm('Êtes-vous sûr de vouloir supprimer cette question ?')) {
    surveyStore.removeQuestion(currentSection.value.uuid, question.uuid);
  }
}

function duplicateQuestion(question: Question) {
  if (!currentSection.value) return;
  if (confirm(`Êtes-vous sûr de vouloir dupliquer cette question : ${question.label} ?`)) {
    surveyStore.duplicateQuestion(currentSection.value.uuid, question);
  }
}

function onQuestionReorder(event: any) {
  if (!currentSection.value) return;
  surveyStore.reorderQuestions(currentSection.value.uuid, event.oldIndex, event.newIndex);
}

function updateSurveyPublishSettings() {

}

function publishSurvey() {
  //todo: déclencher la route pour générer les sections + envoyer les token. Modal de confirmation?
}

// Initialize
onMounted(() => {
  const surveyId = route.params.id as string;

  if (surveyId && surveyId !== 'new') {
    surveyStore.selectSurvey(surveyId);
  } else if (!currentSurvey.value) {
    // Create new survey
    console.log('Creating new survey');
    surveyStore.createSurvey('Nouveau questionnaire');
  }
});
</script>

<style scoped>
.section-menu,
.question-type-menu {
  @apply absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50;
}

.menu-item {
  @apply flex items-center space-x-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors;
}
</style>
