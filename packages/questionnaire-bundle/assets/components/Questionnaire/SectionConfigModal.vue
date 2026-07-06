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
                <DocumentTextIcon class="w-6 h-6 text-primary-600 dark:text-primary-400 mt-1"/>
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
                <Cog6ToothIcon class="w-6 h-6 text-primary-600 dark:text-primary-400 mt-1"/>
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

          <div v-if="localSection.typeSection === 'configurable' && localSection.opt">
            <ValidatedInput
                v-model="localSection.opt.titleTemplate"
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
        <div v-if="localSection.typeSection === 'configurable' && localSection.opt" class="space-y-6">
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
                  <component :is="sourceType.icon" class="w-6 h-6 mx-auto mb-2"/>
                  <div class="text-sm font-medium">{{ sourceType.label }}</div>
                </div>
              </div>
            </div>

            <!-- Elements Management -->
            <div v-if="localSection.opt?.sourceType">
              <div class="flex items-center justify-between mb-4">
                <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                  Liste des {{ localSection.opt.sourceLabel.toLowerCase() }}
                </h4>
                <div class="flex items-center space-x-2">
                  <!--                  <Button-->
                  <!--                      severity="secondary"-->
                  <!--                      type="button"-->
                  <!--                      @click="showImportModal = true"-->
                  <!--                      class="text-sm"-->
                  <!--                  >-->
                  <!--                    <DocumentArrowUpIcon class="w-4 h-4" />-->
                  <!--                    Importer CSV-->
                  <!--                  </Button>-->
                  <!--                  <Button-->
                  <!--                      type="button"-->
                  <!--                      @click="addElement"-->
                  <!--                      class="text-sm"-->
                  <!--                  >-->
                  <!--                    <PlusIcon class="w-4 h-4" />-->
                  <!--                    Ajouter-->
                  <!--                  </Button>-->
                </div>
              </div>

              <!-- Elements List with filtre par semestre (exemple fictif) -->
              <div class="space-y-3">
                <!-- Sélecteur de semestres (multi-select simple / checkboxes) -->
                <div class="mb-3">
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Semestres (filtrer les éléments)
                  </label>

                  <div class="flex items-center space-x-2 mb-2">
                    <!-- todo: composant CheckboxList -->
                    <label class="inline-flex items-center space-x-2" v-for="semestre in semestres" :key="semestre.id">
                      <input type="checkbox" :value="semestre.id" v-model="localSection.opt.selectedSemesters"/>
                      <span class="text-sm">{{ semestre.libelle }}</span>
                    </label>


                    <!-- Action rapide : sélectionner tous / effacer -->
                    <button type="button" class="text-sm text-primary-600 ml-4"
                            @click="selectAllSemestres()">
                      Tous
                    </button>
                    <button type="button" class="text-sm text-gray-500"
                            @click="localSection.opt.selectedSemesters = []">
                      Effacer
                    </button>
                  </div>

                  <!-- Aperçu des semestres choisis -->
                  <div v-if="localSection.opt.selectedSemesters && localSection.opt.selectedSemesters.length"
                       class="flex flex-wrap gap-2">
                      <span
                          v-for="id in localSection.opt.selectedSemesters"
                          :key="id"
                          class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs"
                      >
                        {{ getSemestreLibelle(id) }}
                      </span>
                  </div>
                  <div v-else class="text-sm text-gray-500">Aucun filtre de semestre — tous les éléments sont affichés
                  </div>
                </div>

                <!-- Liste filtrée des éléments -->
                <div class="space-y-4">
                  <!-- Checklist of API Elements -->
                  <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Sélectionner les éléments à évaluer (depuis les semestres sélectionnés)
                    </label>

                    <div v-if="isLoadingElements" class="flex justify-center items-center py-8">
                      <i class="pi pi-spin pi-spinner text-primary-500 text-2xl mr-2"></i>
                      <span class="text-sm text-gray-500">Chargement des éléments...</span>
                    </div>

                    <div v-else-if="availableElements.length === 0" class="text-sm text-gray-500 py-4 text-center border border-dashed border-gray-200 dark:border-gray-700 rounded-lg">
                      Aucun élément disponible pour les semestres sélectionnés.
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-60 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                      <label 
                        v-for="avail in availableElements" 
                        :key="avail.id" 
                        class="flex items-start space-x-2 p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded cursor-pointer text-sm"
                      >
                        <input 
                          type="checkbox" 
                          :checked="isElementSelected(avail)" 
                          @change="toggleElementSelection(avail)"
                          class="mt-1 rounded text-primary-600 focus:ring-primary-500 cursor-pointer"
                        />
                        <div class="flex-1">
                          <span class="font-medium text-gray-900 dark:text-white">{{ avail.name }}</span>
                          <span v-if="avail.code" class="ml-2 text-xs px-1.5 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded font-mono">{{ avail.code }}</span>
                        </div>
                      </label>
                    </div>
                  </div>

                  <!-- Custom / Manually Added Elements -->
                  <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
                    <div class="flex items-center justify-between mb-3">
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Éléments personnalisés / manquants
                      </label>
                      <Button
                        type="button"
                        severity="secondary"
                        @click="addCustomElement"
                        class="text-xs flex items-center"
                      >
                        <PlusIcon class="w-4 h-4 mr-1" />
                        Ajouter un élément manquant
                      </Button>
                    </div>

                    <div v-if="customElements.length === 0" class="text-xs text-gray-400 italic py-2">
                      Aucun élément personnalisé ajouté.
                    </div>
                    <div v-else class="space-y-3 max-h-48 overflow-y-auto pr-1">
                      <div
                        v-for="element in customElements"
                        :key="element.id"
                        class="flex items-center space-x-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50/50 dark:bg-gray-800/30"
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
                          @click="removeCustomElement(element)"
                          class="p-2 text-red-500 hover:text-red-700 rounded flex items-center justify-center"
                        >
                          <XMarkIcon class="w-4 h-4"/>
                        </Button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Preview -->
              <div v-if="localSection.opt?.elements?.length > 0"
                   class="mt-6 p-4 bg-blue-50 dark:bg-blue-900 rounded-lg">
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

      <!--      &lt;!&ndash; Import CSV Modal &ndash;&gt;-->
      <!--      <div v-if="showImportModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-60" @click="showImportModal = false">-->
      <!--        <div-->
      <!--            class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-md mx-4"-->
      <!--            @click.stop-->
      <!--        >-->
      <!--          <div class="flex items-center justify-between mb-4">-->
      <!--            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">-->
      <!--              Importer depuis CSV-->
      <!--            </h3>-->
      <!--            <Button-->
      <!--                @click="showImportModal = false"-->
      <!--                class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"-->
      <!--            >-->
      <!--              <XMarkIcon class="w-5 h-5" />-->
      <!--            </Button>-->
      <!--          </div>-->

      <!--          <div class="space-y-4">-->
      <!--            <div>-->
      <!--              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">-->
      <!--                Fichier CSV-->
      <!--              </label>-->
      <!--              <input-->
      <!--                  ref="csvFileInput"-->
      <!--                  type="file"-->
      <!--                  accept=".csv"-->
      <!--                  @change="handleCSVImport"-->
      <!--                  class="input-field"-->
      <!--              />-->
      <!--            </div>-->

      <!--            <div class="text-sm text-gray-600 dark:text-gray-400">-->
      <!--              <p class="mb-2">Format attendu :</p>-->
      <!--              <code class="block p-2 bg-gray-100 dark:bg-gray-700 rounded text-xs">-->
      <!--                nom,code<br>-->
      <!--                Mathématiques,MATH<br>-->
      <!--                Français,FR<br>-->
      <!--                Histoire,HIST-->
      <!--              </code>-->
      <!--            </div>-->
      <!--          </div>-->
      <!--        </div>-->
      <!--      </div>-->
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import {ValidatedInput, validationRules} from '@components';
import {computed, onMounted, ref, watch} from 'vue';
import {
  AcademicCapIcon,
  BuildingOfficeIcon,
  Cog6ToothIcon,
  CubeIcon,
  DocumentTextIcon,
  ListBulletIcon,
  PlusIcon,
  UserGroupIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline';
import type {ConfigurableElement, Section} from '@types';
import {
  getDepartementSemestresService,
  getSemestrePreviService,
  getEnseignementsService
} from '@requests';
import {v4 as uuidv4} from 'uuid';

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
// const csvFileInput = ref<HTMLInputElement>();

const sourceTypes = [
  {value: 'matiere', label: 'Matières', icon: AcademicCapIcon},
  {value: 'ressource', label: 'Ressources', icon: BuildingOfficeIcon},
  {value: 'sae', label: 'SAÉ', icon: CubeIcon},
  {value: 'previsionnel', label: 'Prévisionnels', icon: UserGroupIcon}
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

const semestres = ref<any>({});
const isLoadingElements = ref(false);
const availableElements = ref<ConfigurableElement[]>([]);

const loadSemestres = async () => {
  if (Object.keys(semestres.value).length === 0) {
    const departement = localStorage.getItem('departement');
    semestres.value = await getDepartementSemestresService(departement);
  }
};

const getSemestreLibelle = (id: number | string) => {
  const list = Array.isArray(semestres.value) ? semestres.value : Object.values(semestres.value || {});
  const found = list.find((s: any) => s.id === id || String(s.id) === String(id));
  return found ? found.libelle : id;
};

const fetchElements = async () => {
  if (!localSection.value.opt) return;

  let semesters = localSection.value.opt.selectedSemesters || [];
  const sourceType = localSection.value.opt.sourceType;

  if (!sourceType) {
    availableElements.value = [];
    return;
  }

  // Get active academic year id
  const selectedAnneeUnivString = localStorage.getItem('selectedAnneeUniv');
  const anneeUnivId = selectedAnneeUnivString ? JSON.parse(selectedAnneeUnivString).id : null;

  // If no semesters selected, fetch for all semesters of the department
  if (semesters.length === 0) {
    const list = Array.isArray(semestres.value) ? semestres.value : Object.values(semestres.value || {});
    semesters = list.map((s: any) => s.id);
  }

  if (semesters.length === 0) {
    availableElements.value = [];
    return;
  }

  isLoadingElements.value = true;
  try {
    if (sourceType === 'previsionnel') {
      const responses = await Promise.all(
        semesters.map(async (semId) => {
          try {
            const data = await getSemestrePreviService(semId, anneeUnivId);
            return (data || []).map((item: any) => ({
              ...item,
              semId: semId
            }));
          } catch (err) {
            console.error(`Erreur lors de la récupération des prévisionnels pour le semestre ${semId}:`, err);
            return [];
          }
        })
      );
      const allPrevis = responses.flat().filter(Boolean);

      availableElements.value = allPrevis.map((item: any) => {
        const name = item.intervenant
          ? `${item.libelleEnseignement} (${item.intervenant})`
          : item.libelleEnseignement;
        return {
          id: uuidv4(),
          name: name,
          code: item.codeEnseignement,
          semesters: [item.semId]
        };
      });
    } else {
      // matiere, ressource, sae
      const responses = await Promise.all(
        semesters.map(async (semId) => {
          try {
            const params = {
              semestre: semId,
              anneeUniversitaire: anneeUnivId
            };
            const data = await getEnseignementsService(params);
            return (data || []).map((item: any) => ({
              ...item,
              semId: semId
            }));
          } catch (err) {
            console.error(`Erreur lors de la récupération des enseignements pour le semestre ${semId}:`, err);
            return [];
          }
        })
      );
      const allEnseignements = responses.flat().filter(Boolean);
      const filtered = allEnseignements.filter((item: any) => item.type === sourceType);

      availableElements.value = filtered.map((item: any) => ({
        id: uuidv4(),
        name: item.libelle,
        code: item.codeEnseignement,
        semesters: [item.semId]
      }));
    }
  } catch (error) {
    console.error('Erreur lors du chargement des éléments configurables:', error);
  } finally {
    isLoadingElements.value = false;
  }
};

const isElementSelected = (avail: ConfigurableElement) => {
  if (!localSection.value.opt?.elements) return false;
  return localSection.value.opt.elements.some(el => {
    if (avail.code && el.code) return avail.code === el.code;
    return avail.name === el.name;
  });
};

const toggleElementSelection = (avail: ConfigurableElement) => {
  if (!localSection.value.opt) return;
  if (!localSection.value.opt.elements) {
    localSection.value.opt.elements = [];
  }

  const index = localSection.value.opt.elements.findIndex(el => {
    if (avail.code && el.code) return avail.code === el.code;
    return avail.name === el.name;
  });

  if (index > -1) {
    localSection.value.opt.elements.splice(index, 1);
  } else {
    localSection.value.opt.elements.push({
      id: avail.id || uuidv4(),
      name: avail.name,
      code: avail.code,
      semesters: avail.semesters || []
    });
  }
};

const customElements = computed(() => {
  if (!localSection.value.opt?.elements) return [];
  return localSection.value.opt.elements.filter(el => {
    if (el.isCustom) return true;
    const matchesAvail = availableElements.value.some(avail => {
      if (avail.code && el.code) return avail.code === el.code;
      return avail.name === el.name;
    });
    return !matchesAvail;
  });
});

function addCustomElement() {
  if (!localSection.value.opt) return;
  if (!localSection.value.opt.elements) {
    localSection.value.opt.elements = [];
  }
  localSection.value.opt.elements.push({
    id: uuidv4(),
    name: '',
    code: '',
    semesters: [],
    isCustom: true
  });
}

function removeCustomElement(element: ConfigurableElement) {
  if (!localSection.value.opt?.elements) return;
  const index = localSection.value.opt.elements.indexOf(element);
  if (index > -1) {
    localSection.value.opt.elements.splice(index, 1);
  }
}

// Watch for section type changes and initialize configurable object
watch(() => localSection.value.typeSection, async (newType) => {
  if (newType === 'configurable') {
    await loadSemestres();
    if (!localSection.value.opt) {
      localSection.value.opt = {
        sourceType: 'previsionnel',
        sourceLabel: 'Prévisionnels',
        elements: [],
        titleTemplate: 'Évaluation de {element}',
        selectedSemesters: []
      };
    }
    await fetchElements();
  }
});

watch(
  () => localSection.value.opt?.selectedSemesters,
  async () => {
    if (!localSection.value.opt) return;
    await fetchElements();
  },
  { deep: true }
);

watch(
  () => localSection.value.opt?.sourceType,
  async (newType, oldType) => {
    if (!localSection.value.opt) return;
    if (newType !== oldType) {
      await fetchElements();
    }
  }
);

function selectSourceType(sourceType: string) {
  if (!localSection.value.opt) {
    localSection.value.opt = {
      sourceType: sourceType as any,
      sourceLabel: '',
      elements: [],
      titleTemplate: 'Évaluation de {element}',
      selectedSemesters: []
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

const selectAllSemestres = () => {
  if (!localSection.value.opt) return;
  const list = Array.isArray(semestres.value) ? semestres.value : Object.values(semestres.value || {});
  localSection.value.opt.selectedSemesters = list.map(item => item.id);
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

function saveSection() {
  if (!localSection.value.title.trim()) return;

  // Generate ID if new section
  if (!localSection.value.uuid) {
    localSection.value.uuid = uuidv4();
  }

  // Clean up configurable settings if normal section
  if (localSection.value.typeSection === 'normal') {
    delete localSection.value.opt;
  } else if (localSection.value.typeSection === 'configurable' && localSection.value.opt) {
    (localSection.value.opt as any).repeat_source = localSection.value.opt.sourceType;
  }

  emit('save', {...localSection.value});
}

onMounted(async () => {
  if (props.section) {
    localSection.value = {...props.section};

    // Initialize configurable if needed
    if (localSection.value.typeSection === 'configurable') {
      await loadSemestres();
      if (!localSection.value.opt) {
        localSection.value.opt = {
          sourceType: 'previsionnel',
          sourceLabel: 'Prévisionnels',
          elements: [],
          titleTemplate: 'Évaluation de {element}',
          selectedSemesters: []
        };
      } else if (!localSection.value.opt.selectedSemesters) {
        localSection.value.opt.selectedSemesters = [];
      }
      await fetchElements();
    }
  }
});
</script>
