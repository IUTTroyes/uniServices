<template>
  <Dialog
    :style="{ width: '92vw', maxWidth: '850px' }"
    :visible="true"
    :modal="true"
    :closable="true"
    :draggable="false"
    @update:visible="$emit('close')"
  >
    <template #header>
      <DialogHeader
        :icon="Cog6ToothIcon"
        :title="isEditing ? 'Modifier la section' : 'Configuration de la section'"
        subtitle="Définissez le type, les informations générales et les éléments évalués"
      />
    </template>

    <form @submit.prevent="saveSection" class="q-form">
      <!-- Section Type -->
      <FormSection :icon="Squares2X2Icon" tone="blue" title="Type de section" subtitle="Choisissez comment cette section est structurée">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div
            :class="['q-choice', localSection.typeSection === 'normal' && 'q-choice-active']"
            @click="localSection.typeSection = 'normal'"
          >
            <DocumentTextIcon class="w-5 h-5 text-primary-600 dark:text-primary-400 shrink-0 mt-0.5" />
            <div>
              <span class="q-choice-title">Section normale</span>
              <span class="q-choice-desc block">Section standard avec questions fixes posées une seule fois</span>
            </div>
          </div>

          <div
            :class="['q-choice', localSection.typeSection === 'configurable' && 'q-choice-active']"
            @click="localSection.typeSection = 'configurable'"
          >
            <Cog6ToothIcon class="w-5 h-5 text-primary-600 dark:text-primary-400 shrink-0 mt-0.5" />
            <div>
              <span class="q-choice-title">Section configurable</span>
              <span class="q-choice-desc block">Section répétée dynamiquement pour chaque élément d'une liste</span>
            </div>
          </div>
        </div>
      </FormSection>

      <!-- Basic Section Info -->
      <FormSection :icon="DocumentTextIcon" tone="slate" title="Informations générales">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <FormField label="Titre de la section" for="section-title" required>
            <input
              id="section-title"
              v-model="localSection.title"
              type="text"
              class="q-input"
              placeholder="Ex: Évaluation pédagogique"
              required
            />
          </FormField>

          <div v-if="localSection.typeSection === 'configurable' && localSection.opt">
            <FormField
              label="Modèle de titre dynamique"
              for="section-title-template"
              hint="Utilisez {element} pour insérer automatiquement le nom"
            >
              <input
                id="section-title-template"
                v-model="localSection.opt.titleTemplate"
                type="text"
                class="q-input"
                placeholder="Évaluation de {element}"
              />
            </FormField>
          </div>
        </div>

        <FormField label="Description (optionnelle)" for="section-description">
          <textarea
            id="section-description"
            v-model="localSection.description"
            rows="3"
            class="q-input"
            placeholder="Précisez le contexte ou les consignes pour cette section..."
          />
        </FormField>
      </FormSection>

      <!-- Configurable Section Settings -->
      <FormSection
        v-if="localSection.typeSection === 'configurable' && localSection.opt"
        :icon="ListBulletIcon"
        tone="purple"
        title="Configuration des éléments à évaluer"
        subtitle="Sélectionnez les matières, ressources, SAÉ ou prévisionnels concernés"
      >
        <!-- Source Type Selection -->
        <div>
          <label class="q-label">Type d'éléments</label>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-2.5">
            <div
              v-for="sourceType in sourceTypes"
              :key="sourceType.value"
              :class="[
                'p-3 rounded-xl border text-center cursor-pointer transition-all flex flex-col items-center justify-center gap-1.5 shadow-2xs',
                localSection.opt?.sourceType === sourceType.value
                  ? 'bg-primary-50/70 dark:bg-primary-950/40 border-primary-400 dark:border-primary-700 text-primary-700 dark:text-primary-300 font-semibold'
                  : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700/80 text-slate-700 dark:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600'
              ]"
              @click="selectSourceType(sourceType.value)"
            >
              <component :is="sourceType.icon" class="w-5 h-5" />
              <span class="text-xs">{{ sourceType.label }}</span>
            </div>
          </div>
        </div>

        <!-- Elements Management -->
        <div v-if="localSection.opt?.sourceType" class="space-y-4 pt-2">
          <!-- Sélecteur de semestres -->
          <div>
            <div class="flex items-center justify-between gap-2 mb-2">
              <label class="q-label !mb-0">Filtrer par semestre</label>
              <div class="flex items-center gap-3 text-xs">
                <button type="button" class="font-semibold text-primary-600 dark:text-primary-400 hover:underline border-0 bg-transparent cursor-pointer" @click="selectAllSemestres()">
                  Tout sélectionner
                </button>
                <span class="text-slate-300 dark:text-slate-600">|</span>
                <button type="button" class="text-slate-500 dark:text-slate-400 hover:underline border-0 bg-transparent cursor-pointer" @click="localSection.opt.selectedSemesters = []">
                  Effacer
                </button>
              </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 p-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-white dark:bg-slate-800">
              <label
                v-for="semestre in semestresList"
                :key="semestre.id"
                :class="[
                  'px-3 py-1.5 rounded-lg border text-xs font-medium cursor-pointer transition-all flex items-center gap-2 select-none',
                  localSection.opt.selectedSemesters?.includes(semestre.id)
                    ? 'bg-primary-50 dark:bg-primary-950/60 border-primary-300 dark:border-primary-700 text-primary-700 dark:text-primary-300 font-semibold'
                    : 'bg-slate-50 dark:bg-slate-900/40 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:border-slate-300'
                ]"
              >
                <input
                  type="checkbox"
                  :value="semestre.id"
                  v-model="localSection.opt.selectedSemesters"
                  class="q-check !w-3.5 !h-3.5"
                />
                <span>{{ semestre.libelle }}</span>
              </label>
            </div>
          </div>

          <!-- Checklist of API Elements -->
          <div>
            <label class="q-label">Éléments disponibles à évaluer</label>

            <div v-if="isLoadingElements" class="flex justify-center items-center py-6 text-xs text-slate-500 gap-2">
              <i class="pi pi-spin pi-spinner text-primary-500 text-lg"></i>
              <span>Chargement des éléments...</span>
            </div>

            <div v-else-if="availableElements.length === 0" class="text-xs text-slate-500 py-6 text-center border border-dashed border-slate-300 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-800">
              Aucun élément disponible pour les semestres sélectionnés.
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-60 overflow-y-auto border border-slate-200 dark:border-slate-700/80 rounded-xl p-3 bg-white dark:bg-slate-800">
              <label 
                v-for="avail in availableElements" 
                :key="avail.id" 
                :class="[
                  'flex items-start gap-2.5 p-2.5 rounded-lg border text-xs cursor-pointer transition-colors',
                  isElementSelected(avail)
                    ? 'bg-primary-50/50 dark:bg-primary-950/30 border-primary-200 dark:border-primary-800/60'
                    : 'border-transparent hover:bg-slate-50 dark:hover:bg-slate-700/50'
                ]"
              >
                <input 
                  type="checkbox" 
                  :checked="isElementSelected(avail)" 
                  @change="toggleElementSelection(avail)"
                  class="q-check mt-0.5"
                />
                <div class="flex-1 min-w-0">
                  <span class="font-medium text-slate-900 dark:text-white block truncate">{{ avail.name }}</span>
                  <span v-if="avail.code" class="inline-block mt-0.5 text-[10px] px-1.5 py-0.5 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded font-mono">{{ avail.code }}</span>
                </div>
              </label>
            </div>
          </div>

          <!-- Custom / Manually Added Elements -->
          <div class="pt-3 border-t border-slate-200 dark:border-slate-700/80">
            <div class="flex items-center justify-between gap-3 mb-2">
              <label class="q-label !mb-0">Éléments personnalisés / manuels</label>
              <Button
                type="button"
                severity="secondary"
                outlined
                size="small"
                icon="pi pi-plus"
                label="Ajouter un élément"
                @click="addCustomElement"
              />
            </div>

            <p v-if="customElements.length === 0" class="q-hint italic">
              Aucun élément personnalisé ajouté.
            </p>
            <div v-else class="space-y-2 max-h-48 overflow-y-auto pr-1">
              <div
                v-for="element in customElements"
                :key="element.id"
                class="flex items-center gap-2 p-2.5 border border-slate-200 dark:border-slate-700/80 rounded-xl bg-white dark:bg-slate-800"
              >
                <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-2">
                  <input
                    v-model="element.name"
                    type="text"
                    class="q-input q-input-sm"
                    placeholder="Nom de l'élément"
                    required
                  />
                  <input
                    v-model="element.code"
                    type="text"
                    class="q-input q-input-sm"
                    placeholder="Code (optionnel)"
                  />
                </div>
                <Button
                  severity="danger"
                  text
                  rounded
                  icon="pi pi-times"
                  aria-label="Supprimer"
                  @click="removeCustomElement(element)"
                />
              </div>
            </div>
          </div>

          <!-- Preview -->
          <div v-if="localSection.opt?.elements?.length > 0" class="q-callout q-callout-info">
            <InformationCircleIcon class="w-4 h-4 shrink-0 mt-0.5" />
            <div class="space-y-1">
              <p class="font-semibold">Aperçu des sections générées ({{ localSection.opt.elements.length }})</p>
              <div v-for="element in localSection.opt.elements.slice(0, 3)" :key="element.id" class="text-xs">
                • {{ generateSectionTitle(element.name) }}
              </div>
              <div v-if="localSection.opt?.elements?.length > 3" class="text-[11px] opacity-80">
                ... et {{ localSection.opt.elements.length - 3 }} autres sections
              </div>
            </div>
          </div>
        </div>
      </FormSection>

      <!-- Actions -->
      <div class="q-actions">
        <Button severity="secondary" outlined type="button" label="Annuler" @click="$emit('close')" />
        <Button type="submit" icon="pi pi-check" :label="isEditing ? 'Mettre à jour' : 'Créer la section'" />
      </div>
    </form>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import {
  AcademicCapIcon,
  BuildingOfficeIcon,
  Cog6ToothIcon,
  CubeIcon,
  DocumentTextIcon,
  InformationCircleIcon,
  ListBulletIcon,
  Squares2X2Icon,
  UserGroupIcon
} from '@heroicons/vue/24/outline';
import type { ConfigurableElement, Section } from '@types';
import {
  getDepartementSemestresService,
  getSemestrePreviService,
  getEnseignementsService
} from '@requests';
import { v4 as uuidv4 } from 'uuid';
import { DialogHeader, FormField, FormSection } from '../Form';

