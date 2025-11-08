<script setup lang="ts">
import {computed} from 'vue';
import type {Etudiant, FilterState, Personnel} from '@types';

interface Props {
  students: Etudiant[];
  staff: Personnel[];
  filteredStudents: Etudiant[];
  filteredStaff: Personnel[];
  filters: FilterState;
}

const props = defineProps<Props>();

const totalCount = computed(() => {
  return props.filters.mode === 'students' ? props.students.length : props.staff.length;
});

const filteredCount = computed(() => {
  return props.filters.mode === 'students' ? props.filteredStudents.length : props.filteredStaff.length;
});

const studentStats = computed(() => {
  if (props.filters.mode !== 'students') return null;

  const semesters = props.students.reduce((acc, student) => {
    acc[student.semester] = (acc[student.semester] || 0) + 1;
    return acc;
  }, {} as Record<number, number>);

  const statuses = props.students.reduce((acc, student) => {
    acc[student.status] = (acc[student.status] || 0) + 1;
    return acc;
  }, {} as Record<string, number>);

  return {semesters, statuses};
});

const staffStats = computed(() => {
  if (props.filters.mode !== 'staff') return null;

  const statuses = props.staff.reduce((acc, member) => {
    const statut = member.statut;
    const group =
        ['MCF', 'PRCE', 'PRAG', 'contractuel'].includes(statut) ? 'permanent' :
            ['VAC', 'ATER', 'vacataire'].includes(statut) ? 'vacataires' :
                'adm';
    acc[group] = (acc[group] || 0) + 1;
    return acc;
  }, {} as Record<string, number>);

  return {statuses};
});

</script>

<template>
  <div class="bg-white border-b border-primary p-4">
    <div class="flex items-center justify-between">
      <!-- Count Summary -->
      <div class="flex items-center space-x-4">
        <div class="flex items-center space-x-2">
          <span class="text-2xl font-bold text-gray-900">{{ filteredCount }}</span>
          <span class="text-sm text-gray-500">/ {{ totalCount }}</span>
          <span class="text-sm text-gray-700">
            {{ filters.mode === 'students' ? 'étudiants' : 'personnels' }}
          </span>
        </div>

        <div v-if="filteredCount < totalCount" class="flex items-center space-x-1">
          <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.414A1 1 0 013 6.586V4z"/>
          </svg>
          <span class="text-sm text-primary-600">Filtré</span>
        </div>
      </div>

      <!-- Quick Stats -->
      <div class="flex items-center space-x-6">
        <div v-if="filters.mode === 'students' && studentStats" class="flex items-center space-x-4">
          <div class="text-center">
            <div class="text-sm font-medium text-gray-900">{{ Object.keys(studentStats.semesters).length }}</div>
            <div class="text-xs text-gray-500">Semestres</div>
          </div>
          <div class="text-center">
            <div class="text-sm font-medium text-success-600">{{ studentStats.statuses.active || 0 }}</div>
            <div class="text-xs text-gray-500">Actifs</div>
          </div>
          <div v-if="studentStats.statuses.suspended" class="text-center">
            <div class="text-sm font-medium text-warning-600">{{ studentStats.statuses.suspended }}</div>
            <div class="text-xs text-gray-500">Suspendus</div>
          </div>
        </div>

        <div v-if="filters.mode === 'staff' && staffStats" class="flex items-center space-x-4">
          <div class="text-center" v-for="(statut, key) in staffStats.statuses" :key="key">
            <div class="text-sm font-medium text-primary-600">{{ staffStats.statuses[key] || 0 }}</div>
            <div class="text-xs text-gray-500">{{ key }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
