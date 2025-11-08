<script setup lang="ts">
import { computed } from 'vue';
import type { FilterState, Student, Staff } from '../types';

interface Props {
  filters: FilterState;
  students: Student[];
  staff: Staff[];
}

const props = defineProps<Props>();

const emit = defineEmits<{
  updateFilters: [filters: Partial<FilterState>];
  resetFilters: [];
  exportData: [];
}>();

// Computed properties for dynamic options
const availableSemesters = computed(() => {
  const semesters = [...new Set(props.students.map(s => s.semester))].sort();
  return semesters;
});

const availableGroups = computed(() => {
  if (!props.filters.studentFilters.semester || !props.filters.studentFilters.groupType) {
    return [];
  }

  const studentsInSemester = props.students.filter(s => s.semester === props.filters.studentFilters.semester);
  const groups = new Set<string>();

  studentsInSemester.forEach(student => {
    const groupType = props.filters.studentFilters.groupType!;
    student.groups[groupType].forEach(group => groups.add(group));
  });

  return Array.from(groups).sort();
});

const availableStaffStatuts = computed(() => {
  return [...new Set(props.staff.map(s => s.statut))].sort();
});

const availableDepartments = computed(() => {
  return [...new Set(props.staff.map(s => s.department))].sort();
});

const updateMode = (mode: 'students' | 'staff') => {
  emit('updateFilters', {
    mode,
    studentFilters: {
      semester: null,
      groupType: null,
      group: null
    },
    staffFilters: {
      statut: null,
      department: null
    }
  });
};

const updateStudentSemester = (semester: number | null) => {
  emit('updateFilters', {
    studentFilters: {
      ...props.filters.studentFilters,
      semester,
      groupType: null,
      group: null
    }
  });
};

const updateStudentGroupType = (groupType: 'CM' | 'TD' | 'TP' | null) => {
  emit('updateFilters', {
    studentFilters: {
      ...props.filters.studentFilters,
      groupType,
      group: null
    }
  });
};

const updateStudentGroup = (group: string | null) => {
  emit('updateFilters', {
    studentFilters: {
      ...props.filters.studentFilters,
      group
    }
  });
};

const updateStaffStatut = (statut: string | null) => {
  emit('updateFilters', {
    staffFilters: {
      ...props.filters.staffFilters,
      statut
    }
  });
};

const updateStaffDepartment = (department: string | null) => {
  emit('updateFilters', {
    staffFilters: {
      ...props.filters.staffFilters,
      department
    }
  });
};

const updateSearch = (searchTerm: string) => {
  emit('updateFilters', { searchTerm });
};

const updateSort = (sortBy: 'nom' | 'prenom' | 'semester' | 'statut') => {
  const sortOrder = props.filters.sortBy === sortBy && props.filters.sortOrder === 'asc' ? 'desc' : 'asc';
  emit('updateFilters', { sortBy, sortOrder });
};

const updateViewMode = (viewMode: 'grid' | 'list') => {
  emit('updateFilters', { viewMode });
};
</script>

