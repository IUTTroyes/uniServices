<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import {
  getEtudiantsService,
  getPersonnelsService,
  exportTrombinoscopeSemestreService,
  exportListingSemestreService,
} from '@requests';
import { useFilters } from '../../../../shared/composables/useFilters';
import FilterSidebar from '@/components/Trombinoscope/FilterSidebar.vue';
import StatisticsPanel from '@/components/Trombinoscope/StatisticsPanel.vue';
import PersonCard from '@/components/Trombinoscope/PersonCard.vue';
import { useUsersStore, useAnneeUnivStore } from '@stores';
import { HeaderComponent, ProfilEtudiant } from '@components';
import { ViewPersonnelDialog } from '@dialogs';
import Dialog from 'primevue/dialog';
import { useToast } from 'primevue/usetoast';

const toast = useToast();
const usersStore = useUsersStore();
const anneeUnivStore = useAnneeUnivStore();

// Profile dialog states
const showStudentDialog = ref(false);
const selectedStudent = ref<any>(null);

const showStaffDialog = ref(false);
const selectedStaff = ref<any>(null);

const handleViewProfile = (person: any) => {
  if (filters.value.mode === 'students' || 'scolarites' in person) {
    selectedStudent.value = person;
    showStudentDialog.value = true;
  } else {
    selectedStaff.value = person;
    showStaffDialog.value = true;
  }
};

const departementId = computed(() => usersStore.departementDefaut?.id);
const selectedAnneeUnivId = computed(() => {
  return anneeUnivStore.selectedAnneeUniv?.id ?? anneeUnivStore.anneeUniv?.id ?? null;
});
const selectedAnneeUnivLabel = computed(() => {
  return (
      anneeUnivStore.selectedAnneeUniv?.libelle ??
      anneeUnivStore.selectedAnneeUniv?.label ??
      anneeUnivStore.anneeUniv?.libelle ??
      ''
  );
});

const students = ref<any[]>([]);
const staff = ref<any[]>([]);
const loading = ref(false);
const exporting = ref(false);
const error = ref<string | null>(null);

// Initialize filters composable
const {
  filters,
  filteredStudents,
  filteredStaff,
  updateFilters,
  resetFilters,
  exportData,
} = useFilters(students, staff);

// Computed properties for current filtered data
const currentFilteredData = computed(() => {
  return filters.value.mode === 'students' ? filteredStudents.value : filteredStaff.value;
});

// Map backend student entity to normalized card object
const mapEtudiantToStudentCard = (e: any) => {
  const scolarites = e.scolarites || [];

  // Find scolarité for the current/selected academic year
  let activeScol: any = null;
  if (selectedAnneeUnivId.value) {
    activeScol =
        scolarites.find((s: any) => {
          const au = s?.anneeUniversitaire;
          const auId = au?.id ?? (typeof au === 'string' ? parseInt(au.split('/').pop() || '') : null);
          return auId === selectedAnneeUnivId.value && s?.actif === true;
        }) ||
        scolarites.find((s: any) => {
          const au = s?.anneeUniversitaire;
          const auId = au?.id ?? (typeof au === 'string' ? parseInt(au.split('/').pop() || '') : null);
          return auId === selectedAnneeUnivId.value;
        });
  }

  // Fallback to active scolarité or latest
  if (!activeScol) {
    activeScol = scolarites.find((s: any) => s?.actif === true) || scolarites[scolarites.length - 1] || null;
  }

  // Extract scolariteSemestre array
  const scolariteSemestres = activeScol?.scolariteSemestre || [];
  const activeScolSem = scolariteSemestres[0] || null;

  // Semestre object (support both native V4 StructureSemestre and legacyContext)
  const semObj = activeScolSem?.semestre;
  const legacySem = activeScolSem?.legacyContext?.semestre;

  const semestreId = semObj?.id ?? legacySem?.oldId ?? null;
  const semestreLibelle = semObj?.libelle ?? legacySem?.libelle ?? '';

  // Groups grouping by CM, TD, TP
  const groupesMap: { CM: string[]; TD: string[]; TP: string[] } = { CM: [], TD: [], TP: [] };
  const groupesIdsMap: { CM: number[]; TD: number[]; TP: number[] } = { CM: [], TD: [], TP: [] };

  (activeScolSem?.groupes || []).forEach((g: any) => {
    const type = (g.type || 'TD') as 'CM' | 'TD' | 'TP';
    if (groupesMap[type]) {
      groupesMap[type].push(g.libelle);
      groupesIdsMap[type].push(g.id);
    }
  });

  return {
    id: e.id,
    prenom: e.prenom,
    nom: e.nom,
    mailUniv: e.mailUniv,
    mailPerso: e.mailPerso,
    photoName: e.photoName,
    scolarites: scolarites,
    scolarite: semestreLibelle || (activeScol ? `Scolarité #${activeScol.id}` : 'Inscrit'),
    semestre: semestreId,
    semestreLibelle,
    groupes: groupesMap,
    groupesIds: groupesIdsMap,
    numEtudiant: e.num_etudiant || e.numEtudiant || '',
    bac: e.bac?.libelle || '',
    anneeSortie: e.annee_sortie ?? e.anneeSortie ?? 0,
    status: activeScol?.actif ? 'active' : 'inactive',
  };
};

