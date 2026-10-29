<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import type { FilterState } from '@types';
import { getDepartementSemestresService, getGroupesSemestreService } from '@requests';
import { useUsersStore } from '@stores';

const usersStore = useUsersStore();
const departementId = computed(() => usersStore.departementDefaut?.id);

interface Props {
  filters: FilterState;
  students: any[];
  staff: any[];
  anneeUnivLabel?: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  updateFilters: [filters: Partial<FilterState>];
  resetFilters: [];
  exportData: [];
  exportTrombiPdf: [];
  exportListingExcel: [];
  exportListingPdf: [];
}>();

const loaded = ref(false);
const semesters = ref<Array<{ id: number; libelle: string; actif?: boolean }>>([]);
const availableGroups = ref<Array<{ id: number; libelle: string; type: string }>>([]);
const loadingGroups = ref(false);

const loadSemesters = async () => {
  if (!departementId.value) return;
  try {
    const res = await getDepartementSemestresService(departementId.value, true);
    const list = Array.isArray(res) ? res : (res?.member || []);
    // Strictly filter active semesters
    semesters.value = list.filter((sem: any) => sem.actif === true);
  } catch (err) {
    console.error('Erreur chargement semestres:', err);
    semesters.value = [];
  } finally {
    loaded.value = true;
  }
};

onMounted(async () => {
  await loadSemesters();
});

watch(departementId, async (newVal, oldVal) => {
  if (newVal && newVal !== oldVal) {
    await loadSemesters();
  }
});

const updateAvailableGroups = async () => {
  const currentSem = props.filters.studentFilters.semester;
  const currentType = props.filters.studentFilters.groupType;

  if (!currentSem || !currentType) {
    availableGroups.value = [];
    return;
  }

  loadingGroups.value = true;
  try {
    const res = await getGroupesSemestreService(currentSem);
    const member: any[] = res?.member || res?.data?.member || (Array.isArray(res) ? res : []);
    const filtered = member.filter((g: any) => g.type === currentType);
    filtered.sort((a: any, b: any) => (a.libelle || '').localeCompare(b.libelle || ''));
    availableGroups.value = filtered.map((g: any) => ({
      id: g.id,
      libelle: g.libelle,
      type: g.type,
    }));
  } catch (err) {
    console.error('Erreur chargement groupes:', err);
    availableGroups.value = [];
  } finally {
    loadingGroups.value = false;
  }
};

const availableStaffStatuts = computed(() => {
  const statuts = props.staff.map((s: any) => s.statut).filter(Boolean);
  return [...new Set(statuts)].sort();
});

const hasActiveFilters = computed(() => {
  if (props.filters.searchTerm) return true;
  if (props.filters.mode === 'students') {
    return Boolean(
        props.filters.studentFilters.semester ||
        props.filters.studentFilters.groupType ||
        props.filters.studentFilters.group
    );
  }
  return Boolean(props.filters.staffFilters.statut || props.filters.staffFilters.department);
});

const searchInputRef = ref<HTMLInputElement | null>(null);

const focusSearch = () => {
  searchInputRef.value?.focus();
};

defineExpose({
  focusSearch,
});

const updateMode = (mode: 'students' | 'staff') => {
  emit('updateFilters', {
    mode,
    searchTerm: '',
    studentFilters: {
      semester: null,
      groupType: null,
      group: null,
    },
    staffFilters: {
      statut: null,
      department: null,
    },
  });
  availableGroups.value = [];
};

const updateStudentSemester = (semester: number | null) => {
  emit('updateFilters', {
    studentFilters: {
      ...props.filters.studentFilters,
      semester,
      groupType: null,
      group: null,
    },
  });
  availableGroups.value = [];
};

const updateStudentGroupType = (groupType: 'CM' | 'TD' | 'TP' | null) => {
  emit('updateFilters', {
    studentFilters: {
      ...props.filters.studentFilters,
      groupType,
      group: null,
    },
  });
  if (groupType) {
    updateAvailableGroups();
  } else {
    availableGroups.value = [];
  }
};

const updateStudentGroup = (group: number | string | null) => {
  emit('updateFilters', {
    studentFilters: {
      ...props.filters.studentFilters,
      group,
    },
  });
};

