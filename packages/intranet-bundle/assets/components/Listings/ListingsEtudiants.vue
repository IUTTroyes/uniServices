<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useUsersStore, useAnneeUnivStore } from '@stores';
import {
  getDepartementSemestresService,
  getSemestresService,
  getGroupesService,
  exportListingGroupeService,
  exportListingSemestreService,
  exportTrombinoscopeSemestreService,
  exportPersonnaliseService,
} from '@requests';
import { EmptyState } from '@components';

const props = withDefaults(
  defineProps<{
    compact?: boolean;
    initialSemestreId?: number | null;
  }>(),
  {
    compact: false,
    initialSemestreId: null,
  }
);

const toast = useToast();
const usersStore = useUsersStore();
const anneeUnivStore = useAnneeUnivStore();

const semestres = ref<any[]>([]);
const selectedSemestreId = ref<number | null>(props.initialSemestreId);
const groupes = ref<any[]>([]);
const isLoadingSemestres = ref(false);
const isLoadingGroupes = ref(false);
const isExporting = ref(false);
const exportingGroupeId = ref<number | null>(null);

// Menus PrimeVue
const menuPdf = ref();
const menuExcel = ref();
const menuCsv = ref();
const menuTrombi = ref();

// Modal Export Personnalisé
const showCustomModal = ref(false);
const customScope = ref<'semestre' | 'groupe'>('semestre');
const customGroupeId = ref<number | null>(null);
const customFormat = ref<'xlsx' | 'pdf' | 'csv'>('xlsx');
const customChamps = ref<string[]>(['numEtudiant', 'nom', 'prenom', 'mailUniv']);
const isExportingCustom = ref(false);

const departementId = computed(() => usersStore.departementDefaut?.id ?? null);
const selectedAnneeUnivId = computed(() => anneeUnivStore.selectedAnneeUniv?.id ?? null);

const selectedSemestre = computed(() => {
  return semestres.value.find((s) => s.id === selectedSemestreId.value) || null;
});

// Groupes triés par type
const groupesParType = computed(() => {
  const map: Record<string, any[]> = {};
  for (const g of groupes.value) {
    const type = g.type || 'AUTRE';
    if (!map[type]) {
      map[type] = [];
    }
    map[type].push(g);
  }
  return map;
});

const typesDisponibles = computed(() => Object.keys(groupesParType.value));

// Groupes par défaut (TD en priorité, sinon TP, sinon tous)
const groupesParDefaut = computed(() => {
  if (groupesParType.value['TD'] && groupesParType.value['TD'].length > 0) {
    return groupesParType.value['TD'];
  }
  if (groupesParType.value['TP'] && groupesParType.value['TP'].length > 0) {
    return groupesParType.value['TP'];
  }
  return groupes.value;
});

onMounted(async () => {
  await loadSemestres();
});

watch(departementId, async () => {
  await loadSemestres();
});

watch(selectedAnneeUnivId, async () => {
  await loadSemestres();
});

watch(selectedSemestreId, async (newVal) => {
  if (newVal) {
    await loadGroupes(newVal);
  } else {
    groupes.value = [];
  }
});

const loadSemestres = async () => {
  isLoadingSemestres.value = true;
  try {
    let result: any[] = [];
    if (departementId.value) {
      result = await getDepartementSemestresService(departementId.value, true);
    } else {
      result = await getSemestresService({ actif: true });
    }
    const list = Array.isArray(result) ? result : (result?.member ?? []);
    semestres.value = list;

    if (list.length > 0) {
      if (!selectedSemestreId.value || !list.some((s) => s.id === selectedSemestreId.value)) {
        selectedSemestreId.value = list[0].id;
      }
    } else {
      selectedSemestreId.value = null;
    }
  } catch (error) {
    console.error('Erreur chargement semestres:', error);
  } finally {
    isLoadingSemestres.value = false;
  }
};

const loadGroupes = async (semestreId: number) => {
  isLoadingGroupes.value = true;
  try {
    const rawGroupes = await getGroupesService({ semestre: semestreId }, '/mini');
    groupes.value = Array.isArray(rawGroupes) ? rawGroupes : (rawGroupes?.member ?? []);
  } catch (error) {
    console.error('Erreur chargement groupes:', error);
    groupes.value = [];
  } finally {
    isLoadingGroupes.value = false;
  }
};