// Map backend personnel entity to normalized card object
const mapPersonnelToStaffCard = (p: any) => {
  return {
    id: p.id,
    prenom: p.prenom,
    nom: p.nom,
    mailUniv: p.mailUniv,
    photoName: p.photoName,
    statut: p.statut || '',
    displayStatut: p.displayStatut || p.statut || '',
    statutSeverity: p.statutSeverity || 'info',
    bureau: p.bureau || '',
    telBureau: p.telBureau || '',
    posteInterne: p.posteInterne || '',
    domaines: Array.isArray(p.domaines) ? p.domaines.join(', ') : (p.domaines || ''),
    position: p.displayStatut || p.statut || '',
    department: usersStore.departementDefaut?.libelle || '',
  };
};

async function fetchData() {
  if (!departementId.value) {
    error.value = 'Aucun département par défaut sélectionné.';
    return;
  }

  loading.value = true;
  error.value = null;

  try {
    const params: Record<string, any> = {
      departement: departementId.value,
      anneeSortie: 0,
      pagination: false,
      itemsPerPage: 1000,
    };

    if (selectedAnneeUnivId.value) {
      params.anneeUniversitaire = selectedAnneeUnivId.value;
    }

    const [etudiantsResp, personnels] = await Promise.all([
      getEtudiantsService(params),
      getPersonnelsService({
        departement: departementId.value,
      }),
    ]);

    const etudiantsList: any[] = Array.isArray(etudiantsResp) ? etudiantsResp : (etudiantsResp?.member || []);

    // Strict filter:
    // 1. anneeSortie === 0 (only students currently in training, exclude graduated/quit)
    // 2. enrolled in the selected academic year
    const activeStudents = etudiantsList.filter((e: any) => {
      const anneeSortie = e.annee_sortie ?? e.anneeSortie ?? 0;
      if (anneeSortie !== 0) return false;

      if (selectedAnneeUnivId.value) {
        const hasScolInYear = (e.scolarites || []).some((s: any) => {
          const au = s?.anneeUniversitaire;
          const auId = au?.id ?? (typeof au === 'string' ? parseInt(au.split('/').pop() || '') : null);
          return auId === selectedAnneeUnivId.value;
        });
        if (!hasScolInYear) return false;
      }

      return true;
    });

    students.value = activeStudents.map(mapEtudiantToStudentCard);

    const personnelsList: any[] = Array.isArray(personnels) ? personnels : (personnels?.member || []);
    staff.value = personnelsList.map(mapPersonnelToStaffCard);
  } catch (e: any) {
    console.error('Erreur chargement données trombinoscope:', e);
    error.value = e?.message || 'Erreur lors de la récupération des données.';
  } finally {
    loading.value = false;
  }
}

// Export handlers
const handleExportTrombiPdf = async () => {
  const semId = filters.value.studentFilters.semester;
  if (!semId) {
    toast?.add({
      severity: 'warn',
      summary: 'Export PDF',
      detail: 'Veuillez sélectionner un semestre pour exporter le trombinoscope.',
      life: 3000,
    });
    return;
  }

  exporting.value = true;
  try {
    await exportTrombinoscopeSemestreService(
        Number(semId),
        filters.value.studentFilters.groupType || null,
        filters.value.studentFilters.group ? Number(filters.value.studentFilters.group) : null
    );
    toast?.add({
      severity: 'success',
      summary: 'Export PDF',
      detail: 'Le trombinoscope PDF a été généré avec succès.',
      life: 3000,
    });
  } catch (err: any) {
    toast?.add({
      severity: 'error',
      summary: 'Erreur Export PDF',
      detail: err?.message || 'Impossible de générer le trombinoscope PDF.',
      life: 4000,
    });
  } finally {
    exporting.value = false;
  }
};

