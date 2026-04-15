<template>
  <div class="card p-4 hover:shadow-md transition-shadow duration-200 group">
    <div class="flex items-center space-x-4">
      <div :class="['text-xl', getFileIconColor(document.type)]">
        {{ getFileIcon(document.type) }}
      </div>

      <div class="flex-1 min-w-0">
        <div class="flex items-center justify-between">
          <h3 class="font-medium text-gray-900 truncate">
            {{ document.title }}
          </h3>
          <button
            @click="$emit('toggleFavorite')"
            class="opacity-0 group-hover:opacity-100 transition-opacity p-1 hover:bg-gray-100 rounded"
          >
            <span :class="document.isFavorite ? 'text-yellow-500' : 'text-gray-400'">
              {{ document.isFavorite ? '⭐' : '☆' }}
            </span>
          </button>
        </div>

        <div class="flex items-center space-x-4 text-sm text-gray-500 mt-1">
          <span>{{ getFileExtension(document.type) }}</span>
          <span>{{ formatFileSize(document.size) }}</span>
          <span>{{ document.author }}</span>
          <span>{{ formatDate(document.lastModified) }}</span>
          <span>{{ document.version }}</span>
        </div>

        <div v-if="document.description" class="text-sm text-gray-600 mt-1 truncate">
          {{ document.description }}
        </div>

        <div v-if="document.tags.length > 0" class="flex flex-wrap gap-1 mt-2">
          <span
            v-for="tag in document.tags.slice(0, 5)"
            :key="tag"
            class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700"
          >
            {{ tag }}
          </span>
          <span
            v-if="document.tags.length > 5"
            class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700"
          >
            +{{ document.tags.length - 5 }}
          </span>
        </div>
      </div>
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