// Actions d'export
const handleExportGroupe = async (groupeId: number, format: 'pdf' | 'xlsx' | 'csv' = 'pdf', libelle = '') => {
  if (!selectedSemestreId.value) return;
  exportingGroupeId.value = groupeId;
  toast.add({
    severity: 'info',
    summary: 'Export en cours',
    detail: `Génération de la feuille d'émargement pour le groupe ${libelle}...`,
    life: 2500,
  });

  try {
    await exportListingGroupeService(groupeId, format, selectedSemestreId.value);
    toast.add({
      severity: 'success',
      summary: 'Export réussi',
      detail: `Le fichier pour le groupe ${libelle} a été téléchargé.`,
      life: 3000,
    });
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: "Erreur lors de l'export",
      detail: error?.message || 'Une erreur est survenue lors de la génération.',
      life: 4500,
    });
  } finally {
    exportingGroupeId.value = null;
  }
};

const handleExportSemestre = async (format: 'pdf' | 'xlsx' | 'csv', type: string | null = null) => {
  if (!selectedSemestreId.value) return;
  isExporting.value = true;
  const label = type ? `type ${type}` : 'tous les groupes';
  toast.add({
    severity: 'info',
    summary: 'Export en cours',
    detail: `Génération du listing (${format.toUpperCase()}) pour ${label}...`,
    life: 2500,
  });

  try {
    await exportListingSemestreService(selectedSemestreId.value, format, type);
    toast.add({
      severity: 'success',
      summary: 'Export réussi',
      detail: `Le listing ${format.toUpperCase()} a été téléchargé avec succès.`,
      life: 3000,
    });
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: "Erreur lors de l'export",
      detail: error?.message || 'Une erreur est survenue lors de la génération.',
      life: 4500,
    });
  } finally {
    isExporting.value = false;
  }
};

const handleExportTrombinoscope = async (type: string | null = null) => {
  if (!selectedSemestreId.value) return;
  isExporting.value = true;
  const label = type ? `type ${type}` : 'complet';
  toast.add({
    severity: 'info',
    summary: 'Génération Gotenberg',
    detail: `Création du trombinoscope PDF (${label}) avec photos...`,
    life: 3000,
  });

  try {
    await exportTrombinoscopeSemestreService(selectedSemestreId.value, type);
    toast.add({
      severity: 'success',
      summary: 'Trombinoscope généré',
      detail: 'Le fichier PDF du trombinoscope a été téléchargé.',
      life: 3000,
    });
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Erreur Gotenberg',
      detail: error?.message || 'Échec de la génération du trombinoscope.',
      life: 4500,
    });
  } finally {
    isExporting.value = false;
  }
};

const submitCustomExport = async () => {
  if (!selectedSemestreId.value) return;
  isExportingCustom.value = true;
  try {
    const payload = {
      semestreId: selectedSemestreId.value,
      groupeId: customScope.value === 'groupe' ? customGroupeId.value : null,
      format: customFormat.value,
      champs: customChamps.value,
    };
    await exportPersonnaliseService(payload);
    toast.add({
      severity: 'success',
      summary: 'Export personnalisé réussi',
      detail: 'Votre fichier sur-mesure a été généré et téléchargé.',
      life: 3000,
    });
    showCustomModal.value = false;
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: "Erreur lors de l'export",
      detail: error?.message || 'Une erreur est survenue.',
      life: 4500,
    });
  } finally {
    isExportingCustom.value = false;
  }
};

// Menus items
const pdfMenuItems = computed(() => {
  const items = [
    {
      label: 'Tous les groupes (complet)',
      icon: 'pi pi-file-pdf',
      command: () => handleExportSemestre('pdf', null),
    },
  ];
  for (const t of typesDisponibles.value) {
    items.push({
      label: `Tous les groupes ${t}`,
      icon: 'pi pi-folder',
      command: () => handleExportSemestre('pdf', t),
    });
  }
  return items;
});

