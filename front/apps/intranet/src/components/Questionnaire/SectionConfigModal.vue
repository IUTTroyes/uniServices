<template>
  <Dialog header="Configuration de la section"
          :style="{ width: '80vw' }"
          :visible="true"
          @update:visible="$emit('close')"
          :modal="true" :closable="true">
    <div
        class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full mx-4 max-h-[90vh] overflow-y-auto"
        @click.stop
    >

      <form @submit.prevent="saveSection" class="space-y-6">
        <!-- Section Type -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
            Type de section
          </label>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div
                :class="[
                'p-4 border-2 rounded-lg cursor-pointer transition-all',
                localSection.typeSection === 'normal'
                  ? 'border-primary-500 bg-primary-50 dark:bg-primary-900'
                  : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
              ]"
                @click="localSection.typeSection = 'normal'"
            >
              <div class="flex items-start space-x-3">
                <DocumentTextIcon class="w-6 h-6 text-primary-600 dark:text-primary-400 mt-1" />
                <div>
                  <h3 class="font-medium text-gray-900 dark:text-white">Section normale</h3>
                  <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Section standard avec questions fixes
                  </p>
                </div>
              </div>
            </div>

            <div
                :class="[
                'p-4 border-2 rounded-lg cursor-pointer transition-all',
                localSection.typeSection === 'configurable'
                  ? 'border-primary-500 bg-primary-50 dark:bg-primary-900'
                  : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
              ]"
                @click="localSection.typeSection = 'configurable'"
            >
              <div class="flex items-start space-x-3">
                <Cog6ToothIcon class="w-6 h-6 text-primary-600 dark:text-primary-400 mt-1" />
                <div>
                  <h3 class="font-medium text-gray-900 dark:text-white">Section configurable</h3>
                  <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Section répétée pour chaque élément d'une liste
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Basic Section Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <ValidatedInput
                v-model="localSection.title"
                name="sectionTitle"
                label="Titre de la section"
                type="text"
                :rules="[validationRules.required]"
                placeholder="Titre de la section"
            />
          </div>

          <div v-if="localSection.typeSection === 'configurable'">
            <ValidatedInput
                v-model="localSection.opt!.titleTemplate"
                name="titleTemplate"
                label="Modèle de titre"
                type="text"
                :rules="[]"
                placeholder="Évaluation de {element}"
                helpText="Utilisez {element} pour insérer le nom de l'élément"
            />
          </div>
        </div>

        <div>
          <ValidatedInput
              v-model="localSection.description"
              name="description"
              label="Description (optionnelle)"
              type="textarea"
              :rules="[]"
              placeholder="Description de la section"
          />
        </div>

        <!-- Configurable Section Settings -->
        <div v-if="localSection.typeSection === 'configurable'" class="space-y-6">
          <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
              Configuration des éléments
            </h3>

            <!-- Source Type Selection -->
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                Type d'éléments
              </label>
              <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                <div
                    v-for="sourceType in sourceTypes"
                    :key="sourceType.value"
                    :class="[
                    'p-3 border rounded-lg cursor-pointer transition-all text-center',
                    localSection.opt?.sourceType === sourceType.value
                      ? 'border-primary-500 bg-primary-50 dark:bg-primary-900 text-primary-700 dark:text-primary-300'
                      : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
                  ]"
                    @click="selectSourceType(sourceType.value)"
                >
                  <component :is="sourceType.icon" class="w-6 h-6 mx-auto mb-2" />
                  <div class="text-sm font-medium">{{ sourceType.label }}</div>
                </div>
              </div>
            </div>

            <!-- Custom Source Label -->
            <div v-if="localSection.opt?.sourceType === 'custom'" class="mb-4">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Nom du type d'élément
              </label>
              <ValidatedInput
                  v-model="localSection.opt.sourceLabel"
                  name="sourceLabel"
                  label="Nom du type d'élément"
                  type="text"
                  :rules="[validationRules.required]"
                  placeholder="Ex: Projets, Équipes, Produits..."
              />
            </div>

            <!-- Elements Management -->
            <div v-if="localSection.opt?.sourceType">
              <div class="flex items-center justify-between mb-4">
                <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                  Liste des {{ localSection.opt.sourceLabel.toLowerCase() }}
                </h4>
                <div class="flex items-center space-x-2">
                  <Button
                      severity="secondary"
                      type="button"
                      @click="showImportModal = true"
                      class="text-sm"
                  >
                    <DocumentArrowUpIcon class="w-4 h-4" />
                    Importer CSV
                  </Button>
                  <Button
                      type="button"
                      @click="addElement"
                      class="text-sm"
                  >
                    <PlusIcon class="w-4 h-4" />
                    Ajouter
                  </Button>
                </div>
              </div>

              <!-- Elements List -->
              <div class="space-y-3 max-h-64 overflow-y-auto">
                <div
                    v-for="(element, index) in localSection.opt.elements"
                    :key="element.id"
                    class="flex items-center space-x-3 p-3 border border-gray-200 dark:border-gray-600 rounded-lg"
                >
                  <div class="flex-1 grid grid-cols-2 gap-3">
                    <ValidatedInput
                        v-model="element.name"
                        name="elementName"
                        label="Nom"
                        type="text"
                        :rules="[validationRules.required]"
                        placeholder="Nom"
                    />
                    <ValidatedInput
                        v-model="element.code"
                        name="elementCode"
                        label="Code (optionnel)"
                        type="text"
                        :rules="[]"
                        placeholder="Code (optionnel)"
                    />
                  </div>
                  <Button
                      severity="danger"
                      type="button"
                      @click="removeElement(index)"
                      class="p-2 text-red-500 hover:text-red-700 rounded"
                  >
                    <XMarkIcon class="w-4 h-4" />
                  </Button>
                </div>
              </div>

              <div v-if="localSection.opt?.elements?.length === 0" class="text-center py-8 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg">
                <ListBulletIcon class="w-12 h-12 text-gray-400 mx-auto mb-4" />
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                  Aucun élément ajouté
                </p>
                <Button
                    type="button"
                    @click="addElement"
                >
                  Ajouter le premier élément
                </Button>
              </div>

              <!-- Preview -->
              <div v-if="localSection.opt?.elements?.length > 0" class="mt-6 p-4 bg-blue-50 dark:bg-blue-900 rounded-lg">
                <h4 class="text-sm font-medium text-blue-900 dark:text-blue-200 mb-2">
                  Aperçu des sections générées
                </h4>
                <div class="space-y-1 text-sm text-blue-800 dark:text-blue-300">
                  <div
                      v-for="element in localSection.opt.elements.slice(0, 3)"
                      :key="element.id"
                  >
                    • {{ generateSectionTitle(element.name) }}
                  </div>
                  <div v-if="localSection.opt?.elements?.length > 3" class="text-blue-600 dark:text-blue-400">
                    ... et {{ localSection.opt.elements.length - 3 }} autres sections
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
          <Button
              severity="secondary"
              type="button" @click="$emit('close')">
            Annuler
          </Button>
          <Button
              type="submit">
            {{ isEditing ? 'Mettre à jour' : 'Créer' }} la section
          </Button>
        </div>
      </form>

      <!-- Import CSV Modal -->
      <div v-if="showImportModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-60" @click="showImportModal = false">
        <div
            class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-md mx-4"
            @click.stop
        >
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              Importer depuis CSV
            </h3>
            <Button
                @click="showImportModal = false"
                class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
            >
              <XMarkIcon class="w-5 h-5" />
            </Button>
          </div>

          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Fichier CSV
              </label>
              <input
                  ref="csvFileInput"
                  type="file"
                  accept=".csv"
                  @change="handleCSVImport"
                  class="input-field"
              />
            </div>

            <div class="text-sm text-gray-600 dark:text-gray-400">
              <p class="mb-2">Format attendu :</p>
              <code class="block p-2 bg-gray-100 dark:bg-gray-700 rounded text-xs">
                nom,code<br>
                Mathématiques,MATH<br>
                Français,FR<br>
                Histoire,HIST
              </code>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import { ValidatedInput, validationRules } from '@components';