const handleExportListingExcel = async () => {
  const semId = filters.value.studentFilters.semester;
  if (!semId) {
    toast?.add({
      severity: 'warn',
      summary: 'Export Excel',
      detail: 'Veuillez sélectionner un semestre pour exporter le listing.',
      life: 3000,
    });
    return;
  }

  exporting.value = true;
  try {
    await exportListingSemestreService(
        Number(semId),
        'xlsx',
        filters.value.studentFilters.groupType || null,
        filters.value.studentFilters.group ? Number(filters.value.studentFilters.group) : null
    );
    toast?.add({
      severity: 'success',
      summary: 'Export Excel',
      detail: 'Le listing Excel a été téléchargé avec succès.',
      life: 3000,
    });
  } catch (err: any) {
    toast?.add({
      severity: 'error',
      summary: 'Erreur Export Excel',
      detail: err?.message || 'Impossible de générer le listing Excel.',
      life: 4000,
    });
  } finally {
    exporting.value = false;
  }
};

// Keyboard shortcuts
const handleKeydown = (event: KeyboardEvent) => {
  if (event.ctrlKey || event.metaKey) {
    switch (event.key) {
      case 'f':
        event.preventDefault();
        const searchInput = document.querySelector('aside input[type="text"]') as HTMLInputElement;
        if (searchInput) searchInput.focus();
        break;
      case 'r':
        event.preventDefault();
        resetFilters();
        break;
      case 'e':
        event.preventDefault();
        exportData();
        break;
    }
  }
};

onMounted(async () => {
  document.addEventListener('keydown', handleKeydown);

  // Initialize academic year if not yet set in store
  if (!anneeUnivStore.selectedAnneeUniv && !anneeUnivStore.anneeUniv) {
    const stored = localStorage.getItem('selectedAnneeUniv');
    if (stored) {
      try {
        anneeUnivStore.selectedAnneeUniv = JSON.parse(stored);
      } catch {}
    }
  }

  if (!anneeUnivStore.selectedAnneeUniv && !anneeUnivStore.anneeUniv) {
    await anneeUnivStore.getCurrentAnneeUniv();
  }

  await fetchData();
});

watch(departementId, async (newVal, oldVal) => {
  if (newVal && newVal !== oldVal) {
    await fetchData();
  }
});

