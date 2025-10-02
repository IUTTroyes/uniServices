<template>
  <div class="flex items-center bg-gray-100 rounded-lg p-1">
    <button
      @click="$emit('changeView', 'grid')"
      :class="[
        'flex items-center px-3 py-1 rounded-md text-sm font-medium transition-colors',
        viewMode === 'grid'
          ? 'bg-white text-gray-900 shadow-sm'
          : 'text-gray-500 hover:text-gray-700'
      ]"
    >
      <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
      </svg>
      Grille
    </button>
    
    <button
      @click="$emit('changeView', 'list')"
      :class="[
        'flex items-center px-3 py-1 rounded-md text-sm font-medium transition-colors',
        viewMode === 'list'
          ? 'bg-white text-gray-900 shadow-sm'
          : 'text-gray-500 hover:text-gray-700'
      ]"
    >
      <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
      </svg>
      Liste
    </button>
  </div>
</template>

<script setup lang="ts">
import type { ViewMode } from '@/types';

interface Props {
  viewMode: ViewMode;
}

defineProps<Props>();

defineEmits<{
  changeView: [mode: ViewMode];
}>();
</script>