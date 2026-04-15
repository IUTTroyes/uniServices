<template>
  <div class="card p-4 hover:shadow-md transition-shadow duration-200 group">
    <div class="flex items-start justify-between mb-3">
      <div class="flex items-center space-x-3">
        <div :class="['text-2xl', getFileIconColor(document.type)]">
          {{ getFileIcon(document.type) }}
        </div>
        <div class="flex-1 min-w-0">
          <h3 class="font-medium text-gray-900 truncate">
            {{ document.title }}
          </h3>
          <p class="text-sm text-gray-500 truncate">
            {{ getFileExtension(document.type) }} • {{ formatFileSize(document.size) }}
          </p>
        </div>
      </div>

      <button
        @click="$emit('toggleFavorite')"
        class="opacity-0 group-hover:opacity-100 transition-opacity p-1 hover:bg-gray-100 rounded"
      >
        <span :class="document.isFavorite ? 'text-yellow-500' : 'text-gray-400'">
          {{ document.isFavorite ? '⭐' : '☆' }}
        </span>
      </button>
    </div>

    <div class="space-y-2">
      <div class="flex items-center text-sm text-gray-500">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
        </svg>
        {{ document.author }}
      </div>

      <div class="flex items-center text-sm text-gray-500">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        {{ formatDate(document.lastModified) }}
      </div>

      <div class="flex items-center text-sm text-gray-500">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h4a1 1 0 011 1v2h4a1 1 0 011 1v1H3V5a1 1 0 011-1h3z"/>
        </svg>
        {{ document.version }}
      </div>
    </div>

    <div v-if="document.description" class="mt-3 text-sm text-gray-600">
      {{ document.description }}
    </div>

    <div v-if="document.tags.length > 0" class="mt-3 flex flex-wrap gap-1">
      <span
        v-for="tag in document.tags.slice(0, 3)"
        :key="tag"
        class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700"
      >
        {{ tag }}
      </span>
      <span
        v-if="document.tags.length > 3"
        class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700"
      >
        +{{ document.tags.length - 3 }}
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Document } from '@/types';
import { getFileIcon, getFileIconColor, formatFileSize, formatDate, getFileExtension } from '@/service/fileUtils';

interface Props {
  document: Document;
}

defineProps<Props>();

defineEmits<{
  toggleFavorite: [];
}>();
</script>