interface Props {
  section?: Section | null;
}

interface Emits {
  close: [];
  save: [section: Section];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const sourceTypes = [
  { value: 'matiere', label: 'Matières', icon: AcademicCapIcon },
  { value: 'ressource', label: 'Ressources', icon: BuildingOfficeIcon },
  { value: 'sae', label: 'SAÉ', icon: CubeIcon },
  { value: 'previsionnel', label: 'Prévisionnels', icon: UserGroupIcon }
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

const semestresList = computed(() => {
  return Array.isArray(semestres.value) ? semestres.value : Object.values(semestres.value || {});
});

const loadSemestres = async () => {
  if (Object.keys(semestres.value).length === 0) {
    const departement = localStorage.getItem('departement');
    semestres.value = await getDepartementSemestresService(departement);
  }
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
    semesters = semestresList.value.map((s: any) => s.id);
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
  localSection.value.opt.selectedSemesters = semestresList.value.map(item => item.id);
};

function generateSectionTitle(elementName: string): string {
  if (!localSection.value.opt?.titleTemplate) return elementName;
  return localSection.value.opt.titleTemplate.replace('{element}', elementName);
}

function saveSection() {
  if (!localSection.value.title.trim()) return;

  if (!localSection.value.uuid) {
    localSection.value.uuid = uuidv4();
  }

  if (localSection.value.typeSection === 'normal') {
    delete localSection.value.opt;
  } else if (localSection.value.typeSection === 'configurable' && localSection.value.opt) {
    (localSection.value.opt as any).repeat_source = localSection.value.opt.sourceType;
  }

  emit('save', { ...localSection.value });
}

onMounted(async () => {
  if (props.section) {
    localSection.value = { ...props.section };

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
