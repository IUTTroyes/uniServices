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
          <Button
              severity="primary"
              class="ml-2"
              @click="showSettings = true"
          >
            <Cog6ToothIcon class="w-4 h-4" />
          </Button>
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
                :key="section.id"
                :class="[
                'border rounded-lg p-3 cursor-pointer transition-colors',
                currentSection?.id === section.id
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
                      {{ section.titre }}
                    </h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                      {{ section.questionnaireQuestions.length }} question{{ section.questionnaireQuestions.length > 1 ? 's' : '' }}
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
            @click="showPublishSettings = true"
            class="w-full text-sm"
        >
          <Cog6ToothIcon class="w-4 h-4" />
          Paramètres de publication
        </Button>
        <Button
            v-if="currentSurvey && currentSurvey.status === 'draft'"
            @click="publishSurvey = true"
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
              {{ currentSection?.titre || 'Sélectionnez une section' }}
            </h2>
            <span class="text-sm text-gray-500 dark:text-gray-400">
              {{ currentSection?.questionnaireQuestions.length || 0 }} question{{ (currentSection?.questionnaireQuestions.length || 0) > 1 ? 's' : '' }}
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

        <div v-else-if="currentSection.questionnaireQuestions.length === 0" class="text-center py-12">
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
                :key="question.id"
                :question="question"
                :section-id="currentSection.id"
                :index="index"
                :all-questions="allQuestions"
                :all-sections="sections"
                @update="updateQuestion"
                @delete="deleteQuestion"
            />
          </draggable>
        </div>
      </div>
    </div>
  </div>

  <!-- Survey Settings Modal -->
  <SurveyPublishSettingsModal
      v-if="showPublishSettings"
      :survey="currentSurvey"
      @close="showPublishSettings = false"
      @update="updateSurveyPublishSettings"
  />

  <SurveySettingsModal
      v-if="showSettings"
      :survey="currentSurvey"
      @close="showSettings = false"
      @update="updateSurveySettings"
  />

  <!-- Preview Modal -->
  <SurveyPreviewModal
      v-if="showPreview"
      :survey="currentSurvey"
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
  EllipsisVerticalIcon,
  PencilIcon,
  DocumentDuplicateIcon,
  TrashIcon,
  Cog6ToothIcon,
  EyeIcon,
  DocumentTextIcon,
  QuestionMarkCircleIcon,
  ChatBubbleLeftRightIcon,
  ListBulletIcon,
  PencilSquareIcon,
  ScaleIcon,
  TableCellsIcon,
  QueueListIcon, RocketLaunchIcon
} from '@heroicons/vue/24/outline';
import ActionDropdown from '@components/components/ActionDropdown.vue';
import { VueDraggableNext as draggable } from 'vue-draggable-next';

import { useSurveyStore } from '@/stores/survey';
import { useUIStore } from '@/stores/ui';
import type { Section, Question, QuestionType } from '@/types/survey';

// Components (these would be separate files)
import QuestionEditor from '@/components/Questionnaire/QuestionEditor.vue';
import SurveySettingsModal from '@/components/Questionnaire/SurveySettingsModal.vue';
import SurveyPreviewModal from '@/components/Questionnaire/SurveyPreviewModal.vue';
import SectionConfigModal from '@/components/Questionnaire/SectionConfigModal.vue';

const route = useRoute();
const surveyStore = useSurveyStore();
const uiStore = useUIStore();

const showSettings = ref(false);
const showPublishSettings = ref(false);
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
  get: () => currentSection.value?.questionnaireQuestions || [],
  set: (value) => {
    if (currentSection.value) {
      currentSection.value.questionnaireQuestions = value;
      surveyStore.updateSurvey({});
    }
  }
});

const allQuestions = computed(() => {
  if (!currentSurvey.value) return [];
  return sections.value.flatMap(section => section.questionnaireQuestions);
});

// Watch for survey changes
watch(currentSurvey, (survey) => {
  if (survey) {
    surveyTitle.value = survey.titre;
    surveyDescription.value = survey.description || '';
  }
}, { immediate: true });

// Survey management
function updateSurveyTitle() {
  if (currentSurvey.value && surveyTitle.value.trim()) {
    surveyStore.updateSurvey({ titre: surveyTitle.value.trim() });
  }
}

function updateSurveyDescription() {
  if (currentSurvey.value) {
    surveyStore.updateSurvey({ description: surveyDescription.value.trim() });
  }
}

function updateSurveySettings(settings: any) {
  surveyStore.updateSurvey({ settings });
  showSettings.value = false;
}

// Section management
function saveSection(section: Section) {
  if (editingSection.value) {
    // Update existing section
    surveyStore.updateSection(section.id, section);
  } else {
    // Add new section
    if (section.typeSection === 'configurable' && section.configurable) {
      // Create multiple sections for configurable type
      section.configurable.elements.forEach((element, index) => {
        const sectionTitle = section.configurable!.titleTemplate.replace('{element}', element.name);
        const newSection = surveyStore.addSection(sectionTitle, section.description);

        // Store reference to original configurable section and element
        surveyStore.updateSection(newSection.id, {
          typeSection: 'configurable',
          opt: {
            ...section.configurable!,
            elements: [element] // Each generated section has only one element
          }
        });
      });
    } else {
      // Normal section
      const newSection = surveyStore.addSection(section.titre, section.description);
      surveyStore.updateSection(newSection.id, { typeSection: 'normal' });
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
  surveyStore.selectSection(section.id);
}

function editSection(section: Section) {
  editingSection.value = section;
  showSectionModal.value = true;
}

function duplicateSection(section: Section) {
  // Implementation for duplicating section
  const newSection = surveyStore.addSection(`${section.title} (copie)`);
  // Copy questions
  section.questionnaireQuestions.forEach(question => {
    const newQuestion = { ...question, id: Date.now().toString() + Math.random() };
    surveyStore.addQuestion(newSection.id, question.type, question.title);
  });
  uiStore.addNotification('success', 'Section dupliquée', 'La section a été dupliquée avec succès.');
}

function deleteSection(section: Section) {
  if (confirm('Êtes-vous sûr de vouloir supprimer cette section ?')) {
    surveyStore.deleteSection(section.id);
    uiStore.addNotification('success', 'Section supprimée', 'La section a été supprimée avec succès.');
  }
}

function onSectionReorder(event: any) {
  surveyStore.reorderSections(event.oldIndex, event.newIndex);
}

// Question management
function addQuestion(type: QuestionType) {
  if (!currentSection.value) return;
  surveyStore.addQuestion(currentSection.value.id, type);
}

function updateQuestion(questionId: string, updates: Partial<Question>) {
  console.log('updateQuestion', questionId);
  if (!currentSection.value) return;
  surveyStore.updateQuestion(currentSection.value.id, questionId, updates);
}

function deleteQuestion(questionId: string) {
  if (!currentSection.value) return;
  if (confirm('Êtes-vous sûr de vouloir supprimer cette question ?')) {
    surveyStore.removeQuestion(currentSection.value.id, questionId);
  }
}

function onQuestionReorder(event: any) {
  if (!currentSection.value) return;
  surveyStore.reorderQuestions(currentSection.value.id, event.oldIndex, event.newIndex);
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