const excelMenuItems = computed(() => {
  const items = [
    {
      label: 'Classeur complet (tous les groupes)',
      icon: 'pi pi-file-excel',
      command: () => handleExportSemestre('xlsx', null),
    },
  ];
  for (const t of typesDisponibles.value) {
    items.push({
      label: `Classeur groupes ${t}`,
      icon: 'pi pi-table',
      command: () => handleExportSemestre('xlsx', t),
    });
  }
  return items;
});

const csvMenuItems = computed(() => {
  const items = [
    {
      label: 'CSV complet (tous les groupes)',
      icon: 'pi pi-file',
      command: () => handleExportSemestre('csv', null),
    },
  ];
  for (const t of typesDisponibles.value) {
    items.push({
      label: `CSV groupes ${t}`,
      icon: 'pi pi-list',
      command: () => handleExportSemestre('csv', t),
    });
  }
  return items;
});

const trombiMenuItems = computed(() => {
  const items = [
    {
      label: 'Trombinoscope complet (PDF)',
      icon: 'pi pi-images',
      command: () => handleExportTrombinoscope(null),
    },
  ];
  for (const t of typesDisponibles.value) {
    items.push({
      label: `Trombinoscope ${t} (PDF)`,
      icon: 'pi pi-users',
      command: () => handleExportTrombinoscope(t),
    });
  }
  return items;
});
</script>

