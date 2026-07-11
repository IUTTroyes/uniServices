<script setup>
import { ref, watch, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import { ValidatedInput, validationRules } from '@components';
import { PlusIcon, TrashIcon, ArrowUpTrayIcon, DocumentIcon, CheckIcon } from '@heroicons/vue/24/outline';

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
    @update:visible="closeDialog"
    modal 
    :header="period ? 'Paramétrer / Éditer la période de stage / alternance' : 'Créer une nouvelle période de stage / alternance'"
    :style="{ width: '90vw', maxWidth: '800px' }" 
    class="text-xs dark:bg-slate-800 dark:text-slate-200"
  >
    <!-- Subtabs within the dialog to organize options -->
    <div class="flex gap-2 border-b border-slate-100 dark:border-slate-700 pb-3 mb-4">
      <button @click="showCreateTab = 'general'"
        :class="['px-3 py-1.5 rounded-lg text-xs font-bold transition-all', showCreateTab === 'general' ? 'bg-violet-100 text-violet-700 dark:bg-violet-950/40 dark:text-violet-400' : 'text-slate-500']">
        Général & Dates
      </button>
      <button @click="showCreateTab = 'interruptions'"
        :class="['px-3 py-1.5 rounded-lg text-xs font-bold transition-all', showCreateTab === 'interruptions' ? 'bg-violet-100 text-violet-700 dark:bg-violet-950/40 dark:text-violet-400' : 'text-slate-500']">
        Interruptions & Soutenances
      </button>
      <button @click="showCreateTab = 'convention'"
        :class="['px-3 py-1.5 rounded-lg text-xs font-bold transition-all', showCreateTab === 'convention' ? 'bg-violet-100 text-violet-700 dark:bg-violet-950/40 dark:text-violet-400' : 'text-slate-500']">
        Modèles & Convention
      </button>
      <button @click="showCreateTab = 'files'"
        :class="['px-3 py-1.5 rounded-lg text-xs font-bold transition-all', showCreateTab === 'files' ? 'bg-violet-100 text-violet-700 dark:bg-violet-950/40 dark:text-violet-400' : 'text-slate-500']">
        Documents Consignes
      </button>
    </div>

    <div class="py-2 space-y-4 max-h-[60vh] overflow-y-auto pr-2">

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
            label="Durée minimale obligatoire (Semaines)" 
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
            <input type="checkbox" id="datesFlex" v-model="localForm.datesFlexibles"
              class="w-4 h-4 text-violet-600 rounded focus:ring-violet-500" />
            <label for="datesFlex"
              class="text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer">Autoriser
              des dates flexibles pour l'étudiant</label>
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

          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-bold text-slate-500">Co-responsables</label>
            <div
              class="flex flex-wrap gap-2 p-2 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 max-h-[120px] overflow-y-auto w-full">
              <div v-for="t in teachers" :key="t.iri || t" class="flex items-center gap-1.5">
                <input type="checkbox" :id="'coresp_' + (t.iri || t)" :value="t.iri || t" v-model="localForm.coResponsablesIris"
                  class="w-3.5 h-3.5 text-violet-600 rounded" />
                <label :for="'coresp_' + (t.iri || t)"
                  class="text-[10px] text-slate-700 dark:text-slate-300 mr-2 cursor-pointer">{{
                    (t.fullName || t).split(' ').slice(1).join(' ') }}</label>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- SECTION 2: INTERRUPTIONS & SOUTENANCES -->
      <div v-if="showCreateTab === 'interruptions'" class="space-y-6">

        <!-- Interruptions 0-n -->
        <div class="space-y-3">
          <div class="flex justify-between items-center">
            <h4 class="text-xs font-bold text-slate-800 dark:text-slate-100 uppercase tracking-wider">Périodes
              d'interruptions ({{ localForm.interruptions?.length || 0 }})</h4>
            <button type="button" @click="addInterruptionRow"
              class="px-2.5 py-1.5 bg-slate-50 hover:bg-slate-100 dark:bg-slate-700 dark:hover:bg-slate-600 text-[10px] font-bold text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-600 rounded-lg flex items-center gap-1 transition-all cursor-pointer">
              <PlusIcon class="w-3 h-3" />
              <span>Ajouter interruption</span>
            </button>
          </div>

          <div class="space-y-3" v-if="localForm.interruptions?.length > 0">
            <div v-for="(item, idx) in localForm.interruptions" :key="idx"
              class="flex flex-wrap items-center gap-3 bg-slate-50 dark:bg-slate-900/40 p-3 border border-slate-100 dark:border-slate-800 rounded-xl">
              <div class="flex flex-col gap-1 w-[120px]">
                <label class="text-[9px] text-slate-400">Date début</label>
                <input type="date" v-model="item.dateDebut"
                  class="p-1 border border-slate-200 dark:border-slate-700 rounded bg-white dark:bg-slate-900 text-[10px]" />
              </div>
              <div class="flex flex-col gap-1 w-[120px]">
                <label class="text-[9px] text-slate-400">Date fin</label>
                <input type="date" v-model="item.dateFin"
                  class="p-1 border border-slate-200 dark:border-slate-700 rounded bg-white dark:bg-slate-900 text-[10px]" />
              </div>
              <div class="flex flex-col gap-1 flex-1 min-w-[200px]">
                <label class="text-[9px] text-slate-400">Motif</label>
                <input type="text" v-model="item.motif" placeholder="Ex: Vacances de Noël, etc."
                  class="p-1.5 border border-slate-200 dark:border-slate-700 rounded bg-white dark:bg-slate-900 text-[10px]" />
              </div>
              <button type="button" @click="removeInterruptionRow(idx)"
                class="text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/20 p-2 rounded-lg mt-4 self-end cursor-pointer">
                <TrashIcon class="w-4 h-4" />
              </button>
            </div>
          </div>
          <div v-else
            class="text-center py-4 bg-slate-50 dark:bg-slate-900/20 border border-dashed border-slate-200 dark:border-slate-700 rounded-xl text-slate-400 text-[10px]">
            Aucune période d'interruption déclarée.
          </div>
        </div>

        <!-- Soutenances -->
        <div class="space-y-3 pt-4 border-t border-slate-100 dark:border-slate-700">
          <div class="flex justify-between items-center">
            <h4 class="text-xs font-bold text-slate-800 dark:text-slate-100 uppercase tracking-wider">Périodes de
              Soutenances ({{ localForm.soutenances?.length || 0 }})</h4>
            <button type="button" @click="addSoutenanceRow"
              class="px-2.5 py-1.5 bg-slate-50 hover:bg-slate-100 dark:bg-slate-700 dark:hover:bg-slate-600 text-[10px] font-bold text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-600 rounded-lg flex items-center gap-1 transition-all cursor-pointer">
              <PlusIcon class="w-3 h-3" />
              <span>Ajouter soutenance</span>
            </button>
          </div>

          <div class="space-y-3" v-if="localForm.soutenances?.length > 0">
            <div v-for="(item, idx) in localForm.soutenances" :key="idx"
              class="grid grid-cols-1 md:grid-cols-4 gap-3 bg-slate-50 dark:bg-slate-900/40 p-4 border border-slate-100 dark:border-slate-800 rounded-xl">
              <div class="flex flex-col gap-1">
                <label class="text-[9px] text-slate-400">Début soutenances</label>
                <input type="date" v-model="item.dateDebut"
                  class="p-1 border border-slate-200 dark:border-slate-700 rounded bg-white dark:bg-slate-900 text-[10px]" />
              </div>
              <div class="flex flex-col gap-1">
                <label class="text-[9px] text-slate-400">Fin soutenances</label>
                <input type="date" v-model="item.dateFin"
                  class="p-1 border border-slate-200 dark:border-slate-700 rounded bg-white dark:bg-slate-900 text-[10px]" />
              </div>
              <div class="flex flex-col gap-1">
                <label class="text-[9px] text-slate-400">Rendu du rapport</label>
                <input type="date" v-model="item.dateRenduRapport"
                  class="p-1 border border-slate-200 dark:border-slate-700 rounded bg-white dark:bg-slate-900 text-[10px]" />
              </div>
              <div class="flex flex-col gap-1 md:col-span-4">
                <label class="text-[9px] text-slate-400">Modalités d'évaluation (Texte)</label>
                <textarea rows="2" v-model="item.modalites" placeholder="Ex: Modalités de présentation, jury..."
                  class="p-2 border border-slate-200 dark:border-slate-700 rounded bg-white dark:bg-slate-900 text-[10px]"></textarea>
              </div>
              <button type="button" @click="removeSoutenanceRow(idx)"
                class="text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/20 p-2 rounded-lg justify-self-end mt-2 md:col-span-4 cursor-pointer">
                <TrashIcon class="w-4 h-4 text-rose-600 inline" />
                <span class="text-[10px] font-bold ml-1">Supprimer cette soutenance</span>
              </button>
            </div>
          </div>
          <div v-else
            class="text-center py-4 bg-slate-50 dark:bg-slate-900/20 border border-dashed border-slate-200 dark:border-slate-700 rounded-xl text-slate-400 text-[10px]">
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
          class="border-2 border-dashed border-slate-200 dark:border-slate-700 hover:border-violet-400 dark:hover:border-violet-500 rounded-2xl p-6 text-center cursor-pointer hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-all duration-300"
          @click="triggerConsigneUpload">
          <div
            class="w-10 h-10 bg-violet-50 dark:bg-violet-500/10 rounded-full flex items-center justify-center text-violet-600 mx-auto">
            <ArrowUpTrayIcon class="w-5 h-5" />
          </div>
          <h4 class="text-xs font-bold text-slate-800 dark:text-slate-100 mt-2">Cliquez pour ajouter une consigne</h4>
          <p class="text-[10px] text-slate-400 mt-0.5">Documents pdf d'aide, guides, chartes de stage...</p>
        </div>

        <div class="space-y-2 mt-4" v-if="localForm.consignesFichiers?.length > 0">
          <h5 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Fichiers téléversés ({{
            localForm.consignesFichiers.length }})</h5>

          <div v-for="(f, idx) in localForm.consignesFichiers" :key="idx"
            class="flex items-center justify-between p-3 border border-slate-100 dark:border-slate-800 rounded-xl bg-slate-50/50 dark:bg-slate-900/20">
            <span class="font-bold text-slate-700 dark:text-slate-300 flex items-center gap-2">
              <DocumentIcon class="w-4 h-4 text-rose-500" />
              {{ f.name }} <span class="text-[9px] text-slate-400">({{ f.size }})</span>
            </span>
            <button type="button" @click="removeConsigneFile(idx)"
              class="text-xs text-rose-600 hover:text-rose-700 font-bold">
              Supprimer
            </button>
          </div>
        </div>
      </div>

    </div>

    <!-- Footer Buttons -->
    <div class="flex items-center justify-between border-t border-slate-100 dark:border-slate-700 pt-4 mt-6">
      <div></div> <!-- Spacer -->
      <button @click="handleSave"
        class="py-3 px-6 bg-violet-600 hover:bg-violet-700 text-white font-bold rounded-xl text-xs transition-all flex items-center justify-center gap-2 border-0 cursor-pointer">
        <CheckIcon v-if="period" class="w-3.5 h-3.5" />
        <PlusIcon v-else class="w-3.5 h-3.5" />
        <span>{{ period ? 'Enregistrer les modifications' : 'Créer la période' }}</span>
      </button>
    </div>
  </Dialog>
</template>