const updateStaffStatut = (statut: string | null) => {
  emit('updateFilters', {
    staffFilters: {
      ...props.filters.staffFilters,
      statut,
    },
  });
};

const updateSearch = (searchTerm: string) => {
  emit('updateFilters', { searchTerm });
};

const updateSortBy = (sortBy: any) => {
  emit('updateFilters', { sortBy });
};

const toggleSortOrder = () => {
  const nextOrder = props.filters.sortOrder === 'asc' ? 'desc' : 'asc';
  emit('updateFilters', { sortOrder: nextOrder });
};

const updateViewMode = (viewMode: 'grid' | 'list') => {
  emit('updateFilters', { viewMode });
};
</script>

<template>
  <aside class="w-80 bg-white border-r border-gray-200 flex flex-col h-full shadow-sm flex-shrink-0">
    <!-- Header -->
    <div class="p-5 border-b border-gray-100 flex items-center justify-between">
      <div class="flex items-center gap-2.5">
        <div class="w-8 h-8 rounded-lg bg-primary-50 text-primary-600 flex items-center justify-center border border-primary-200/60 shadow-2xs">
          <i class="pi pi-filter text-sm"></i>
        </div>
        <div>
          <h2 class="text-base font-bold text-gray-900 leading-tight">Filtres</h2>
          <span v-if="anneeUnivLabel" class="text-xs text-gray-500 font-semibold">
            {{ anneeUnivLabel }}
          </span>
        </div>
      </div>
      <button
          v-if="hasActiveFilters"
          @click="emit('resetFilters')"
          class="text-xs text-primary-700 hover:text-primary-900 bg-primary-50 hover:bg-primary-100 px-2.5 py-1 rounded-lg border border-primary-200 font-semibold inline-flex items-center gap-1.5 transition-colors cursor-pointer"
      >
        <i class="pi pi-refresh text-[10px]"></i>
        <span>Réinitialiser</span>
      </button>
    </div>

    <!-- Scrollable content -->
    <div class="flex-1 overflow-y-auto p-5 space-y-6">
      <!-- Mode Toggle (Segmented control) -->
      <div>
        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
          Affichage
        </label>
        <div class="grid grid-cols-2 p-1 bg-gray-100 rounded-xl border border-gray-200/80">
          <button
              @click="updateMode('students')"
              type="button"
              :class="[
              'flex items-center justify-center gap-2 py-2 px-3 rounded-lg text-xs transition-all duration-200 cursor-pointer',
              filters.mode === 'students'
                ? 'bg-white text-primary-700 shadow-xs font-bold border border-gray-200/60'
                : 'text-gray-600 hover:text-gray-900 font-semibold'
            ]"
          >
            <i class="pi pi-users text-xs"></i>
            <span>Étudiants</span>
          </button>
          <button
              @click="updateMode('staff')"
              type="button"
              :class="[
              'flex items-center justify-center gap-2 py-2 px-3 rounded-lg text-xs transition-all duration-200 cursor-pointer',
              filters.mode === 'staff'
                ? 'bg-white text-primary-700 shadow-xs font-bold border border-gray-200/60'
                : 'text-gray-600 hover:text-gray-900 font-semibold'
            ]"
          >
            <i class="pi pi-id-card text-xs"></i>
            <span>Personnel</span>
          </button>
        </div>
      </div>

      <!-- Search Input -->
      <div>
        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
          Recherche
        </label>
        <div class="relative">
          <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-xs"></i>
          <input
              ref="searchInputRef"
              type="text"
              :value="filters.searchTerm"
              @input="updateSearch(($event.target as HTMLInputElement).value)"
              :placeholder="filters.mode === 'students' ? 'Nom, prénom, email, N°...' : 'Nom, prénom, bureau, matière...'"
              class="w-full pl-8 pr-8 py-2 text-xs bg-white border border-gray-300 rounded-lg text-gray-900 font-medium placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 shadow-2xs transition-all"
          />
          <button
              v-if="filters.searchTerm"
              @click="updateSearch('')"
              type="button"
              class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700 cursor-pointer"
          >
            <i class="pi pi-times-circle text-xs"></i>
          </button>
        </div>
      </div>

      <!-- Student Specific Filters -->
      <div v-if="filters.mode === 'students'" class="space-y-4 pt-2 border-t border-gray-100">
        <!-- Semester Selection -->
        <div>
          <label class="block text-xs font-bold text-gray-700 mb-1.5">
            Semestre
          </label>
          <select
              :value="filters.studentFilters.semester || ''"
              @change="updateStudentSemester($event.target.value ? Number($event.target.value) : null)"
              class="w-full text-xs py-2 px-3 bg-white border border-gray-300 rounded-lg text-gray-900 font-medium focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 shadow-2xs transition-all cursor-pointer"
          >
            <option value="">Tous les semestres</option>
            <option v-for="sem in semesters" :key="sem.id" :value="sem.id">
              {{ sem.libelle }}
            </option>
          </select>
        </div>

        <!-- Group Type Selection -->
        <div v-if="filters.studentFilters.semester" class="space-y-2">
          <label class="block text-xs font-bold text-gray-700 mb-1">
            Type de groupe
          </label>
          <div class="grid grid-cols-4 gap-1 p-1 bg-gray-100 rounded-lg border border-gray-200/80">
            <button
                type="button"
                @click="updateStudentGroupType(null)"
                :class="[
                'py-1.5 text-xs font-semibold rounded-md text-center transition-all cursor-pointer',
                !filters.studentFilters.groupType
                  ? 'bg-white text-primary-700 shadow-xs font-bold border border-gray-200/60'
                  : 'text-gray-700 hover:text-gray-900 hover:bg-gray-200/60'
              ]"
            >
              Tous
            </button>
            <button
                v-for="gt in (['CM', 'TD', 'TP'] as const)"
                :key="gt"
                type="button"
                @click="updateStudentGroupType(gt)"
                :class="[
                'py-1.5 text-xs font-semibold rounded-md text-center transition-all cursor-pointer',
                filters.studentFilters.groupType === gt
                  ? 'bg-white text-primary-700 shadow-xs font-bold border border-gray-200/60'
                  : 'text-gray-700 hover:text-gray-900 hover:bg-gray-200/60'
              ]"
            >
              {{ gt }}
            </button>
          </div>
        </div>

        <!-- Specific Group Selection -->
        <div v-if="filters.studentFilters.groupType">
          <label class="block text-xs font-bold text-gray-700 mb-1.5 flex items-center justify-between">
            <span>Groupe spécifique</span>
            <span v-if="loadingGroups" class="text-[10px] text-gray-500 font-normal">Chargement...</span>
          </label>
          <select
              :value="filters.studentFilters.group || ''"
              @change="updateStudentGroup($event.target.value || null)"
              class="w-full text-xs py-2 px-3 bg-white border border-gray-300 rounded-lg text-gray-900 font-medium focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 shadow-2xs transition-all cursor-pointer"
          >
            <option value="">Tous les groupes {{ filters.studentFilters.groupType }}</option>
            <option v-for="grp in availableGroups" :key="grp.id" :value="grp.id">
              {{ grp.libelle }}
            </option>
          </select>
        </div>
      </div>

      <!-- Staff Specific Filters -->
      <div v-if="filters.mode === 'staff'" class="space-y-4 pt-2 border-t border-gray-100">
        <div>
          <label class="block text-xs font-bold text-gray-700 mb-1.5">
            Statut
          </label>
          <select
              :value="filters.staffFilters.statut || ''"
              @change="updateStaffStatut($event.target.value || null)"
              class="w-full text-xs py-2 px-3 bg-white border border-gray-300 rounded-lg text-gray-900 font-medium focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 shadow-2xs transition-all cursor-pointer"
          >
            <option value="">Tous les statuts</option>
            <option v-for="st in availableStaffStatuts" :key="st" :value="st">
              {{ st }}
            </option>
          </select>
        </div>
      </div>

      <!-- Display Options -->
      <div class="space-y-4 pt-2 border-t border-gray-100">
        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
          Affichage & Tri
        </label>

        <!-- Sort By + Order -->
        <div>
          <label class="block text-xs font-bold text-gray-700 mb-1.5">
            Trier par
          </label>
          <div class="flex items-center gap-2">
            <select
                :value="filters.sortBy"
                @change="updateSortBy($event.target.value)"
                class="flex-1 text-xs py-2 px-3 bg-white border border-gray-300 rounded-lg text-gray-900 font-medium focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 shadow-2xs transition-all cursor-pointer"
            >
              <option value="nom">Nom</option>
              <option value="prenom">Prénom</option>
              <option v-if="filters.mode === 'students'" value="semester">Semestre</option>
              <option v-if="filters.mode === 'students'" value="groupe">Groupe</option>
              <option v-if="filters.mode === 'staff'" value="statut">Statut</option>
            </select>

            <button
                @click="toggleSortOrder"
                type="button"
                :title="filters.sortOrder === 'asc' ? 'Ordre croissant (A-Z)' : 'Ordre décroissant (Z-A)'"
                class="p-2 bg-gray-100 hover:bg-gray-200 border border-gray-300 text-gray-800 rounded-lg transition-colors flex items-center justify-center cursor-pointer shadow-2xs"
            >
              <i :class="[filters.sortOrder === 'asc' ? 'pi pi-sort-amount-down-alt' : 'pi pi-sort-amount-up', 'text-xs']"></i>
            </button>
          </div>
        </div>

        <!-- Grid vs List Mode -->
        <div>
          <label class="block text-xs font-bold text-gray-700 mb-1.5">
            Format de vue
          </label>
          <div class="grid grid-cols-2 p-1 bg-gray-100 rounded-lg border border-gray-200/80">
            <button
                @click="updateViewMode('grid')"
                type="button"
                :class="[
                'flex items-center justify-center gap-1.5 py-1.5 text-xs font-semibold rounded-md transition-all cursor-pointer',
                filters.viewMode === 'grid'
                  ? 'bg-white text-primary-700 shadow-xs font-bold border border-gray-200/60'
                  : 'text-gray-700 hover:text-gray-900 hover:bg-gray-200/60'
              ]"
            >
              <i class="pi pi-th-large text-xs"></i>
              <span>Grille</span>
            </button>
            <button
                @click="updateViewMode('list')"
                type="button"
                :class="[
                'flex items-center justify-center gap-1.5 py-1.5 text-xs font-semibold rounded-md transition-all cursor-pointer',
                filters.viewMode === 'list'
                  ? 'bg-white text-primary-700 shadow-xs font-bold border border-gray-200/60'
                  : 'text-gray-700 hover:text-gray-900 hover:bg-gray-200/60'
              ]"
            >
              <i class="pi pi-bars text-xs"></i>
              <span>Liste</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Exports Section -->
      <div class="space-y-2.5 pt-2 border-t border-gray-100">
        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
          Exports
        </label>

        <!-- Gotenberg PDF Trombinoscope export (if semester selected) -->
        <button
            v-if="filters.mode === 'students' && filters.studentFilters.semester"
            type="button"
            @click="emit('exportTrombiPdf')"
            class="w-full flex items-center justify-center gap-2 py-2 px-3 rounded-xl text-xs font-semibold bg-red-50 text-red-700 border border-red-200 hover:bg-red-600 hover:text-white hover:border-red-600 shadow-2xs transition-all duration-200 cursor-pointer"
        >
          <i class="pi pi-file-pdf text-xs"></i>
          <span>Trombinoscope PDF</span>
        </button>

        <!-- Listing Excel export (if semester selected) -->
        <button
            v-if="filters.mode === 'students' && filters.studentFilters.semester"
            type="button"
            @click="emit('exportListingExcel')"
            class="w-full flex items-center justify-center gap-2 py-2 px-3 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-300 hover:bg-emerald-600 hover:text-white hover:border-emerald-600 shadow-2xs transition-all duration-200 cursor-pointer"
        >
          <i class="pi pi-file-excel text-xs"></i>
          <span>Listing Émargement (Excel)</span>
        </button>

        <!-- CSV Export (Current view) -->
        <button
            type="button"
            @click="emit('exportData')"
            class="w-full flex items-center justify-center gap-2 py-2 px-3 rounded-xl text-xs font-semibold bg-gray-100 text-gray-800 border border-gray-300 hover:bg-gray-200 shadow-2xs transition-all duration-200 cursor-pointer"
        >
          <i class="pi pi-download text-xs text-gray-600"></i>
          <span>Exporter la vue (CSV)</span>
        </button>
      </div>
    </div>
  </aside>
</template>