<template>
  <div class="listings-etudiants-container flex flex-col gap-4">
    <!-- BARRE DE SÉLECTION DU SEMESTRE -->
    <div class="flex flex-wrap items-center justify-between gap-3 p-3 bg-neutral-50 dark:bg-neutral-800/60 rounded-xl border border-neutral-200/70 dark:border-neutral-700/60">
      <div class="flex items-center gap-2">
        <i class="pi pi-calendar text-violet-600 dark:text-violet-400 text-lg"></i>
        <label for="select-semestre-listing" class="text-xs font-semibold text-neutral-700 dark:text-neutral-200 uppercase tracking-wide">
          Semestre :
        </label>
        <Select
          id="select-semestre-listing"
          v-model="selectedSemestreId"
          :options="semestres"
          option-label="libelle"
          option-value="id"
          placeholder="Choisir un semestre"
          class="w-48 sm:w-56 text-sm"
          :loading="isLoadingSemestres"
        />
      </div>

      <div class="flex items-center gap-2 text-xs text-neutral-500">
        <Tag v-if="selectedSemestre" :value="`Semestre ${selectedSemestre.libelle}`" severity="info" />
        <Tag v-if="groupes.length > 0" :value="`${groupes.length} groupe(s)`" severity="secondary" />
      </div>
    </div>

    <!-- ÉTAT DE CHARGEMENT -->
    <div v-if="isLoadingGroupes" class="grid grid-cols-2 sm:grid-cols-4 gap-2 py-4">
      <Skeleton height="38px" border-radius="8px" class="w-full" />
      <Skeleton height="38px" border-radius="8px" class="w-full" />
      <Skeleton height="38px" border-radius="8px" class="w-full" />
      <Skeleton height="38px" border-radius="8px" class="w-full" />
    </div>

    <!-- ÉTAT AUCUN SEMESTRE OU GROUPE -->
    <EmptyState
      v-else-if="!selectedSemestreId"
      compact
      title="Sélectionnez un semestre"
      description="Choisissez un semestre pour accéder aux boutons d'émargement et aux exports."
      icon="pi pi-calendar"
      color="purple"
    />

    <EmptyState
      v-else-if="groupes.length === 0"
      compact
      title="Aucun groupe disponible"
      description="Aucun groupe n'est configuré pour le semestre sélectionné."
      icon="pi pi-users"
      color="gray"
    />

    <!-- CONTENU PRINCIPAL -->
    <template v-else>
      <!-- 1. ACCÈS RAPIDE PAR DÉFAUT (1-CLIC ÉMARGEMENT PDF) -->
      <div class="section-quick-access flex flex-col gap-2">
        <div class="flex items-center justify-between">
          <div class="text-xs font-semibold text-neutral-600 dark:text-neutral-300 uppercase tracking-wider flex items-center gap-1.5">
            <i class="pi pi-bolt text-amber-500"></i>
            Émargement 1-clic (PDF Gotenberg)
          </div>
          <small class="text-neutral-400 text-xs">Groupes par défaut</small>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">
          <Button
            v-for="groupe in groupesParDefaut"
            :key="groupe.id"
            severity="info"
            outlined
            size="small"
            class="flex items-center justify-center gap-2 py-2 px-3 hover:shadow-sm transition-all"
            :loading="exportingGroupeId === groupe.id"
            :title="`Télécharger l'émargement PDF pour ${groupe.libelle}`"
            @click="handleExportGroupe(groupe.id, 'pdf', groupe.libelle)"
          >
            <i class="pi pi-file-pdf text-rose-500"></i>
            <span class="font-bold text-neutral-800 dark:text-neutral-100">{{ groupe.libelle }}</span>
          </Button>
        </div>
      </div>

      <!-- 2. EXPORTS GROUPÉS & MULTI-FORMATS -->
      <div class="section-formats pt-1 border-t border-neutral-100 dark:border-neutral-800">
        <div class="text-xs font-semibold text-neutral-600 dark:text-neutral-300 uppercase tracking-wider mb-2 flex items-center gap-1.5">
          <i class="pi pi-download text-violet-500"></i>
          Exports groupés & Formats
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2">
          <!-- BOUTON FORMAT PDF -->
          <div>
            <Button
              type="button"
              severity="danger"
              outlined
              size="small"
              class="w-full flex items-center justify-between gap-1 text-xs font-medium"
              :loading="isExporting"
              @click="(event) => menuPdf.toggle(event)"
            >
              <div class="flex items-center gap-1.5 truncate">
                <i class="pi pi-file-pdf text-rose-600"></i>
                <span class="truncate">Format PDF</span>
              </div>
              <i class="pi pi-chevron-down text-xs opacity-70"></i>
            </Button>
            <Menu ref="menuPdf" :model="pdfMenuItems" :popup="true" />
          </div>

          <!-- BOUTON FORMAT EXCEL -->
          <div>
            <Button
              type="button"
              severity="success"
              outlined
              size="small"
              class="w-full flex items-center justify-between gap-1 text-xs font-medium"
              :loading="isExporting"
              @click="(event) => menuExcel.toggle(event)"
            >
              <div class="flex items-center gap-1.5 truncate">
                <i class="pi pi-file-excel text-emerald-600"></i>
                <span class="truncate">Format Excel</span>
              </div>
              <i class="pi pi-chevron-down text-xs opacity-70"></i>
            </Button>
            <Menu ref="menuExcel" :model="excelMenuItems" :popup="true" />
          </div>

          <!-- BOUTON FORMAT CSV -->
          <div>
            <Button
              type="button"
              severity="secondary"
              outlined
              size="small"
              class="w-full flex items-center justify-between gap-1 text-xs font-medium"
              :loading="isExporting"
              @click="(event) => menuCsv.toggle(event)"
            >
              <div class="flex items-center gap-1.5 truncate">
                <i class="pi pi-file text-slate-500"></i>
                <span class="truncate">Format CSV</span>
              </div>
              <i class="pi pi-chevron-down text-xs opacity-70"></i>
            </Button>
            <Menu ref="menuCsv" :model="csvMenuItems" :popup="true" />
          </div>

          <!-- BOUTON TROMBI PDF -->
          <div>
            <Button
              type="button"
              severity="help"
              outlined
              size="small"
              class="w-full flex items-center justify-between gap-1 text-xs font-medium"
              :loading="isExporting"
              @click="(event) => menuTrombi.toggle(event)"
            >
              <div class="flex items-center gap-1.5 truncate">
                <i class="pi pi-images text-purple-600"></i>
                <span class="truncate">Trombi. PDF</span>
              </div>
              <i class="pi pi-chevron-down text-xs opacity-70"></i>
            </Button>
            <Menu ref="menuTrombi" :model="trombiMenuItems" :popup="true" />
          </div>

          <!-- BOUTON EXPORT PERSONNALISÉ -->
          <div>
            <Button
              type="button"
              severity="primary"
              size="small"
              class="w-full flex items-center justify-center gap-1.5 text-xs font-medium"
              @click="showCustomModal = true"
            >
              <i class="pi pi-sliders-h"></i>
              <span class="truncate">Personnalisé</span>
            </Button>
          </div>
        </div>
      </div>
    </template>

    <!-- DIALOG MODAL : EXPORT PERSONNALISÉ -->
    <Dialog
      v-model:visible="showCustomModal"
      modal
      header="Export personnalisé des étudiants"
      :style="{ width: '90vw', maxWidth: '560px' }"
    >
      <div class="flex flex-col gap-4 py-2">
        <!-- Périmètre -->
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-neutral-700 dark:text-neutral-200 uppercase tracking-wide">
            Périmètre d'export
          </label>
          <div class="flex items-center gap-4 text-sm">
            <label class="flex items-center gap-2 cursor-pointer">
              <input
                type="radio"
                name="customScope"
                value="semestre"
                v-model="customScope"
                class="accent-primary-600"
              />
              <span>Tout le semestre ({{ selectedSemestre?.libelle }})</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input
                type="radio"
                name="customScope"
                value="groupe"
                v-model="customScope"
                class="accent-primary-600"
              />
              <span>Un groupe précis</span>
            </label>
          </div>
        </div>

        <!-- Choix du groupe si périmètre groupe -->
        <div v-if="customScope === 'groupe'" class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-neutral-700 dark:text-neutral-200">
            Sélectionner le groupe
          </label>
          <Select
            v-model="customGroupeId"
            :options="groupes"
            option-label="libelle"
            option-value="id"
            placeholder="Choisir un groupe"
            class="w-full text-sm"
          />
        </div>

        <!-- Format de sortie -->
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-neutral-700 dark:text-neutral-200 uppercase tracking-wide">
            Format de fichier
          </label>
          <div class="grid grid-cols-3 gap-2">
            <Button
              type="button"
              :severity="customFormat === 'xlsx' ? 'success' : 'secondary'"
              :outlined="customFormat !== 'xlsx'"
              size="small"
              class="flex items-center justify-center gap-1.5"
              @click="customFormat = 'xlsx'"
            >
              <i class="pi pi-file-excel"></i>
              <span>Excel (.xlsx)</span>
            </Button>
            <Button
              type="button"
              :severity="customFormat === 'pdf' ? 'danger' : 'secondary'"
              :outlined="customFormat !== 'pdf'"
              size="small"
              class="flex items-center justify-center gap-1.5"
              @click="customFormat = 'pdf'"
            >
              <i class="pi pi-file-pdf"></i>
              <span>PDF</span>
            </Button>
            <Button
              type="button"
              :severity="customFormat === 'csv' ? 'info' : 'secondary'"
              :outlined="customFormat !== 'csv'"
              size="small"
              class="flex items-center justify-center gap-1.5"
              @click="customFormat = 'csv'"
            >
              <i class="pi pi-file"></i>
              <span>CSV</span>
            </Button>
          </div>
        </div>

        <!-- Colonnes à inclure -->
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-neutral-700 dark:text-neutral-200 uppercase tracking-wide">
            Colonnes à exporter
          </label>
          <div class="grid grid-cols-2 gap-2 text-sm bg-neutral-50 dark:bg-neutral-800/60 p-3 rounded-lg border border-neutral-200/60 dark:border-neutral-700/60">
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" value="numEtudiant" v-model="customChamps" class="accent-primary-600 rounded" />
              <span>N° Étudiant</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" value="nom" v-model="customChamps" class="accent-primary-600 rounded" />
              <span>Nom</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" value="prenom" v-model="customChamps" class="accent-primary-600 rounded" />
              <span>Prénom</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" value="mailUniv" v-model="customChamps" class="accent-primary-600 rounded" />
              <span>Email Universitaire</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" value="bac" v-model="customChamps" class="accent-primary-600 rounded" />
              <span>Bac / Origine</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" value="groupes" v-model="customChamps" class="accent-primary-600 rounded" />
              <span>Groupes d'affectation</span>
            </label>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end gap-2 pt-2">
          <Button
            label="Annuler"
            severity="secondary"
            text
            size="small"
            @click="showCustomModal = false"
          />
          <Button
            label="Télécharger l'export"
            icon="pi pi-download"
            severity="primary"
            size="small"
            :loading="isExportingCustom"
            :disabled="customChamps.length === 0 || (customScope === 'groupe' && !customGroupeId)"
            @click="submitCustomExport"
          />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<style scoped>
.listings-etudiants-container {
  width: 100%;
}
</style>