watch(selectedAnneeUnivId, async (newVal, oldVal) => {
  if (newVal && newVal !== oldVal) {
    await fetchData();
  }
});

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
  <div class="flex flex-col h-[calc(100vh-4rem)] overflow-hidden bg-gray-50/60">
    <HeaderComponent
        icon="pi pi-users"
        titre="Trombinoscope"
        :description="selectedAnneeUnivLabel ? `Consultez les profils des étudiants et du personnel — Année ${selectedAnneeUnivLabel}` : 'Consultez les profils des étudiants et du personnel'"
    />

    <div class="flex-1 flex overflow-hidden">
      <!-- Filter Sidebar -->
      <FilterSidebar
          :filters="filters"
          :students="students"
          :staff="staff"
          :annee-univ-label="selectedAnneeUnivLabel"
          @update-filters="updateFilters"
          @reset-filters="resetFilters"
          @export-data="exportData"
          @export-trombi-pdf="handleExportTrombiPdf"
          @export-listing-excel="handleExportListingExcel"
      />

      <!-- Main Content Area -->
      <main class="flex-1 flex flex-col min-w-0 overflow-hidden bg-gray-50/50">
        <!-- Statistics Panel -->
        <StatisticsPanel
            :students="students"
            :staff="staff"
            :filtered-students="filteredStudents"
            :filtered-staff="filteredStaff"
            :filters="filters"
            :annee-univ-label="selectedAnneeUnivLabel"
            @reset-filters="resetFilters"
        />

        <!-- Content Area -->
        <div class="flex-1 overflow-y-auto p-6">
          <!-- Loading State -->
          <div v-if="loading" class="flex flex-col justify-center items-center py-20 space-y-4">
            <div class="w-10 h-10 border-3 border-primary-200 border-t-primary-600 rounded-full animate-spin"></div>
            <div class="text-sm font-medium text-gray-500">Chargement des données du trombinoscope...</div>
          </div>

          <!-- Error State -->
          <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-2xl p-8 text-center max-w-lg mx-auto my-12 shadow-sm">
            <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center mx-auto mb-3">
              <i class="pi pi-exclamation-triangle text-xl"></i>
            </div>
            <h3 class="text-base font-semibold text-red-800 mb-1">Erreur de chargement</h3>
            <p class="text-xs text-red-600 mb-4">{{ error }}</p>
            <button
                @click="fetchData"
                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-semibold shadow-xs transition-colors"
            >
              Réessayer
            </button>
          </div>

          <!-- Empty State -->
          <div v-else-if="currentFilteredData.length === 0" class="bg-white border border-gray-200 rounded-2xl p-12 text-center max-w-md mx-auto my-12 shadow-sm">
            <div class="w-16 h-16 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center mx-auto mb-4">
              <i class="pi pi-search text-2xl"></i>
            </div>
            <h3 class="text-base font-semibold text-gray-900 mb-1">Aucun résultat trouvé</h3>
            <p class="text-xs text-gray-500 mb-6">
              Aucun {{ filters.mode === 'students' ? 'étudiant' : 'membre du personnel' }} ne correspond à vos critères de filtrage.
            </p>
            <button
                @click="resetFilters"
                class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-xs font-semibold shadow-xs transition-colors inline-flex items-center gap-2"
            >
              <i class="pi pi-refresh text-xs"></i>
              <span>Réinitialiser les critères</span>
            </button>
          </div>

          <!-- Grid View Mode -->
          <div
              v-else-if="filters.viewMode === 'grid'"
              class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4"
          >
            <PersonCard
                v-for="person in currentFilteredData"
                :key="person.id"
                :person="person"
                :mode="filters.mode"
                :view-mode="filters.viewMode"
                @view-profile="handleViewProfile"
            />
          </div>

          <!-- List View Mode -->
          <div
              v-else
              class="space-y-3 max-w-5xl mx-auto"
          >
            <PersonCard
                v-for="person in currentFilteredData"
                :key="person.id"
                :person="person"
                :mode="filters.mode"
                :view-mode="filters.viewMode"
                @view-profile="handleViewProfile"
            />
          </div>
        </div>

        <!-- Footer status bar -->
        <footer class="bg-white border-t border-gray-200 px-6 py-2.5 flex items-center justify-between text-xs text-gray-500">
          <div class="flex items-center gap-4">
            <span>
              <kbd class="px-1.5 py-0.5 bg-gray-100 border border-gray-200 rounded text-[10px] font-mono">Ctrl+F</kbd> recherche
            </span>
            <span>
              <kbd class="px-1.5 py-0.5 bg-gray-100 border border-gray-200 rounded text-[10px] font-mono">Ctrl+R</kbd> réinitialiser
            </span>
            <span>
              <kbd class="px-1.5 py-0.5 bg-gray-100 border border-gray-200 rounded text-[10px] font-mono">Ctrl+E</kbd> exporter CSV
            </span>
          </div>
          <div class="font-medium text-gray-600">
            {{ currentFilteredData.length }} élément{{ currentFilteredData.length > 1 ? 's' : '' }} affiché{{ currentFilteredData.length > 1 ? 's' : '' }}
          </div>
        </footer>
      </main>
    </div>

    <!-- Student Profile Dialog -->
    <Dialog
        header=" "
        :visible="showStudentDialog"
        modal
        :style="{ width: '90vw' }"
        :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
        dismissable-mask
        :closable="true"
        @update:visible="showStudentDialog = $event"
    >
      <ProfilEtudiant
          v-if="selectedStudent && showStudentDialog"
          :key="selectedStudent.id"
          :etudiantId="Number(selectedStudent.id)"
          :isVisible="showStudentDialog"
      />
    </Dialog>

    <!-- Staff Profile Dialog -->
    <ViewPersonnelDialog
        :isVisible="showStaffDialog"
        :personnel="selectedStaff"
        @update:visible="showStaffDialog = $event"
    />
  </div>
</template>

