<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { HeaderComponent, EmptyState, Alert } from '@components';
import { useMessageStore } from '../stores/messageStore';
import messageService from '../requests/messageService';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import SelectButton from 'primevue/selectbutton';
import MultiSelect from 'primevue/multiselect';
import RadioButton from 'primevue/radiobutton';
import ToggleSwitch from 'primevue/toggleswitch';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';

const route = useRoute();
const router = useRouter();
const messageStore = useMessageStore();

// Niveaux de destinataires principaux
const targetType = ref('SEMESTRES');
const targetTypeOptions = [
  { label: 'Semestres complets', value: 'SEMESTRES', icon: 'pi pi-calendar' },
  { label: 'Groupes (CM / TD / TP)', value: 'GROUPES', icon: 'pi pi-users' },
  { label: 'Étudiants', value: 'ETUDIANTS', icon: 'pi pi-user' },
  { label: 'Enseignants', value: 'ENSEIGNANTS', icon: 'pi pi-briefcase' },
];

// Sous-options enseignants
const enseignantsType = ref('ALL');
const enseignantsTypeOptions = [
  { label: 'Tous les enseignants', value: 'ALL', desc: 'Ensemble du corps professoral du département' },
  { label: 'Permanents uniquement', value: 'PERMANENT', desc: 'MCF, PU, PRAG, PRCE, contractuels...' },
  { label: 'Vacataires uniquement', value: 'VACATAIRE', desc: 'Chargés d\'enseignement vacataires, ATER, pros...' },
  { label: 'Choisir nominativement', value: 'NOMINATIF', desc: 'Sélectionner des enseignants précis' },
];

// Sélections
const selectedSemestres = ref([]);
const selectedGroupes = ref([]);
const selectedEtudiants = ref([]);
const selectedEnseignants = ref([]);
const selectedCopiePersonnels = ref([]);

// Formulaire message
const sujet = ref('');
const contenu = ref('');
const prioritaire = ref(false);
const copieEmail = ref(true);

// Pièces jointes
const attachedFiles = ref([]);
const fileInputRef = ref(null);

// Modal de confirmation
const showConfirmDialog = ref(false);

// Données distantes
const departement = ref(null);
const semestresList = ref([]);
const groupesList = ref([]);
const etudiantsList = ref([]);
const enseignantsList = ref([]);
const personnelsList = ref([]);

const isLoadingTargets = ref(true);
const isSending = ref(false);
const errorMessage = ref('');

onMounted(async () => {
  try {
    const res = await messageService.getTargets();
    departement.value = res.data?.departement ?? null;
    semestresList.value = res.data?.semestres ?? [];
    groupesList.value = res.data?.groupes ?? [];
    etudiantsList.value = res.data?.etudiants ?? [];
    enseignantsList.value = res.data?.enseignants ?? [];
    personnelsList.value = res.data?.personnels ?? [];

    // Présélection par défaut : premier semestre si disponible
    if (semestresList.value.length > 0 && selectedSemestres.value.length === 0) {
      selectedSemestres.value = [semestresList.value[0]];
    }

    // Gestion de la duplication d'un message existant (?duplicate=123)
    const duplicateId = route.query.duplicate;
    if (duplicateId) {
      try {
        const dupRes = await messageService.getMessage(duplicateId);
        if (dupRes.data) {
          const d = dupRes.data;
          sujet.value = d.sujet?.startsWith('Copie : ') ? d.sujet : `Copie : ${d.sujet || ''}`;
          contenu.value = d.contenu || '';
          prioritaire.value = !!d.prioritaire;
          copieEmail.value = !!d.copieEmail;
          if (d.typeCible && ['SEMESTRES', 'GROUPES', 'ETUDIANTS', 'ENSEIGNANTS'].includes(d.typeCible)) {
            targetType.value = d.typeCible;
          }
        }
      } catch (e) {
        console.warn('Impossible de charger le message à dupliquer:', e);
      }
    }
  } catch (err) {
    console.error('Erreur chargement cibles:', err);
    errorMessage.value = 'Impossible de charger la liste des destinataires. Vérifiez votre connexion.';
  } finally {
    isLoadingTargets.value = false;
  }
});

