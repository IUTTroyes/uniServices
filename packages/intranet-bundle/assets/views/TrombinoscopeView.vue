<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useFilters } from '@composables/useFilters';
import FilterSidebar from '@/components/Trombinoscope/FilterSidebar.vue';
import StatisticsPanel from '@/components/Trombinoscope/StatisticsPanel.vue';
import PersonCard from '@/components/Trombinoscope/PersonCard.vue';
import { useTrombinoscope } from '@/composables/useTrombinoscope';
import type { PersonItem } from '@/types/trombinoscope';
import { useUsersStore, useAnneeUnivStore } from '@stores';
import { HeaderComponent, ProfilEtudiant, Kpi, ListSkeleton } from '@components';
import { ViewPersonnelDialog } from '@dialogs';
import Dialog from 'primevue/dialog';
import { useToast } from 'primevue/usetoast';

const toast = useToast();
const route = useRoute();
const router = useRouter();
const usersStore = useUsersStore();
const anneeUnivStore = useAnneeUnivStore();

const filterSidebarRef = ref<InstanceType<typeof FilterSidebar> | null>(null);

// Profile dialog states
const showStudentDialog = ref(false);
const selectedStudent = ref<any>(null);

const showStaffDialog = ref(false);
const selectedStaff = ref<any>(null);

const handleViewProfile = (person: PersonItem) => {
  if (filters.value.mode === 'students' || 'scolarites' in person || 'scolarite' in person) {
    selectedStudent.value = person;
    showStudentDialog.value = true;
  } else {
    selectedStaff.value = person;
    showStaffDialog.value = true;
  }
};

const departementId = computed(() => usersStore.departementDefaut?.id);
const departementLibelle = computed(() => usersStore.departementDefaut?.libelle);

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

// Single shared reactive lists for students and staff
const students = ref<StudentCardItem[]>([]);
const staff = ref<StaffCardItem[]>([]);

// Setup filters composable
const {
  filters,
  filteredStudents,
  filteredStaff,
  updateFilters,
  resetFilters,
  exportData,
} = useFilters(students, staff);

// Initialize Trombinoscope composable
const {
  loading,
  exporting,
  error,
  fetchData,
  exportTrombiPdf,
  exportListingExcel,
} = useTrombinoscope(departementId, selectedAnneeUnivId, departementLibelle, filters, students, staff);

// Computed properties for current filtered data
const currentFilteredData = computed(() => {
  return filters.value.mode === 'students' ? filteredStudents.value : filteredStaff.value;
});

const isFiltered = computed(() => {
  if (filters.value.searchTerm && filters.value.searchTerm.trim() !== '') return true;
  if (filters.value.mode === 'students') {
    return Boolean(
      filters.value.studentFilters.semester ||
      filters.value.studentFilters.groupType ||
      filters.value.studentFilters.group
    );
  }
  return Boolean(filters.value.staffFilters.statut || filters.value.staffFilters.department);
});

const PERMANENTS_STATUTS = ['MCF', 'PU', 'PRCE', 'PRAG', 'CONTRACTUEL', 'CONTRAC', 'PRCACDD', 'CDD'];
const VACATAIRES_STATUTS = ['VAC', 'VACATAIRE', 'ATER', 'ENSAM', 'DO', 'PRO', 'PROFESSION', 'PR'];

const isStaffPermanent = (statut?: string) => {
  const s = (statut || '').trim().toUpperCase();
  return PERMANENTS_STATUTS.includes(s);
};

const isStaffVacataire = (statut?: string) => {
  const s = (statut || '').trim().toUpperCase();
  return VACATAIRES_STATUTS.includes(s);
};

