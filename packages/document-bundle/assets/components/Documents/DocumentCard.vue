<template>
  <div
    @click="$emit('selectDocument', document)"
    class="card p-4 hover:shadow-md border border-surface-200 dark:border-surface-700 hover:border-primary-300 transition-all duration-200 group cursor-pointer flex flex-col justify-between relative bg-white dark:bg-surface-900"
  >
    <div>
      <!-- Header -->
      <div class="flex items-start justify-between mb-3">
        <div class="flex items-center space-x-3 min-w-0">
          <div :class="['text-3xl p-2 rounded-lg bg-surface-50 dark:bg-surface-800 flex items-center justify-center', getFileIconColor(document.type)]">
            {{ getFileIcon(document.type) }}
          </div>
          <div class="flex-1 min-w-0">
            <h3 class="font-semibold text-surface-900 dark:text-surface-0 truncate group-hover:text-primary-600 transition-colors">
              {{ document.title }}
            </h3>
            <p class="text-xs text-surface-500 dark:text-surface-400 truncate">
              {{ getFileExtension(document.type) }} • {{ formatFileSize(document.size) }}
            </p>
          </div>
        </div>

        <button
          @click.stop="$emit('toggleFavorite', document.id)"
          class="p-1 hover:bg-surface-100 dark:hover:bg-surface-700 rounded transition-colors"
          :title="document.isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris'"
        >
          <span :class="['text-lg', document.isFavorite ? 'text-yellow-500' : 'text-gray-300 group-hover:text-gray-400']">
            {{ document.isFavorite ? '⭐' : '☆' }}
          </span>
        </button>
      </div>

      <!-- Info badges -->
      <div class="space-y-1.5 text-xs text-surface-500 dark:text-surface-400 my-2">
        <div class="flex items-center">
          <span class="w-4 me-1 opacity-70">👤</span>
          <span class="truncate">{{ document.author }}</span>
        </div>

        <div class="flex items-center">
          <span class="w-4 me-1 opacity-70">📅</span>
          <span>{{ formatDate(document.lastModified) }}</span>
        </div>
      </div>

      <div v-if="document.description" class="mt-2 text-xs text-surface-600 dark:text-surface-300 line-clamp-2">
        {{ document.description }}
      </div>

      <!-- Tags -->
      <div v-if="document.tags.length > 0" class="mt-3 flex flex-wrap gap-1">
        <span
          v-for="tag in document.tags.slice(0, 3)"
          :key="tag"
          class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-surface-100 dark:bg-surface-800 text-surface-600 dark:text-surface-300"
        >
          #{{ tag }}
        </span>
        <span
          v-if="document.tags.length > 3"
          class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-surface-100 dark:bg-surface-800 text-surface-600 dark:text-surface-300 font-medium"
        >
          +{{ document.tags.length - 3 }}
        </span>
      </div>
    </div>

    <!-- Quick Footer Actions -->
    <div class="mt-4 pt-3 border-t border-surface-100 dark:border-surface-700 flex items-center justify-between opacity-100 transition-opacity">
      <span class="text-xs font-medium text-primary-600 hover:underline">Voir détails →</span>
      <div class="flex items-center space-x-1">
        <button
          @click.stop="$emit('downloadDocument', document)"
          class="p-1.5 text-surface-500 dark:text-surface-400 hover:text-primary-600 hover:bg-primary-50 rounded transition-colors"
          title="Télécharger"
        >
          📥
        </button>
        <button
          v-permission="'isPersonnel'"
          @click.stop="$emit('deleteDocument', document)"
          class="p-1.5 text-surface-500 dark:text-surface-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors"
          title="Supprimer"
        >
          🗑️
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Document } from '@types';
import { getFileIcon, getFileIconColor, formatFileSize, formatDate, getFileExtension } from '@/service/utils/fileUtils';

interface Props {
  document: Document;
}

defineProps<Props>();

defineEmits<{
  selectDocument: [document: Document];
  downloadDocument: [document: Document];
  deleteDocument: [document: Document];
  toggleFavorite: [documentId: string];
}>();
</script>