// Gestion des pièces jointes
const triggerFileInput = () => {
  fileInputRef.value?.click();
};

const handleFilesSelected = (event) => {
  const files = Array.from(event.target.files || []);
  addFiles(files);
  event.target.value = '';
};

const handleFilesDropped = (event) => {
  const files = Array.from(event.dataTransfer?.files || []);
  addFiles(files);
};

const addFiles = (files) => {
  const maxFileSize = 10 * 1024 * 1024; // 10 Mo par fichier
  for (const file of files) {
    if (file.size > maxFileSize) {
      errorMessage.value = `Le fichier "${file.name}" dépasse 10 Mo.`;
      continue;
    }
    if (!attachedFiles.value.some(f => f.name === file.name && f.size === file.size)) {
      attachedFiles.value.push(file);
    }
  }
};

const removeAttachedFile = (index) => {
  attachedFiles.value.splice(index, 1);
};

const formatFileSize = (bytes) => {
  if (!bytes) return '0 B';
  const k = 1024;
  const sizes = ['B', 'Ko', 'Mo', 'Go'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};

const getFileIcon = (mimeType, filename) => {
  const f = (filename || '').toLowerCase();
  const m = (mimeType || '').toLowerCase();
  if (m.includes('pdf') || f.endsWith('.pdf')) return 'pi pi-file-pdf text-red-500';
  if (m.includes('word') || f.match(/\.(doc|docx)$/)) return 'pi pi-file-word text-blue-500';
  if (m.includes('excel') || m.includes('spreadsheet') || f.match(/\.(xls|xlsx|csv)$/)) return 'pi pi-file-excel text-emerald-500';
  if (m.includes('image') || f.match(/\.(jpg|jpeg|png|gif|webp)$/)) return 'pi pi-image text-purple-500';
  if (m.includes('zip') || f.match(/\.(zip|tar|gz|rar)$/)) return 'pi pi-folder text-amber-500';
  return 'pi pi-file text-surface-500';
};

// Helpers groupes
const selectAllGroupesByType = (type) => {
  const matches = groupesList.value.filter(g => g.type === type);
  const currentIds = new Set(selectedGroupes.value.map(g => g.id));
  const newOnes = matches.filter(g => !currentIds.has(g.id));
  selectedGroupes.value = [...selectedGroupes.value, ...newOnes];
};

const selectAllSemestres = () => {
  selectedSemestres.value = [...semestresList.value];
};

const clearSemestres = () => {
  selectedSemestres.value = [];
};

const clearGroupes = () => {
  selectedGroupes.value = [];
};

// Style de badge selon le type de groupe
const getGroupeTypeBadgeClass = (type) => {
  switch (type) {
    case 'CM':
      return 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300 border-purple-200 dark:border-purple-800';
    case 'TD':
      return 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 border-blue-200 dark:border-blue-800';
    case 'TP':
      return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800';
    default:
      return 'bg-surface-100 text-surface-800 dark:bg-surface-800 dark:text-surface-200';
  }
};

// Estimation du nombre de destinataires principaux
const primaryDestinatairesSummary = computed(() => {
  if (targetType.value === 'SEMESTRES') {
    const count = selectedSemestres.value.length;
    if (count === 0) return 'Aucun semestre sélectionné';
    return `${count} semestre(s) sélectionné(s)`;
  }
  if (targetType.value === 'GROUPES') {
    const count = selectedGroupes.value.length;
    if (count === 0) return 'Aucun groupe sélectionné';
    return `${count} groupe(s) sélectionné(s)`;
  }
  if (targetType.value === 'ETUDIANTS') {
    const count = selectedEtudiants.value.length;
    if (count === 0) return 'Aucun étudiant sélectionné';
    return `${count} étudiant(s) sélectionné(s)`;
  }
  if (targetType.value === 'ENSEIGNANTS') {
    if (enseignantsType.value === 'ALL') {
      return `${enseignantsList.value.length} enseignant(s) au total`;
    }
    if (enseignantsType.value === 'PERMANENT') {
      const pCount = enseignantsList.value.filter(e => e.categorie === 'PERMANENT').length;
      return `${pCount} enseignant(s) permanent(s)`;
    }
    if (enseignantsType.value === 'VACATAIRE') {
      const vCount = enseignantsList.value.filter(e => e.categorie === 'VACATAIRE').length;
      return `${vCount} enseignant(s) vacataire(s)`;
    }
    if (enseignantsType.value === 'NOMINATIF') {
      return `${selectedEnseignants.value.length} enseignant(s) sélectionné(s)`;
    }
  }
  return '';
});

const openConfirmDialog = () => {
  errorMessage.value = '';

  if (!sujet.value.trim() || !contenu.value.trim()) {
    errorMessage.value = 'Veuillez saisir un objet et un contenu pour le message.';
    return;
  }

  if (targetType.value === 'SEMESTRES' && selectedSemestres.value.length === 0) {
    errorMessage.value = 'Veuillez sélectionner au moins un semestre complet.';
    return;
  }

  if (targetType.value === 'GROUPES' && selectedGroupes.value.length === 0) {
    errorMessage.value = 'Veuillez sélectionner au moins un groupe (CM, TD ou TP).';
    return;
  }

  if (targetType.value === 'ETUDIANTS' && selectedEtudiants.value.length === 0) {
    errorMessage.value = 'Veuillez sélectionner au moins un étudiant.';
    return;
  }

  if (targetType.value === 'ENSEIGNANTS' && enseignantsType.value === 'NOMINATIF' && selectedEnseignants.value.length === 0) {
    errorMessage.value = 'Veuillez sélectionner au moins un enseignant nominativement.';
    return;
  }

  showConfirmDialog.value = true;
};

const submitMessage = async () => {
  isSending.value = true;
  errorMessage.value = '';
  try {
    const formData = new FormData();
    formData.append('sujet', sujet.value.trim());
    formData.append('contenu', contenu.value.trim());
    formData.append('destinataireType', targetType.value);
    formData.append('prioritaire', prioritaire.value ? '1' : '0');
    formData.append('copieEmail', copieEmail.value ? '1' : '0');

    const semestreIds = targetType.value === 'SEMESTRES' ? selectedSemestres.value.map(s => s.id) : [];
    const groupeIds = targetType.value === 'GROUPES' ? selectedGroupes.value.map(g => g.id) : [];
    const etudiantIds = targetType.value === 'ETUDIANTS' ? selectedEtudiants.value.map(e => e.id) : [];
    const enseignantIds = (targetType.value === 'ENSEIGNANTS' && enseignantsType.value === 'NOMINATIF')
      ? selectedEnseignants.value.map(e => e.id)
      : [];
    const copiePersonnelIds = selectedCopiePersonnels.value.map(p => p.id);

    formData.append('semestreIds', JSON.stringify(semestreIds));
    formData.append('groupeIds', JSON.stringify(groupeIds));
    formData.append('etudiantIds', JSON.stringify(etudiantIds));
    formData.append('enseignantIds', JSON.stringify(enseignantIds));
    formData.append('copiePersonnelIds', JSON.stringify(copiePersonnelIds));
    if (targetType.value === 'ENSEIGNANTS') {
      formData.append('enseignantsType', enseignantsType.value);
    }

    for (const file of attachedFiles.value) {
      formData.append('files[]', file);
    }

    await messageStore.sendMessage(formData);
    showConfirmDialog.value = false;
    router.push('/message/envoyes');
  } catch (err) {
    errorMessage.value = err.response?.data?.message || "Erreur lors de l'envoi du message.";
    showConfirmDialog.value = false;
  } finally {
    isSending.value = false;
  }
};
</script>

<template>
  <HeaderComponent
    icon="pi pi-pencil"
    titre="Nouveau message"
    description="Rédigez et diffusez un message interne officiel vers des semestres, groupes, étudiants ou enseignants."
    :show-back="true"
    back-url="/message"
    color="teal"
  />

  <div class="card p-6 mx-auto space-y-6">
    <Alert
      v-if="errorMessage"
      severity="error"
      :message="errorMessage"
      @close="errorMessage = ''"
    />

    <form @submit.prevent="openConfirmDialog" class="space-y-6">
      <!-- 1. SÉLECTION DES DESTINATAIRES PRINCIPAUX -->
      <div class="p-5 rounded-xl border border-surface-200 dark:border-surface-700 bg-surface-50/50 dark:bg-surface-900/50 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
          <div>
            <h3 class="text-base font-semibold text-surface-900 dark:text-surface-50 flex items-center gap-2">
              <i class="pi pi-send text-teal-600 dark:text-teal-400" />
              Destinataires principaux
            </h3>
            <p class="text-xs text-surface-500">
              Choisissez le niveau de diffusion pour votre communication
              <span v-if="departement" class="font-medium">({{ departement.libelle }})</span>.
            </p>
          </div>
          <Tag :value="primaryDestinatairesSummary" severity="info" class="self-start sm:self-auto" />
        </div>

        <!-- Onglets / Boutons du niveau de destinataires -->
        <SelectButton
          v-model="targetType"
          :options="targetTypeOptions"
          option-label="label"
          option-value="value"
          class="w-full flex-wrap"
          :allow-empty="false"
        >
          <template #option="slotProps">
            <div class="flex items-center gap-2 py-1 px-2">
              <i :class="slotProps.option.icon" class="text-sm" />
              <span class="text-xs sm:text-sm font-medium">{{ slotProps.option.label }}</span>
            </div>
          </template>
        </SelectButton>

        <!-- NIVEAU 1 : SEMESTRES COMPLETS -->
        <div v-if="targetType === 'SEMESTRES'" class="space-y-2 pt-1">
          <EmptyState
            v-if="!isLoadingTargets && semestresList.length === 0"
            compact
            title="Aucun semestre actif"
            description="Aucun semestre actif n'a été trouvé pour votre département."
            icon="pi pi-calendar"
            color="amber"
          />
          <template v-else>
            <div class="flex items-center justify-between text-xs text-surface-600 dark:text-surface-400">
              <span>Sélectionnez un ou plusieurs semestres actifs :</span>
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  class="text-teal-600 dark:text-teal-400 hover:underline font-medium"
                  @click="selectAllSemestres"
                >
                  Tous
                </button>
                <span>•</span>
                <button
                  type="button"
                  class="text-surface-500 hover:underline"
                  @click="clearSemestres"
                >
                  Effacer
                </button>
              </div>
            </div>

            <MultiSelect
              v-model="selectedSemestres"
              :options="semestresList"
              option-label="libelle"
              placeholder="Choisir un ou plusieurs semestres..."
              display="chip"
              filter
              class="w-full"
              :loading="isLoadingTargets"
            >
              <template #option="slotProps">
                <div class="flex items-center justify-between w-full py-1">
                  <span class="font-medium text-sm">{{ slotProps.option.libelle }}</span>
                  <span class="text-xs text-surface-500 font-mono px-2 py-0.5 rounded bg-surface-100 dark:bg-surface-800">
                    {{ slotProps.option.code }}
                  </span>
                </div>
              </template>
            </MultiSelect>
            <p class="text-xs text-surface-500">
              Tous les étudiants inscrits dans les semestres sélectionnés recevront le message.
            </p>
          </template>
        </div>

        <!-- NIVEAU 2 : GROUPES (CM / TD / TP) -->
        <div v-else-if="targetType === 'GROUPES'" class="space-y-2 pt-1">
          <EmptyState
            v-if="!isLoadingTargets && groupesList.length === 0"
            compact
            title="Aucun groupe disponible"
            description="Aucun groupe de cours n'a été configuré sur les semestres actifs."
            icon="pi pi-users"
            color="amber"
          />
          <template v-else>
            <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-surface-600 dark:text-surface-400">
              <span>Sélectionnez un ou plusieurs groupes de cours :</span>
              <div class="flex flex-wrap items-center gap-2">
                <button
                  type="button"
                  class="px-2 py-0.5 rounded border border-purple-200 text-purple-700 dark:border-purple-800 dark:text-purple-300 hover:bg-purple-50 dark:hover:bg-purple-950/40 text-xs font-medium"
                  @click="selectAllGroupesByType('CM')"
                >
                  + Tous les CM
                </button>
                <button
                  type="button"
                  class="px-2 py-0.5 rounded border border-blue-200 text-blue-700 dark:border-blue-800 dark:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-950/40 text-xs font-medium"
                  @click="selectAllGroupesByType('TD')"
                >
                  + Tous les TD
                </button>
                <button
                  type="button"
                  class="px-2 py-0.5 rounded border border-emerald-200 text-emerald-700 dark:border-emerald-800 dark:text-emerald-300 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 text-xs font-medium"
                  @click="selectAllGroupesByType('TP')"
                >
                  + Tous les TP
                </button>
                <button
                  type="button"
                  class="text-surface-500 hover:underline ml-1"
                  @click="clearGroupes"
                >
                  Effacer
                </button>
              </div>
            </div>

            <MultiSelect
              v-model="selectedGroupes"
              :options="groupesList"
              option-label="libelle"
              placeholder="Choisir des groupes (CM, TD, TP)..."
              display="chip"
              filter
              class="w-full"
              :loading="isLoadingTargets"
            >
              <template #option="slotProps">
                <div class="flex items-center justify-between w-full py-1">
                  <div class="flex items-center gap-2">
                    <span
                      :class="['text-xs font-semibold px-2 py-0.5 rounded border', getGroupeTypeBadgeClass(slotProps.option.type)]"
                    >
                      {{ slotProps.option.type }}
                    </span>
                    <span class="font-medium text-sm">{{ slotProps.option.libelle }}</span>
                  </div>
                  <span class="text-xs text-surface-500">
                    {{ slotProps.option.semestres?.join(', ') }}
                  </span>
                </div>
              </template>
              <template #chip="slotProps">
                <div class="inline-flex items-center gap-1.5 py-0.5 px-2 rounded text-xs bg-surface-200 dark:bg-surface-700">
                  <span class="font-bold text-[10px] uppercase opacity-75">{{ slotProps.value.type }}</span>
                  <span>{{ slotProps.value.libelle }}</span>
                </div>
              </template>
            </MultiSelect>
            <p class="text-xs text-surface-500">
              Les étudiants appartenant à au moins l'un des groupes sélectionnés seront destinataires (sans doublons).
            </p>
          </template>
        </div>

        <!-- NIVEAU 3 : ÉTUDIANTS NOMINATIFS -->
        <div v-else-if="targetType === 'ETUDIANTS'" class="space-y-2 pt-1">
          <EmptyState
            v-if="!isLoadingTargets && etudiantsList.length === 0"
            compact
            title="Aucun étudiant trouvé"
            description="Aucun étudiant n'a de scolarité active dans ce département."
            icon="pi pi-user"
            color="amber"
          />
          <template v-else>
            <label class="block text-xs text-surface-600 dark:text-surface-400">
              Recherchez et sélectionnez des étudiants précis de votre département :
            </label>

            <MultiSelect
              v-model="selectedEtudiants"
              :options="etudiantsList"
              option-label="display"
              placeholder="Rechercher par nom, prénom ou numéro étudiant..."
              display="chip"
              filter
              filter-placeholder="Tapez pour filtrer..."
              class="w-full"
              :loading="isLoadingTargets"
            >
              <template #option="slotProps">
                <div class="flex items-center justify-between w-full py-1">
                  <div>
                    <span class="font-medium text-sm">{{ slotProps.option.nom }} {{ slotProps.option.prenom }}</span>
                    <span v-if="slotProps.option.numeroEtudiant" class="text-xs text-surface-500 ml-2 font-mono">
                      #{{ slotProps.option.numeroEtudiant }}
                    </span>
                  </div>
                  <Tag v-if="slotProps.option.semestre" :value="slotProps.option.semestre" severity="secondary" class="text-xs" />
                </div>
              </template>
            </MultiSelect>
            <p class="text-xs text-surface-500">
              Idéal pour convoquer individuellement des étudiants ou faire un rappel ciblé.
            </p>
          </template>
        </div>

        <!-- NIVEAU 4 : ENSEIGNANTS -->
        <div v-else-if="targetType === 'ENSEIGNANTS'" class="space-y-3 pt-1">
          <EmptyState
            v-if="!isLoadingTargets && enseignantsList.length === 0"
            compact
            title="Aucun enseignant trouvé"
            description="Aucun personnel enseignant n'est rattaché à votre département."
            icon="pi pi-briefcase"
            color="amber"
          />
          <template v-else>
            <label class="block text-xs font-semibold text-surface-700 dark:text-surface-300">
              Catégorie d'enseignants ciblée :
            </label>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
              <div
                v-for="opt in enseignantsTypeOptions"
                :key="opt.value"
                class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-colors"
                :class="[
                  enseignantsType === opt.value
                    ? 'border-teal-500 bg-teal-50/60 dark:bg-teal-950/30'
                    : 'border-surface-200 dark:border-surface-700 hover:bg-surface-100/50 dark:hover:bg-surface-800/50'
                ]"
                @click="enseignantsType = opt.value"
              >
                <RadioButton
                  v-model="enseignantsType"
                  :input-id="'ens-' + opt.value"
                  :value="opt.value"
                  class="mt-0.5"
                />
                <label :for="'ens-' + opt.value" class="cursor-pointer text-sm">
                  <span class="font-semibold block text-surface-900 dark:text-surface-50">{{ opt.label }}</span>
                  <span class="text-xs text-surface-500">{{ opt.desc }}</span>
                </label>
              </div>
            </div>

            <!-- Si nominatif : MultiSelect des enseignants -->
            <div v-if="enseignantsType === 'NOMINATIF'" class="pt-2 space-y-1">
              <label class="block text-xs text-surface-600 dark:text-surface-400">
                Sélectionnez les enseignants destinataires :
              </label>
              <MultiSelect
                v-model="selectedEnseignants"
                :options="enseignantsList"
                option-label="display"
                placeholder="Sélectionner des enseignants..."
                display="chip"
                filter
                class="w-full"
                :loading="isLoadingTargets"
              >
                <template #option="slotProps">
                  <div class="flex items-center justify-between w-full py-1">
                    <div>
                      <span class="font-medium text-sm">{{ slotProps.option.nom }} {{ slotProps.option.prenom }}</span>
                      <span class="text-xs text-surface-500 ml-2">({{ slotProps.option.email }})</span>
                    </div>
                    <Tag
                      :value="slotProps.option.statutLibelle || slotProps.option.statut"
                      :severity="slotProps.option.categorie === 'PERMANENT' ? 'success' : 'info'"
                      class="text-xs"
                    />
                  </div>
                </template>
              </MultiSelect>
            </div>
          </template>
        </div>
      </div>

      <!-- 2. MISE EN COPIE (CC) : MEMBRES DU PERSONNEL -->
      <div class="p-4 rounded-xl border border-surface-200 dark:border-surface-700 bg-surface-50/30 dark:bg-surface-900/30 space-y-2">
        <div class="flex items-center justify-between">
          <label class="text-sm font-semibold text-surface-900 dark:text-surface-50 flex items-center gap-2">
            <i class="pi pi-copy text-surface-500" />
            En copie (CC) : membres du personnel
          </label>
          <span v-if="selectedCopiePersonnels.length > 0" class="text-xs font-semibold text-teal-600 dark:text-teal-400">
            {{ selectedCopiePersonnels.length }} en copie
          </span>
        </div>
        <p class="text-xs text-surface-500">
          Optionnel : mettez en copie un ou plusieurs collègues (enseignants, secrétariat, direction, etc.).
        </p>

        <MultiSelect
          v-model="selectedCopiePersonnels"
          :options="personnelsList"
          option-label="display"
          placeholder="Ajouter des personnels en copie (CC)..."
          display="chip"
          filter
          filter-placeholder="Rechercher un personnel..."
          class="w-full"
          :loading="isLoadingTargets"
        >
          <template #option="slotProps">
            <div class="flex items-center justify-between w-full py-1">
              <div>
                <span class="font-medium text-sm">{{ slotProps.option.nom }} {{ slotProps.option.prenom }}</span>
                <span class="text-xs text-surface-500 ml-2">({{ slotProps.option.email }})</span>
              </div>
              <div class="flex items-center gap-1.5">
                <Tag
                  v-if="slotProps.option.isEnseignant"
                  value="Enseignant"
                  severity="success"
                  class="text-[10px]"
                />
                <Tag
                  v-else
                  value="Personnel"
                  severity="secondary"
                  class="text-[10px]"
                />
                <span class="text-xs text-surface-400 font-mono">{{ slotProps.option.statut || '' }}</span>
              </div>
            </div>
          </template>
        </MultiSelect>
      </div>

      <!-- 3. OBJET & CONTENU DU MESSAGE -->
      <div class="space-y-4">
        <div>
          <label for="sujet" class="block text-sm font-semibold mb-1 text-surface-900 dark:text-surface-50">
            Objet / Sujet du message <span class="text-red-500">*</span>
          </label>
          <InputText
            id="sujet"
            v-model="sujet"
            placeholder="Ex : Consignes pour le projet S2, Report de la séance de TD, Examen..."
            class="w-full"
            required
          />
        </div>

        <div>
          <label for="contenu" class="block text-sm font-semibold mb-1 text-surface-900 dark:text-surface-50">
            Contenu du message <span class="text-red-500">*</span>
          </label>
          <Textarea
            id="contenu"
            v-model="contenu"
            rows="8"
            placeholder="Rédigez votre message ici. Les destinataires pourront le consulter depuis leur espace uniServices..."
            class="w-full font-sans"
            required
          />
        </div>

        <!-- Pièces jointes -->
        <div>
          <label class="block text-sm font-semibold mb-1 text-surface-900 dark:text-surface-50">
            Pièces jointes (documents attachés)
          </label>
          <input
            ref="fileInputRef"
            type="file"
            multiple
            class="hidden"
            @change="handleFilesSelected"
          />

          <div
            class="border-2 border-dashed border-surface-300 dark:border-surface-700 hover:border-teal-500 rounded-xl p-4 text-center cursor-pointer transition-colors bg-surface-50/50 dark:bg-surface-900/30"
            @click="triggerFileInput"
            @dragover.prevent
            @drop.prevent="handleFilesDropped"
          >
            <i class="pi pi-upload text-2xl text-teal-600 mb-1" />
            <p class="text-xs font-semibold text-surface-800 dark:text-surface-200 m-0">
              Cliquez ou glissez-déposez des fichiers ici pour les joindre
            </p>
            <span class="text-[11px] text-surface-400">PDF, Word, Excel, images (max 10 Mo par fichier)</span>
          </div>

          <!-- Liste des fichiers sélectionnés -->
          <div v-if="attachedFiles.length > 0" class="mt-3 space-y-2">
            <div
              v-for="(f, idx) in attachedFiles"
              :key="idx"
              class="flex items-center justify-between p-2.5 rounded-lg border border-surface-200 dark:border-surface-700 bg-surface-50 dark:bg-surface-800/50 text-xs"
            >
              <div class="flex items-center gap-2 min-w-0">
                <i :class="getFileIcon(f.type, f.name)" class="text-base shrink-0" />
                <span class="font-medium truncate text-surface-800 dark:text-surface-200">{{ f.name }}</span>
                <span class="text-[10px] text-surface-400 shrink-0">({{ formatFileSize(f.size) }})</span>
              </div>
              <Button
                icon="pi pi-times"
                severity="secondary"
                text
                rounded
                size="small"
                title="Supprimer ce fichier"
                @click="removeAttachedFile(idx)"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- 4. OPTIONS D'ENVOI -->
      <div class="pt-3 border-t border-surface-200 dark:border-surface-800 space-y-3">
        <div class="flex items-center justify-between p-3 rounded-lg bg-surface-50 dark:bg-surface-900/50 border border-surface-200/60 dark:border-surface-800">
          <div>
            <span class="text-sm font-semibold block text-surface-900 dark:text-surface-50">
              Message prioritaire / urgent
            </span>
            <span class="text-xs text-surface-500">
              Affiche un badge d'urgence rouge distinctif dans la boîte de réception des destinataires.
            </span>
          </div>
          <ToggleSwitch v-model="prioritaire" />
        </div>

        <div class="flex items-center justify-between p-3 rounded-lg bg-surface-50 dark:bg-surface-900/50 border border-surface-200/60 dark:border-surface-800">
          <div>
            <span class="text-sm font-semibold block text-surface-900 dark:text-surface-50">
              Envoyer également une notification par email
            </span>
            <span class="text-xs text-surface-500">
              Transmet une copie intégrale du message sur l'adresse email universitaire des destinataires et des personnes en copie.
            </span>
          </div>
          <ToggleSwitch v-model="copieEmail" />
        </div>
      </div>

      <!-- 5. ACTIONS & RÉCAPITULATIF -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-4 border-t border-surface-200 dark:border-surface-800">
        <div class="text-xs text-surface-500">
          <span class="font-medium">Récapitulatif :</span>
          {{ primaryDestinatairesSummary }}
          <span v-if="selectedCopiePersonnels.length > 0">
            • {{ selectedCopiePersonnels.length }} personnel(s) en copie (CC)
          </span>
        </div>

        <div class="flex items-center justify-end gap-3">
          <Button
            label="Annuler"
            severity="secondary"
            text
            @click="router.push('/message')"
            :disabled="isSending"
          />
          <Button
            type="submit"
            label="Envoyer le message"
            icon="pi pi-send"
            severity="primary"
            :loading="isSending"
          />
        </div>
      </div>
    </form>
  </div>

  <!-- Dialogue de confirmation avant diffusion -->
  <Dialog
    v-model:visible="showConfirmDialog"
    header="Confirmation de diffusion"
    :modal="true"
    :style="{ width: '38rem' }"
    :breakpoints="{ '960px': '75vw', '641px': '95vw' }"
    :closable="!isSending"
  >
    <div class="space-y-4 text-sm">
      <div class="p-3.5 rounded-lg bg-surface-50 dark:bg-surface-800 border border-surface-200 dark:border-surface-700 space-y-2">
        <div>
          <span class="text-xs text-surface-500 font-medium">Objet du message :</span>
          <p class="font-bold text-surface-900 dark:text-surface-50 text-base m-0">{{ sujet }}</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap pt-1">
          <Tag :value="primaryDestinatairesSummary" severity="info" />
          <Tag v-if="prioritaire" value="Urgent" severity="danger" icon="pi pi-exclamation-triangle" />
          <Tag v-if="copieEmail" value="Copie par email" severity="secondary" icon="pi pi-envelope" />
        </div>
      </div>

      <div class="space-y-2 py-1 border-y border-surface-100 dark:border-surface-800 text-xs">
        <div class="flex justify-between items-center py-1">
          <span class="text-surface-500">Cible principale :</span>
          <span class="font-semibold text-surface-900 dark:text-surface-100">
            {{ targetTypeOptions.find(o => o.value === targetType)?.label }}
          </span>
        </div>
        <div v-if="selectedCopiePersonnels.length > 0" class="flex justify-between items-center py-1">
          <span class="text-surface-500">Personnels en copie (CC) :</span>
          <span class="font-semibold text-surface-900 dark:text-surface-100">
            {{ selectedCopiePersonnels.length }} membre(s) du personnel
          </span>
        </div>
        <div v-if="attachedFiles.length > 0" class="flex justify-between items-center py-1">
          <span class="text-surface-500">Pièce(s) jointe(s) :</span>
          <span class="font-semibold text-surface-900 dark:text-surface-100">
            {{ attachedFiles.length }} document(s) ({{ formatFileSize(attachedFiles.reduce((acc, f) => acc + f.size, 0)) }})
          </span>
        </div>
      </div>

      <!-- Alerte sur l'envoi email -->
      <div
        v-if="copieEmail"
        class="p-3 rounded-lg bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-amber-900 dark:text-amber-200 text-xs flex items-start gap-2.5"
      >
        <i class="pi pi-exclamation-circle text-base text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
        <div>
          <strong class="block mb-0.5">Notification immédiate par email activée</strong>
          <span>Une copie de ce message et de ses pièces jointes sera distribuée instantanément sur les boîtes institutionnelles des destinataires.</span>
        </div>
      </div>

      <p class="text-xs text-surface-500 m-0">
        Êtes-vous certain de vouloir diffuser ce message ?
      </p>
    </div>

    <template #footer>
      <div class="flex items-center justify-end gap-2 pt-2">
        <Button
          label="Modifier le message"
          severity="secondary"
          text
          @click="showConfirmDialog = false"
          :disabled="isSending"
        />
        <Button
          label="Confirmer et diffuser"
          icon="pi pi-check"
          severity="primary"
          :loading="isSending"
          @click="submitMessage"
        />
      </div>
    </template>
  </Dialog>
</template>