const staffKpis = computed(() => {
  const total = staff.value.length;
  const filtered = filteredStaff.value.length;
  const permanents = staff.value.filter(p => isStaffPermanent(p.statut)).length;
  const vacataires = staff.value.filter(p => isStaffVacataire(p.statut)).length;
  const biatss = staff.value.filter(p => !isStaffPermanent(p.statut) && !isStaffVacataire(p.statut)).length;

  const permanentsPct = total > 0 ? Math.round((permanents / total) * 100) : 0;
  const vacatairesPct = total > 0 ? Math.round((vacataires / total) * 100) : 0;
  const biatssPct = total > 0 ? Math.round((biatss / total) * 100) : 0;

  return {
    total,
    filtered,
    permanents,
    permanentsPct,
    vacataires,
    vacatairesPct,
    biatss,
    biatssPct,
  };
});

const canViewKpis = computed(() => !usersStore.isEtudiant);

const studentsKpis = computed(() => {
  const total = students.value.length;
  const filtered = filteredStudents.value.length;
  const active = students.value.filter(s => s.status === 'active').length;
  const activePct = total > 0 ? Math.round((active / total) * 100) : 0;

  const distinctSemesters = [
    ...new Set(
      students.value
        .map(s => s.semestreLibelle || (s.semestre ? `S${s.semestre}` : ''))
        .filter(Boolean)
    ),
  ].sort();

  const semestersCount = distinctSemesters.length;
  const semestersList = distinctSemesters.length > 0
    ? distinctSemesters.slice(0, 4).join(', ') + (distinctSemesters.length > 4 ? '...' : '')
    : 'Aucun semestre';

  return {
    total,
    filtered,
    active,
    activePct,
    semestersCount,
    semestersList,
  };
});

// URL synchronization
const initializeFiltersFromQuery = () => {
  const q = route.query;
  const updates: any = {};

  if (q.mode === 'staff' || q.mode === 'students') {
    updates.mode = q.mode;
  }
  if (q.view === 'list' || q.view === 'grid') {
    updates.viewMode = q.view;
  }
  if (q.q) {
    updates.searchTerm = String(q.q);
  }
  if (q.semestre) {
    updates.studentFilters = {
      ...filters.value.studentFilters,
      semester: Number(q.semestre),
      groupType: (q.groupType as any) || null,
      group: (q.group as any) || null,
    };
  }
  if (q.statut) {
    updates.staffFilters = {
      ...filters.value.staffFilters,
      statut: String(q.statut),
    };
  }

  if (Object.keys(updates).length > 0) {
    updateFilters(updates);
  }
};

let queryDebounceTimeout: ReturnType<typeof setTimeout> | null = null;
watch(
  () => [
    filters.value.mode,
    filters.value.viewMode,
    filters.value.searchTerm,
    filters.value.studentFilters.semester,
    filters.value.studentFilters.groupType,
    filters.value.studentFilters.group,
    filters.value.staffFilters.statut,
  ],
  () => {
    if (queryDebounceTimeout) clearTimeout(queryDebounceTimeout);
    queryDebounceTimeout = setTimeout(() => {
      const nextQuery: Record<string, string> = {};
      if (filters.value.mode !== 'students') nextQuery.mode = filters.value.mode;
      if (filters.value.viewMode !== 'grid') nextQuery.view = filters.value.viewMode;
      if (filters.value.searchTerm) nextQuery.q = filters.value.searchTerm;
      if (filters.value.studentFilters.semester) nextQuery.semestre = String(filters.value.studentFilters.semester);
      if (filters.value.studentFilters.groupType) nextQuery.groupType = filters.value.studentFilters.groupType;
      if (filters.value.studentFilters.group) nextQuery.group = String(filters.value.studentFilters.group);
      if (filters.value.staffFilters.statut) nextQuery.statut = filters.value.staffFilters.statut;

      router.replace({ query: nextQuery });
    }, 250);
  }
);

