<script setup lang="ts">
import type {Personnel, Etudiant} from '@types';

import { getStatutText, getStatutColor } from "@utils"

interface Props {
  person: Etudiant | Personnel;
  mode: 'students' | 'staff';
  viewMode: 'grid' | 'list';
}

defineProps<Props>();

const isStudent = (person: Etudiant | Personnel): person is Etudiant => {
  return 'scolarites' in person;
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
          :src="person.photoName"
          :alt="`Photo de ${person.prenom} ${person.nom}`"
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
          {{ person.prenom }} {{ person.nom }}
        </h3>
        <p :class="viewMode === 'grid' ? 'text-sm text-gray-600' : 'text-xs text-gray-600 truncate'">
          {{ person.mailUniv }}
        </p>
      </div>

      <!-- Student Info -->
      <div v-if="isStudent(person)" :class="viewMode === 'grid' ? 'space-y-2' : 'space-y-1'">
        <div class="flex items-center justify-center gap-2" v-if="viewMode === 'grid'">
          <span class="badge badge-primary">S{{ person.scolarite }}</span><!-- todo: semestre -->
        </div>
        <div class="flex items-center gap-2" v-else>
          <span class="badge badge-primary text-xs">S{{ person.scolarite }}</span><!-- todo: semestre -->
        </div>

        <!-- Groups -->
        <div :class="viewMode === 'grid' ? 'space-y-1' : 'mt-1'">
          <div class="flex gap-1">
            <template v-for="(groups, type) in person.groupes" :key="type">
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
          <Badge :severity="getStatutColor(person.statut)">
            {{ getStatutText(person.statut) }}
          </Badge>
        </div>
        <div class="flex items-center gap-2" v-else>
          <Badge :severity="getStatutColor(person.statut)">
            {{ getStatutText(person.statut) }}
          </Badge>
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
