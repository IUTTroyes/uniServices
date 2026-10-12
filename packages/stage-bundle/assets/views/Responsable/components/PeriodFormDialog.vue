<script setup>
import { ref, watch, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import { ValidatedInput, validationRules } from '@components';
import {
  BriefcaseIcon,
  CalendarIcon,
  AcademicCapIcon,
  DocumentTextIcon,
  FolderIcon,
  PlusIcon,
  TrashIcon,
  ArrowUpTrayIcon,
  DocumentIcon,
  CheckIcon
} from '@heroicons/vue/24/outline';
import DialogHeader from '../../../components/Form/DialogHeader.vue';
import FormField from '../../../components/Form/FormField.vue';

const props = defineProps({
  visible: {
    type: Boolean,
    required: true
  },
  period: {
    type: Object,
    default: null
  },
  dbAnneeUnivs: {
    type: Array,
    default: () => []
  },
  dbSemestres: {
    type: Array,
    default: () => []
  },
  teachers: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['update:visible', 'save']);

const toast = useToast();

const showCreateTab = ref('general'); // 'general' | 'interruptions' | 'convention' | 'files'
const localForm = ref({});

// Watch the visible prop to initialize/reset form when opening
watch(
  () => props.visible,
  (newVal) => {
    if (newVal) {
      showCreateTab.value = 'general';
      if (props.period) {
        // Edit mode
        localForm.value = {
          name: props.period.name,
          type: props.period.type,
          level: props.period.level,
          semestreProgrammeIri: props.period.semestreProgrammeIri || '',
          dates: props.period.dates,
          minWeeks: props.period.minWeeks,
          anneeUniversitaireIri: props.period.anneeUniversitaireIri,
          responsablePrincipalIri: props.period.responsablePrincipalIri,
          coResponsablesIris: [...(props.period.coResponsablesIris || [])],
          datesFlexibles: props.period.datesFlexibles,
          commentaireLibre: props.period.commentaireLibre || '',
          competencesVisees: props.period.competencesVisees || '',
          evalEntreprise: props.period.evalEntreprise || '',
          evalPedagogique: props.period.evalPedagogique || '',
          encadrement: props.period.encadrement || '',
          documentsRendre: props.period.documentsRendre || '',
          consignesFichiers: props.period.consignesFichiers ? JSON.parse(JSON.stringify(props.period.consignesFichiers)) : [],
          interruptions: props.period.interruptions ? JSON.parse(JSON.stringify(props.period.interruptions)) : [],
          soutenances: props.period.soutenances ? JSON.parse(JSON.stringify(props.period.soutenances)) : []
        };
      } else {
        // Creation mode
        const activeYearObj = props.dbAnneeUnivs.find(y => y.actif);
        const activeYearIri = activeYearObj ? activeYearObj['@id'] : '';
        localForm.value = {
          name: '',
          type: 'Stage',
          level: 'BUT 3',
          semestreProgrammeIri: '',
          dates: '',
          minWeeks: 16,
          anneeUniversitaireIri: activeYearIri,
          responsablePrincipalIri: '',
          coResponsablesIris: [],
          datesFlexibles: false,
          commentaireLibre: '',
          competencesVisees: '',
          evalEntreprise: '',
          evalPedagogique: '',
          encadrement: '',
          documentsRendre: '',
          consignesFichiers: [],
          interruptions: [],
          soutenances: []
        };
      }
    }
  },
  { immediate: true }
);

// Map lists for ValidatedInput type="select" options
const anneeOptions = computed(() => {
  return props.dbAnneeUnivs.map(a => ({
    label: a.libelle,
    value: a['@id']
  }));
});

const semestreOptions = computed(() => {
  return props.dbSemestres.map(s => ({
    label: s.libelle,
    value: s['@id']
  }));
});

const teacherOptions = computed(() => {
  return props.teachers.map(t => ({
    label: t.fullName || t,
    value: t.iri || t
  }));
});

const addInterruptionRow = () => {
  localForm.value.interruptions.push({ dateDebut: '', dateFin: '', motif: '' });
};

const removeInterruptionRow = (idx) => {
  localForm.value.interruptions.splice(idx, 1);
};

const addSoutenanceRow = () => {
  localForm.value.soutenances.push({ dateDebut: '', dateFin: '', dateRenduRapport: '', modalites: '' });
};

const removeSoutenanceRow = (idx) => {
  localForm.value.soutenances.splice(idx, 1);
};

const triggerConsigneUpload = () => {
  localForm.value.consignesFichiers.push({
    name: 'Consignes_Période_' + Date.now().toString().slice(-4) + '.pdf',
    size: '720 Ko'
  });
  toast.add({ severity: 'success', summary: 'Fichier ajouté', detail: 'Le document de consignes a été téléversé.', life: 2000 });
};

const removeConsigneFile = (idx) => {
  localForm.value.consignesFichiers.splice(idx, 1);
};

// Form errors validation tracking
const formErrors = ref({});
const handleValidation = (fieldName, result) => {
  formErrors.value[fieldName] = result.isValid ? null : result.errorMessage;
};

const handleSave = () => {
  // Simple validation to match existing logic
  if (!localForm.value.name?.trim() || !localForm.value.dates?.trim()) {
    toast.add({ severity: 'warn', summary: 'Champs manquants', detail: 'Veuillez remplir le nom et les dates.', life: 3000 });
    return;
  }
  emit('save', localForm.value);
};

const closeDialog = () => {
  emit('update:visible', false);
};
</script>

<template>
  <Dialog
    :visible="visible"
    modal
    :closable="false"
    :style="{ width: '850px', maxWidth: '95vw' }"
    class="p-dialog-clean"
    @update:visible="closeDialog"
  >
    <template #header>
      <DialogHeader
        :icon="BriefcaseIcon"
        tone="teal"
        :title="period ? 'Paramétrer la période' : 'Nouvelle période de stage / alternance'"
        :subtitle="period ? (period.name || 'Modifier les paramètres et le calendrier de cette période') : 'Définissez le calendrier, les règles et les consignes'"
        @close="closeDialog"
      />
    </template>

    <div class="space-y-4 pt-1">
      <!-- Subtabs within the dialog using q-tabs -->
      <div class="q-tabs">
        <button
          type="button"
          @click="showCreateTab = 'general'"
          :class="['q-tab', showCreateTab === 'general' ? 'q-tab-active' : '']"
        >
          <CalendarIcon class="w-4 h-4" />
          <span>Général & Dates</span>
        </button>
        <button
          type="button"
          @click="showCreateTab = 'interruptions'"
          :class="['q-tab', showCreateTab === 'interruptions' ? 'q-tab-active' : '']"
        >
          <AcademicCapIcon class="w-4 h-4" />
          <span>Interruptions & Soutenances</span>
        </button>
        <button
          type="button"
          @click="showCreateTab = 'convention'"
          :class="['q-tab', showCreateTab === 'convention' ? 'q-tab-active' : '']"
        >
          <DocumentTextIcon class="w-4 h-4" />
          <span>Convention & Modalités</span>
        </button>
        <button
          type="button"
          @click="showCreateTab = 'files'"
          :class="['q-tab', showCreateTab === 'files' ? 'q-tab-active' : '']"
        >
          <FolderIcon class="w-4 h-4" />
          <span>Consignes & Documents</span>
        </button>
      </div>

      <div class="py-1 space-y-4 max-h-[60vh] overflow-y-auto pr-1">

        <!-- SECTION 1: GENERAL & DATES -->
        <div v-if="showCreateTab === 'general'" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <ValidatedInput
              v-model="localForm.name"
              name="name"
              label="Nom de la période"
              type="text"
              placeholder="Ex: BUT 3 Informatique - Stage 2026"
              :rules="validationRules.required"
              @validation="res => handleValidation('name', res)"
            />

            <ValidatedInput
              v-model="localForm.anneeUniversitaireIri"
              name="anneeUniversitaireIri"
              label="Année universitaire"
              type="select"
              placeholder="-- Sélectionner l'année --"
              :options="anneeOptions"
              :rules="validationRules.required"
              @validation="res => handleValidation('anneeUniversitaireIri', res)"
            />
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <ValidatedInput
              v-model="localForm.type"
              name="type"
              label="Type de contrat"
              type="select"
              :options="[{ label: 'Stage classique', value: 'Stage' }, { label: 'Alternance / Apprentissage', value: 'Alternance' }]"
            />

            <ValidatedInput
              v-model="localForm.semestreProgrammeIri"
              name="semestreProgrammeIri"
              label="Niveau d'études / Semestre"
              type="select"
              placeholder="-- Aucun semestre --"
              :options="semestreOptions"
            />

            <ValidatedInput
              v-model="localForm.minWeeks"
              name="minWeeks"
              label="Durée min. (Semaines)"
              type="number"
              :min="1"
            />
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <ValidatedInput
              v-model="localForm.dates"
              name="dates"
              label="Dates de la période"
              type="text"
              placeholder="Ex: 02/03/2026 au 26/06/2026"
              :rules="validationRules.required"
              @validation="res => handleValidation('dates', res)"
            />

            <div class="flex items-center gap-2 pt-6">
              <input
                type="checkbox"
                id="datesFlex"
                v-model="localForm.datesFlexibles"
                class="q-check"
              />
              <label for="datesFlex" class="text-xs font-semibold text-slate-700 dark:text-slate-300 cursor-pointer">
                Autoriser des dates flexibles pour l'étudiant
              </label>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <ValidatedInput
              v-model="localForm.responsablePrincipalIri"
              name="responsablePrincipalIri"
              label="Responsable Principal"
              type="select"
              placeholder="-- Sélectionner le créateur --"
              :options="teacherOptions"
            />

            <FormField label="Co-responsables">
              <div class="flex flex-wrap gap-2 p-2.5 border border-slate-200 dark:border-slate-700/80 rounded-xl bg-slate-50/70 dark:bg-slate-900/40 max-h-[120px] overflow-y-auto w-full">
                <div v-for="t in teachers" :key="t.iri || t" class="flex items-center gap-1.5">
                  <input
                    type="checkbox"
                    :id="'coresp_' + (t.iri || t)"
                    :value="t.iri || t"
                    v-model="localForm.coResponsablesIris"
                    class="q-check !w-3.5 !h-3.5"
                  />
                  <label :for="'coresp_' + (t.iri || t)" class="text-xs text-slate-700 dark:text-slate-300 mr-2 cursor-pointer">
                    {{ (t.fullName || t).split(' ').slice(1).join(' ') }}
                  </label>
                </div>
              </div>
            </FormField>
          </div>
        </div>

        <!-- SECTION 2: INTERRUPTIONS & SOUTENANCES -->
        <div v-if="showCreateTab === 'interruptions'" class="space-y-6">

          <!-- Interruptions 0-n -->
          <div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-900/30 space-y-3">
            <div class="flex justify-between items-center">
              <h4 class="text-xs font-bold text-slate-800 dark:text-slate-100 uppercase tracking-wider">
                Périodes d'interruptions ({{ localForm.interruptions?.length || 0 }})
              </h4>
              <Button
                type="button"
                size="small"
                severity="secondary"
                outlined
                icon="pi pi-plus"
                label="Ajouter interruption"
                @click="addInterruptionRow"
              />
            </div>

            <div class="space-y-3" v-if="localForm.interruptions?.length > 0">
              <div
                v-for="(item, idx) in localForm.interruptions"
                :key="idx"
                class="flex flex-wrap items-center gap-3 bg-white dark:bg-slate-800 p-3 border border-slate-200 dark:border-slate-700/80 rounded-xl shadow-2xs"
              >
                <div class="flex flex-col gap-1 w-[130px]">
                  <label class="text-2xs font-semibold text-slate-500">Date début</label>
                  <input type="date" v-model="item.dateDebut" class="q-input q-input-sm" />
                </div>
                <div class="flex flex-col gap-1 w-[130px]">
                  <label class="text-2xs font-semibold text-slate-500">Date fin</label>
                  <input type="date" v-model="item.dateFin" class="q-input q-input-sm" />
                </div>
                <div class="flex flex-col gap-1 flex-1 min-w-[200px]">
                  <label class="text-2xs font-semibold text-slate-500">Motif</label>
                  <input type="text" v-model="item.motif" placeholder="Ex: Vacances de Noël, etc." class="q-input q-input-sm" />
                </div>
                <Button
                  type="button"
                  severity="danger"
                  text
                  rounded
                  size="small"
                  icon="pi pi-trash"
                  aria-label="Supprimer"
                  class="self-end"
                  @click="removeInterruptionRow(idx)"
                />
              </div>
            </div>
            <div
              v-else
              class="text-center py-6 border border-dashed border-slate-200 dark:border-slate-700 rounded-xl text-slate-400 text-xs"
            >
              Aucune période d'interruption déclarée.
            </div>
          </div>

          <!-- Soutenances -->
          <div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-900/30 space-y-3">
            <div class="flex justify-between items-center">
              <h4 class="text-xs font-bold text-slate-800 dark:text-slate-100 uppercase tracking-wider">
                Périodes de Soutenances ({{ localForm.soutenances?.length || 0 }})
              </h4>
              <Button
                type="button"
                size="small"
                severity="secondary"
                outlined
                icon="pi pi-plus"
                label="Ajouter soutenance"
                @click="addSoutenanceRow"
              />
            </div>

            <div class="space-y-3" v-if="localForm.soutenances?.length > 0">
              <div
                v-for="(item, idx) in localForm.soutenances"
                :key="idx"
                class="grid grid-cols-1 md:grid-cols-4 gap-3 bg-white dark:bg-slate-800 p-4 border border-slate-200 dark:border-slate-700/80 rounded-xl shadow-2xs"
              >
                <div class="flex flex-col gap-1">
                  <label class="text-2xs font-semibold text-slate-500">Début soutenances</label>
                  <input type="date" v-model="item.dateDebut" class="q-input q-input-sm" />
                </div>
                <div class="flex flex-col gap-1">
                  <label class="text-2xs font-semibold text-slate-500">Fin soutenances</label>
                  <input type="date" v-model="item.dateFin" class="q-input q-input-sm" />
                </div>
                <div class="flex flex-col gap-1 md:col-span-2">
                  <label class="text-2xs font-semibold text-slate-500">Rendu du rapport</label>
                  <input type="date" v-model="item.dateRenduRapport" class="q-input q-input-sm" />
                </div>
                <div class="flex flex-col gap-1 md:col-span-4">
                  <label class="text-2xs font-semibold text-slate-500">Modalités d'évaluation (Texte)</label>
                  <textarea rows="2" v-model="item.modalites" placeholder="Ex: Modalités de présentation, jury..." class="q-input q-input-sm"></textarea>
                </div>
                <div class="md:col-span-4 flex justify-end">
                  <Button
                    type="button"
                    severity="danger"
                    text
                    size="small"
                    icon="pi pi-trash"
                    label="Supprimer cette soutenance"
                    @click="removeSoutenanceRow(idx)"
                  />
                </div>
              </div>
            </div>
            <div
              v-else
              class="text-center py-6 border border-dashed border-slate-200 dark:border-slate-700 rounded-xl text-slate-400 text-xs"
            >
              Aucune période de soutenance déclarée.
            </div>
          </div>

        </div>

        <!-- SECTION 3: CONVENTION TEXT FIELDS -->
        <div v-if="showCreateTab === 'convention'" class="space-y-4">
          <ValidatedInput
            v-model="localForm.commentaireLibre"
            name="commentaireLibre"
            label="Commentaire libre"
            type="textarea"
            placeholder="Saisissez des commentaires généraux pour alimenter la convention..."
          />

          <ValidatedInput
            v-model="localForm.competencesVisees"
            name="competencesVisees"
            label="Compétences visées"
            type="textarea"
            placeholder="Compétences techniques et comportementales à acquérir..."
          />

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <ValidatedInput
              v-model="localForm.evalEntreprise"
              name="evalEntreprise"
              label="Modalités d'évaluation entreprise"
              type="textarea"
              placeholder="Comment l'entreprise évalue l'étudiant (grille, rapport de tuteur)..."
            />

            <ValidatedInput
              v-model="localForm.evalPedagogique"
              name="evalPedagogique"
              label="Modalités d'évaluations pédagogiques"
              type="textarea"
              placeholder="Mode de calcul de la note finale (rapport, soutenance, coefficients)..."
            />
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <ValidatedInput
              v-model="localForm.encadrement"
              name="encadrement"
              label="Modalités d'encadrement"
              type="textarea"
              placeholder="Visites, bilans téléphoniques, livret d'apprentissage..."
            />

            <ValidatedInput
              v-model="localForm.documentsRendre"
              name="documentsRendre"
              label="Documents à rendre"
              type="textarea"
              placeholder="Rapports, synthèses d'activité, certificats..."
            />
          </div>
        </div>

        <!-- SECTION 4: INSTRUCTIONS FILES UPLOADS -->
        <div v-if="showCreateTab === 'files'" class="space-y-4">
          <div
            class="border-2 border-dashed border-slate-200 dark:border-slate-700 hover:border-teal-400 dark:hover:border-teal-500 rounded-2xl p-6 text-center cursor-pointer hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-all duration-300"
            @click="triggerConsigneUpload">
            <div
              class="w-10 h-10 bg-teal-50 dark:bg-teal-500/10 rounded-full flex items-center justify-center text-teal-600 mx-auto">
              <ArrowUpTrayIcon class="w-5 h-5" />
            </div>
            <h4 class="text-xs font-bold text-slate-800 dark:text-slate-100 mt-2">Cliquez pour ajouter une consigne</h4>
            <p class="text-2xs text-slate-400 mt-0.5">Documents pdf d'aide, guides, chartes de stage...</p>
          </div>

          <div class="space-y-2 mt-4" v-if="localForm.consignesFichiers?.length > 0">
            <h5 class="text-2xs font-bold text-slate-400 uppercase tracking-wider">
              Fichiers téléversés ({{ localForm.consignesFichiers.length }})
            </h5>

            <div
              v-for="(f, idx) in localForm.consignesFichiers"
              :key="idx"
              class="flex items-center justify-between p-3 border border-slate-200 dark:border-slate-700/80 rounded-xl bg-white dark:bg-slate-800 shadow-2xs"
            >
              <span class="font-semibold text-xs text-slate-700 dark:text-slate-300 flex items-center gap-2">
                <DocumentIcon class="w-4 h-4 text-teal-500" />
                {{ f.name }} <span class="text-2xs text-slate-400">({{ f.size }})</span>
              </span>
              <Button
                type="button"
                severity="danger"
                text
                size="small"
                label="Supprimer"
                @click="removeConsigneFile(idx)"
              />
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Footer Buttons -->
    <template #footer>
      <div class="flex items-center justify-end gap-3 pt-3">
        <Button
          label="Annuler"
          severity="secondary"
          text
          @click="closeDialog"
        />
        <Button
          :label="period ? 'Enregistrer les modifications' : 'Créer la période'"
          :icon="period ? 'pi pi-check' : 'pi pi-plus'"
          severity="primary"
          @click="handleSave"
        />
      </div>
    </template>
  </Dialog>
</template>