import { ref, computed, onMounted, watch } from 'vue';
import {
  XMarkIcon,
  DocumentTextIcon,
  Cog6ToothIcon,
  PlusIcon,
  DocumentArrowUpIcon,
  ListBulletIcon,
  AcademicCapIcon,
  BuildingOfficeIcon,
  UserGroupIcon,
  CubeIcon,
  WrenchScrewdriverIcon
} from '@heroicons/vue/24/outline';
import type { Section, ConfigurableElement } from '@types';
import { v4 as uuidv4 } from 'uuid';

interface Props {
  section?: Section | null;
}

interface Emits {
  close: [];
  save: [section: Section];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const showImportModal = ref(false);
const csvFileInput = ref<HTMLInputElement>();

const sourceTypes = [
  { value: 'subjects', label: 'Matières', icon: AcademicCapIcon },
  { value: 'departments', label: 'Départements', icon: BuildingOfficeIcon },
  { value: 'teams', label: 'Équipes', icon: UserGroupIcon },
  { value: 'products', label: 'Produits', icon: CubeIcon },
  { value: 'custom', label: 'Personnalisé', icon: WrenchScrewdriverIcon }
];

const localSection = ref<Section>({
  id: null,
  title: '',
  description: '',
  questions: [],
  typeSection: 'normal',
  uuid: uuidv4(),
  sortOrder: 0
});

const isEditing = computed(() => !!props.section);

// Watch for section type changes and initialize configurable object
watch(() => localSection.value.type, (newType) => {
  if (newType === 'configurable' && !localSection.value.opt) {
    localSection.value.opt = {
      sourceType: 'subjects',
      sourceLabel: 'Matières',
      elements: [],
      titleTemplate: 'Évaluation de {element}'
    };
  }
});
function selectSourceType(sourceType: string) {
  if (!localSection.value.opt) {
    localSection.value.opt = {
      sourceType: sourceType as any,
      sourceLabel: '',
      elements: [],
      titleTemplate: 'Évaluation de {element}'
    };
  } else {
    localSection.value.opt.sourceType = sourceType as any;
  }

  // Set default label
  const selectedType = sourceTypes.find(t => t.value === sourceType);
  if (selectedType && sourceType !== 'custom') {
    localSection.value.opt.sourceLabel = selectedType.label;
  }
}

function addElement() {
  if (!localSection.value.opt) return;

  const newElement: ConfigurableElement = {
    id: uuidv4(),
    name: '',
    code: ''
  };

  localSection.value.opt.elements.push(newElement);
}

function removeElement(index: number) {
  if (!localSection.value.opt) return;
  localSection.value.opt.elements.splice(index, 1);
}

function generateSectionTitle(elementName: string): string {
  if (!localSection.value.opt?.titleTemplate) return elementName;
  return localSection.value.opt.titleTemplate.replace('{element}', elementName);
}

function handleCSVImport(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0];
  if (!file || !localSection.value.opt) return;