// Keyboard shortcuts
const handleKeydown = (event: KeyboardEvent) => {
  if (event.ctrlKey || event.metaKey) {
    switch (event.key) {
      case 'f':
        event.preventDefault();
        filterSidebarRef.value?.focusSearch();
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
  initializeFiltersFromQuery();

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
  if (queryDebounceTimeout) clearTimeout(queryDebounceTimeout);
});
</script>

<template>
  <div class="flex flex-col bg-gray-50/60">
    <HeaderComponent
      icon="pi pi-users"
      titre="Trombinoscope"
      :description="selectedAnneeUnivLabel ? `Consultez les profils des étudiants et du personnel — Année ${selectedAnneeUnivLabel}` : 'Consultez les profils des étudiants et du personnel'"
    />

                <!-- KPIs globaux : tous étudiants & personnels, vue globale et directe (défilent au scroll) -->
            <section
              v-if="canViewKpis"
              aria-label="Statistiques globales"
              class="mb-6"
            >
              <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-7 gap-3">
                <!-- Étudiants inscrits -->
                <Kpi
                  label="Étudiants inscrits"
                  :value="studentsKpis.total"
                  icon="pi pi-users"
                  color="blue"
                  :description="`${studentsKpis.active} actifs (${studentsKpis.activePct}%)`"
                  class="cursor-pointer hover:shadow-md hover:border-blue-400/50 transition-all"
                  @click="updateFilters({ mode: 'students' })"
                />

                <!-- Étudiants actifs -->
                <Kpi
                  label="Étudiants actifs"
                  :value="studentsKpis.active"
                  icon="pi pi-user-check"
                  color="emerald"
                  :description="`${studentsKpis.activePct}% en formation`"
                  class="cursor-pointer hover:shadow-md hover:border-emerald-400/50 transition-all"
                  @click="updateFilters({ mode: 'students' })"
                />

                <!-- Semestres actifs -->
                <Kpi
                  label="Semestres actifs"
                  :value="studentsKpis.semestersCount"
                  icon="pi pi-calendar"
                  color="indigo"
                  :description="studentsKpis.semestersList"
                  class="cursor-pointer hover:shadow-md hover:border-indigo-400/50 transition-all"
                  @click="updateFilters({ mode: 'students' })"
                />

                <!-- Effectif personnels -->
                <Kpi
                  label="Effectif personnels"
                  :value="staffKpis.total"
                  icon="pi pi-id-card"
                  color="purple"
                  description="Membres du département"
                  class="cursor-pointer hover:shadow-md hover:border-purple-400/50 transition-all"
                  @click="updateFilters({ mode: 'staff' })"
                />

                <!-- Permanents -->
                <Kpi
                  label="Permanents"
                  :value="staffKpis.permanents"
                  icon="pi pi-check-circle"
                  color="teal"
                  :description="`${staffKpis.permanentsPct}% de l'effectif`"
                  class="cursor-pointer hover:shadow-md hover:border-teal-400/50 transition-all"
                  @click="updateFilters({ mode: 'staff' })"
                />

                <!-- Vacataires -->
                <Kpi
                  label="Vacataires"
                  :value="staffKpis.vacataires"
                  icon="pi pi-briefcase"
                  color="amber"
                  :description="`${staffKpis.vacatairesPct}% de l'effectif`"
                  class="cursor-pointer hover:shadow-md hover:border-amber-400/50 transition-all"
                  @click="updateFilters({ mode: 'staff' })"
                />

                <!-- BIATSS & Autres -->
                <Kpi
                  label="BIATSS & Autres"
                  :value="staffKpis.biatss"
                  icon="pi pi-building"
                  color="violet"
                  :description="`${staffKpis.biatssPct}% de l'effectif`"
                  class="cursor-pointer hover:shadow-md hover:border-violet-400/50 transition-all"
                  @click="updateFilters({ mode: 'staff' })"
                />
              </div>
            </section>

    <div class="flex-1 flex overflow-hidden">
      <!-- Filter Sidebar -->
      <FilterSidebar
        ref="filterSidebarRef"
        :filters="filters"
        :students="students"
        :staff="staff"
        :annee-univ-label="selectedAnneeUnivLabel"
        @update-filters="updateFilters"
        @reset-filters="resetFilters"
        @export-data="exportData"
        @export-trombi-pdf="exportTrombiPdf(toast)"
        @export-listing-excel="exportListingExcel(toast)"
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
          <!-- Loading State with ListSkeleton -->
          <div v-if="loading" class="py-6">
            <ListSkeleton />
          </div>

          <!-- Error State -->
          <div
            v-else-if="error"
            class="bg-red-50 border border-red-200 rounded-2xl p-8 text-center max-w-lg mx-auto my-12 shadow-sm"
          >
            <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center mx-auto mb-3">
              <i class="pi pi-exclamation-triangle text-xl" />
            </div>
            <h3 class="text-base font-semibold text-red-800 mb-1">Erreur de chargement</h3>
            <p class="text-xs text-red-600 mb-4">{{ error }}</p>
            <button
              @click="fetchData"
              class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-semibold shadow-xs transition-colors cursor-pointer"
            >
              Réessayer
            </button>
          </div>

          <template v-else>


            <!-- Empty State -->
            <div
              v-if="currentFilteredData.length === 0"
              class="bg-white border border-gray-200 rounded-2xl p-12 text-center max-w-md mx-auto my-12 shadow-sm"
            >
              <div class="w-16 h-16 rounded-2xl bg-gray-100 border border-gray-200 text-gray-500 flex items-center justify-center mx-auto mb-4 shadow-2xs">
                <i :class="[isFiltered ? 'pi pi-search' : 'pi pi-users', 'text-2xl']" />
              </div>
              <h3 class="text-base font-bold text-gray-900 mb-1.5">
                {{ isFiltered ? 'Aucun résultat trouvé' : (filters.mode === 'students' ? 'Aucun étudiant' : 'Aucun personnel') }}
              </h3>
              <p class="text-xs text-gray-600 mb-6 leading-relaxed">
                <template v-if="isFiltered">
                  Aucun {{ filters.mode === 'students' ? 'étudiant' : 'membre du personnel' }} ne correspond à vos critères de filtrage.
                </template>
                <template v-else>
                  Aucun {{ filters.mode === 'students' ? 'étudiant inscrit pour cette année universitaire' : 'membre du personnel trouvé dans ce département' }}.
                </template>
              </p>
              <button
                v-if="isFiltered"
                @click="resetFilters"
                class="px-4 py-2.5 bg-primary-600 hover:bg-primary-700 active:bg-primary-800 text-white rounded-lg text-xs font-semibold shadow-xs transition-colors inline-flex items-center gap-2 cursor-pointer"
              >
                <i class="pi pi-refresh text-xs" />
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
          </template>
        </div>

        <!-- Footer status bar -->
        <footer class="bg-white border-t border-gray-200 px-6 py-2.5 flex items-center justify-between text-xs text-gray-500">
          <div class="flex items-center gap-4">
            <span class="flex items-center gap-1.5 text-gray-600">
              <kbd class="px-2 py-0.5 bg-gray-100 border border-gray-300 rounded text-[11px] font-mono text-gray-700 font-semibold shadow-2xs">Ctrl+F</kbd> recherche
            </span>
            <span class="flex items-center gap-1.5 text-gray-600">
              <kbd class="px-2 py-0.5 bg-gray-100 border border-gray-300 rounded text-[11px] font-mono text-gray-700 font-semibold shadow-2xs">Ctrl+R</kbd> réinitialiser
            </span>
            <span class="flex items-center gap-1.5 text-gray-600">
              <kbd class="px-2 py-0.5 bg-gray-100 border border-gray-300 rounded text-[11px] font-mono text-gray-700 font-semibold shadow-2xs">Ctrl+E</kbd> exporter CSV
            </span>
          </div>
          <div class="font-semibold text-gray-700 bg-gray-50 px-3 py-1 rounded-full border border-gray-200 shadow-2xs">
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
