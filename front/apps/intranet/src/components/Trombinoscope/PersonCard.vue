<script setup lang="ts">
import type {Staff, Student} from '../types';

interface Props {
  person: Student | Staff;
  mode: 'students' | 'staff';
  viewMode: 'grid' | 'list';
}

defineProps<Props>();

const isStudent = (person: Student | Staff): person is Student => {
  return 'semester' in person;
};

const getStatusBadgeClass = (status: string) => {
  switch (status) {
    case 'active':
      return 'badge-success';
    case 'inactive':
      return 'badge-warning';
    case 'suspended':
      return 'badge-gray';
    case 'Enseignant':
      return 'badge-primary';
    case 'Administratif':
      return 'badge-warning';
    case 'Technique':
      return 'badge-gray';
    case 'Direction':
      return 'badge-success';
    default:
      return 'badge-gray';
  }
};

const getStatusText = (status: string) => {
  switch (status) {
    case 'active':
      return 'Actif';
    case 'inactive':
      return 'Inactif';
    case 'suspended':
      return 'Suspendu';
    default:
      return status;
  }
};
</script>

<template>
  <div
      :class="[
      'card p-4 hover:scale-105 transition-transform duration-200',
      viewMode === 'list' ? 'flex items-center space-x-4' : 'text-center'
    ]"
  >
    <!-- Photo -->
    <div :class="viewMode === 'grid' ? 'mb-4' : 'flex-shrink-0'">
      <img
          :src="person.photo"
          :alt="`Photo de ${person.firstName} ${person.lastName}`"
          :class="[
          'object-cover border-2 border-gray-200',
          viewMode === 'grid' ? 'w-20 h-20 mx-auto rounded-full' : 'w-16 h-16 rounded-full'
        ]"
      />
    </div>

    <!-- Info -->
    <div :class="viewMode === 'list' ? 'flex-1 min-w-0' : ''">
      <!-- Name -->
      <div :class="viewMode === 'grid' ? 'mb-2' : 'mb-1'">
        <h3 :class="viewMode === 'grid' ? 'text-lg font-semibold text-gray-900' : 'text-base font-semibold text-gray-900'">
          {{ person.firstName }} {{ person.lastName }}
        </h3>
        <p :class="viewMode === 'grid' ? 'text-sm text-gray-600' : 'text-xs text-gray-600 truncate'">
          {{ person.email }}
        </p>
      </div>

      <!-- Student Info -->
      <div v-if="isStudent(person)" :class="viewMode === 'grid' ? 'space-y-2' : 'space-y-1'">
        <div class="flex items-center justify-center gap-2" v-if="viewMode === 'grid'">
          <span class="badge badge-primary">S{{ person.semester }}</span>
          <span :class="['badge', getStatusBadgeClass(person.status)]">
            {{ getStatusText(person.status) }}
          </span>
        </div>
        <div class="flex items-center gap-2" v-else>
          <span class="badge badge-primary text-xs">S{{ person.semester }}</span>
          <span :class="['badge text-xs', getStatusBadgeClass(person.status)]">
            {{ getStatusText(person.status) }}
          </span>
        </div>

        <!-- Groups -->
        <div :class="viewMode === 'grid' ? 'space-y-1' : 'mt-1'">
          <div class="flex gap-1">
            <template v-for="(groups, type) in person.groups" :key="type">
              <span
                  v-for="group in groups"
                  :key="group"
                  :class="[
                  'px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-xs w-25',
                  viewMode === 'list' ? 'text-xs' : ''
                ]"
              >
                {{ group }}
              </span>
            </template>
          </div>
        </div>
      </div>

      <!-- Staff Info -->
      <div v-else :class="viewMode === 'grid' ? 'space-y-2' : 'space-y-1'">
        <div class="flex items-center justify-center gap-2" v-if="viewMode === 'grid'">
          <span :class="['badge', getStatusBadgeClass(person.status)]">
            {{ person.status }}
          </span>
        </div>
        <div class="flex items-center gap-2" v-else>
          <span :class="['badge text-xs', getStatusBadgeClass(person.status)]">
            {{ person.status }}
          </span>
        </div>

        <div :class="viewMode === 'grid' ? 'text-center' : ''">
          <p :class="viewMode === 'grid' ? 'text-sm text-gray-600' : 'text-xs text-gray-600'">
            {{ person.position }}
          </p>
          <p :class="viewMode === 'grid' ? 'text-sm text-gray-500' : 'text-xs text-gray-500'">
            {{ person.department }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