<template>
  <div class="bg-white w-80 border-r border-gray-200 p-6 overflow-y-auto">
    <div class="space-y-6">
      <!-- Header -->
      <div class="text-center">
        <h2 class="text-xl font-semibold text-gray-900 mb-2">Filtrer et rechercher</h2>
      </div>

      <!-- Mode Selection -->
      <div class="filter-section">
        <h3 class="text-sm font-medium text-gray-900 mb-3">Mode d'affichage</h3>
        <div class="flex bg-gray-100 rounded-lg p-1">
          <button
              @click="updateMode('students')"
              :class="[
              'flex-1 text-sm font-medium py-2 px-3 rounded-md transition-colors duration-200',
              filters.mode === 'students'
                ? 'bg-white text-primary-600 shadow-sm'
                : 'text-gray-600 hover:text-gray-800'
            ]"
          >
            Étudiants
          </button>
          <button
              @click="updateMode('staff')"
              :class="[
              'flex-1 text-sm font-medium py-2 px-3 rounded-md transition-colors duration-200',
              filters.mode === 'staff'
                ? 'bg-white text-primary-600 shadow-sm'
                : 'text-gray-600 hover:text-gray-800'
            ]"
          >
            Personnel
          </button>
        </div>
      </div>

      <!-- Search -->
      <div class="filter-section">
        <h3 class="text-sm font-medium text-gray-900 mb-3">Recherche</h3>
        <div class="relative">
          <input
              type="text"
              :value="filters.searchTerm"
              @input="updateSearch(($event.target as HTMLInputElement).value)"
              placeholder="Nom, prénom..."
              class="input-field pl-10"
          />
          <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
      </div>

      <!-- Student Filters -->
      <div v-if="filters.mode === 'students'" class="filter-section">
        <h3 class="text-sm font-medium text-gray-900 mb-3">Filtres étudiants</h3>
        <div class="space-y-4">
          <!-- Semester -->
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Semestre</label>
            <select
                :value="filters.studentFilters.semester || ''"
                @change="updateStudentSemester($event.target.value ? parseInt($event.target.value) : null)"
                class="input-field text-sm"
            >
              <option value="">Tous les semestres</option>
              <option v-for="semester in availableSemesters" :key="semester" :value="semester">
                Semestre {{ semester }}
              </option>
            </select>
          </div>

          <!-- Group Type -->
          <div v-if="filters.studentFilters.semester">
            <label class="block text-xs font-medium text-gray-700 mb-1">Type de groupe</label>
            <select
                :value="filters.studentFilters.groupType || ''"
                @change="updateStudentGroupType($event.target.value || null)"
                class="input-field text-sm"
            >
              <option value="">Tous les types</option>
              <option value="CM">CM</option>
              <option value="TD">TD</option>
              <option value="TP">TP</option>
            </select>
          </div>

          <!-- Specific Group -->
          <div v-if="filters.studentFilters.groupType && availableGroups.length > 0">
            <label class="block text-xs font-medium text-gray-700 mb-1">Groupe spécifique</label>
            <select
                :value="filters.studentFilters.group || ''"
                @change="updateStudentGroup($event.target.value || null)"
                class="input-field text-sm"
            >
              <option value="">Tous les groupes</option>
              <option v-for="group in availableGroups" :key="group" :value="group">
                {{ group }}
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- Staff Filters -->
      <div v-if="filters.mode === 'staff'" class="filter-section">
        <h3 class="text-sm font-medium text-gray-900 mb-3">Filtres personnel</h3>
        <div class="space-y-4">
          <!-- statut -->
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Statut</label>
            <select
                :value="filters.staffFilters.statut || ''"
                @change="updateStaffStatut($event.target.value || null)"
                class="input-field text-sm"
            >
              <option value="">Tous les statuts</option>
              <option v-for="statut in availableStaffStatuts" :key="statut" :value="statut">
                {{ statut }}
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- Sort & View Options -->
      <div class="filter-section">
        <h3 class="text-sm font-medium text-gray-900 mb-3">Options d'affichage</h3>
        <div class="space-y-4">
          <!-- Sort -->
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Trier par</label>
            <select
                :value="filters.sortBy"
                @change="updateSort($event.target.value as any)"
                class="input-field text-sm w-full"
            >
              <option value="nom">Nom</option>
              <option value="prenom">Prénom</option>
              <option v-if="filters.mode === 'students'" value="semester">Semestre</option>
              <option v-if="filters.mode === 'staff'" value="statut">Statut</option>
            </select>
          </div>

          <!-- View Mode -->
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Affichage</label>
            <div class="flex bg-gray-100 rounded-lg p-1">
              <button
                  @click="updateViewMode('grid')"
                  :class="[
                  'flex-1 text-xs font-medium py-2 px-3 rounded-md transition-colors duration-200',
                  filters.viewMode === 'grid'
                    ? 'bg-white text-primary-600 shadow-sm'
                    : 'text-gray-600 hover:text-gray-800'
                ]"
              >
                Grille
              </button>
              <button
                  @click="updateViewMode('list')"
                  :class="[
                  'flex-1 text-xs font-medium py-2 px-3 rounded-md transition-colors duration-200',
                  filters.viewMode === 'list'
                    ? 'bg-white text-primary-600 shadow-sm'
                    : 'text-gray-600 hover:text-gray-800'
                ]"
              >
                Liste
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="filter-section">
        <div class="space-y-3">
          <Button
              severity="warn"
              @click="emit('resetFilters')"
              class="w-full btn-secondary text-sm"
          >
            Réinitialiser les filtres
          </Button>
          <Button
              severity="info"
              @click="emit('exportData')"
              class="w-full btn-primary text-sm"
          >
            Exporter les données
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>