  const reader = new FileReader();
  reader.onload = (e) => {
    const csvText = e.target?.result as string;
    const lines = csvText.split('\n').filter(line => line.trim());

    if (lines.length < 2) return; // Need header + at least one data line

    const headers = lines[0].split(',').map(h => h.trim());
    const nameIndex = headers.findIndex(h => h.toLowerCase().includes('nom') || h.toLowerCase().includes('name'));
    const codeIndex = headers.findIndex(h => h.toLowerCase().includes('code'));

    const elements: ConfigurableElement[] = [];

    for (let i = 1; i < lines.length; i++) {
      const values = lines[i].split(',').map(v => v.trim());
      if (values.length > nameIndex && values[nameIndex]) {
        elements.push({
          id: uuidv4(),
          name: values[nameIndex],
          code: codeIndex >= 0 && values[codeIndex] ? values[codeIndex] : undefined
        });
      }
    }

    localSection.value.opt!.elements = elements;
    showImportModal.value = false;
  };

  reader.readAsText(file);
}

function saveSection() {
  if (!localSection.value.title.trim()) return;

  // Generate ID if new section
  if (!localSection.value.uuid) {
    localSection.value.uuid = uuidv4();
  }

  // Clean up configurable settings if normal section
  if (localSection.value.typeSection === 'normal') {
    delete localSection.value.opt;
  }

  emit('save', { ...localSection.value });
}

onMounted(() => {
  if (props.section) {
    localSection.value = { ...props.section };

    // Initialize configurable if needed
    if (localSection.value.typeSection === 'configurable' && !localSection.value.opt) {
      localSection.value.opt = {
        sourceType: 'subjects',
        sourceLabel: 'Matières',
        elements: [],
        titleTemplate: 'Évaluation de {element}'
      };
    }
  }
});
</script>
