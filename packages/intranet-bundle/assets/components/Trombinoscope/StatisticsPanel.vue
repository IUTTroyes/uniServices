<script setup lang="ts">
import { computed } from 'vue';
import type { FilterState } from '@types';

interface Props {
  students: any[];
  staff: any[];
  filteredStudents: any[];
  filteredStaff: any[];
  filters: FilterState;
  anneeUnivLabel?: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  resetFilters: [];
}>();

const totalCount = computed(() => {
  return props.filters.mode === 'students' ? props.students.length : props.staff.length;
});

const filteredCount = computed(() => {
  return props.filters.mode === 'students' ? props.filteredStudents.length : props.filteredStaff.length;
});

const isFiltered = computed(() => {
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

const activeFiltersList = computed(() => {
  const list: string[] = [];
  if (props.filters.searchTerm) {
    list.push(`Recherche: "${props.filters.searchTerm}"`);
  }
  if (props.filters.mode === 'students') {
    if (props.filters.studentFilters.semester) {
      const match = props.students.find((s: any) => s.semestre === props.filters.studentFilters.semester);
      list.push(match?.semestreLibelle ? `Semestre: ${match.semestreLibelle}` : `Semestre #${props.filters.studentFilters.semester}`);
    }
    if (props.filters.studentFilters.groupType) {
      list.push(`Type: ${props.filters.studentFilters.groupType}`);
    }
    if (props.filters.studentFilters.group) {
      list.push(`Groupe: ${props.filters.studentFilters.group}`);
    }
  } else {
    if (props.filters.staffFilters.statut) {
      list.push(`Statut: ${props.filters.staffFilters.statut}`);
    }
    if (props.filters.staffFilters.department) {
      list.push(`Département: ${props.filters.staffFilters.department}`);
    }
  }
  return list;
});

const studentStats = computed(() => {
  if (props.filters.mode !== 'students') return null;

  const semesters = props.students.reduce((acc, student: any) => {
    const sem = student.semestreLibelle || (student.semestre ? `S${student.semestre}` : 'Non assigné');
    acc[sem] = (acc[sem] || 0) + 1;
    return acc;
  }, {} as Record<string, number>);

  const activeCount = props.students.filter((s: any) => s.status === 'active').length;

  return { semesters, activeCount };
});

const staffStats = computed(() => {
  if (props.filters.mode !== 'staff') return null;

  const categories = props.staff.reduce((acc, member) => {
    const statut = member.statut || 'Autre';
    const group =
        ['MCF', 'PU', 'PRCE', 'PRAG', 'contractuel'].includes(statut) ? 'Permanents' :
            ['VAC', 'ATER', 'vacataire', 'ENSAM', 'DO'].includes(statut) ? 'Vacataires' :
                'Personnels';
    acc[group] = (acc[group] || 0) + 1;
    return acc;
  }, {} as Record<string, number>);

  return { categories };
});
</script>

<template>
  <div class="bg-white border-b border-gray-200 px-6 py-3.5 flex flex-wrap items-center justify-between gap-4">
    <!-- Left: Count summary & filter tag chips -->
    <div class="flex items-center flex-wrap gap-3">
      <div class="flex items-baseline gap-2">
        <span class="text-2xl font-bold text-gray-900 tracking-tight">{{ filteredCount }}</span>
        <span class="text-sm font-medium text-gray-400">/ {{ totalCount }}</span>
        <span class="text-sm font-medium text-gray-700">
          {{ filters.mode === 'students' ? (filteredCount > 1 ? 'étudiants' : 'étudiant') : 'personnel' }}
        </span>
        <span
            v-if="anneeUnivLabel && filters.mode === 'students'"
            class="hidden md:inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-gray-100 text-gray-600 border border-gray-200"
        >
          {{ anneeUnivLabel }}
        </span>
      </div>

      <!-- Active filters tags -->
      <div v-if="isFiltered" class="flex items-center gap-1.5 flex-wrap pl-2 border-l border-gray-200">
        <span
            v-for="flt in activeFiltersList"
            :key="flt"
            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-primary-50 text-primary-700 border border-primary-200"
        >
          {{ flt }}
        </span>

        <button
            @click="emit('resetFilters')"
            class="text-xs text-gray-400 hover:text-red-600 transition-colors inline-flex items-center gap-1 ml-1"
            title="Effacer les filtres"
        >
          <i class="pi pi-times-circle text-xs"></i>
          <span>Effacer</span>
        </button>
      </div>
    </div>

    <!-- Right: Quick breakdown stats -->
    <div class="flex items-center gap-4 flex-wrap">
      <!-- Students Stats -->
      <div v-if="filters.mode === 'students' && studentStats" class="flex items-center gap-4 text-xs">
        <div class="flex items-center gap-1.5 text-gray-500">
          <i class="pi pi-calendar text-gray-400"></i>
          <span class="font-medium text-gray-700">{{ Object.keys(studentStats.semesters).length }}</span>
          <span>semestres</span>
        </div>
        <div class="flex items-center gap-1.5 text-emerald-600">
          <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
          <span class="font-semibold text-gray-800">{{ studentStats.activeCount }}</span>
          <span class="text-gray-500">actifs</span>
        </div>
      </div>

      <!-- Staff Stats -->
      <div v-if="filters.mode === 'staff' && staffStats" class="flex items-center gap-3 text-xs">
        <div
            v-for="(count, category) in staffStats.categories"
            :key="category"
            class="flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-gray-50 border border-gray-200/80"
        >
          <span class="text-gray-500">{{ category }}:</span>
          <span class="font-semibold text-gray-800">{{ count }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
