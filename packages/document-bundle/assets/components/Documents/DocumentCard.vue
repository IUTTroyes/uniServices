<template>
  <div
    @click="$emit('selectDocument', document)"
    class="card p-4 hover:shadow-lg border border-gray-200 hover:border-primary-300 transition-all duration-200 group cursor-pointer flex flex-col justify-between relative bg-white rounded-xl"
  >
    <div>
      <!-- Header -->
      <div class="flex items-start justify-between mb-3">
        <div class="flex items-center space-x-3 min-w-0">
          <div :class="['text-3xl p-2 rounded-lg bg-gray-50 flex items-center justify-center', getFileIconColor(document.type)]">
            {{ getFileIcon(document.type) }}
          </div>
          <div class="flex-1 min-w-0">
            <h3 class="font-semibold text-gray-900 truncate group-hover:text-primary-600 transition-colors">
              {{ document.title }}
            </h3>
            <p class="text-xs text-gray-500 truncate">
              {{ getFileExtension(document.type) }} • {{ formatFileSize(document.size) }}
            </p>
          </div>
        </div>

        <button
          @click.stop="$emit('toggleFavorite', document.id)"
          class="p-1 hover:bg-gray-100 rounded transition-colors"
          :title="document.isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris'"
        >
          <span :class="['text-lg', document.isFavorite ? 'text-yellow-500' : 'text-gray-300 group-hover:text-gray-400']">
            {{ document.isFavorite ? '⭐' : '☆' }}
          </span>
        </button>
      </div>

      <!-- Info badges -->
      <div class="space-y-1.5 text-xs text-gray-500 my-2">
        <div class="flex items-center">
          <span class="w-4 me-1 opacity-70">👤</span>
          <span class="truncate">{{ document.author }}</span>
        </div>

        <div class="flex items-center">
          <span class="w-4 me-1 opacity-70">📅</span>
          <span>{{ formatDate(document.lastModified) }}</span>
        </div>
      </div>

      <div v-if="document.description" class="mt-2 text-xs text-gray-600 line-clamp-2">
        {{ document.description }}
      </div>

      <!-- Tags -->
      <div v-if="document.tags.length > 0" class="mt-3 flex flex-wrap gap-1">
        <span
          v-for="tag in document.tags.slice(0, 3)"
          :key="tag"
          class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600"
        >
          #{{ tag }}
        </span>
        <span
          v-if="document.tags.length > 3"
          class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600 font-medium"
        >
          +{{ document.tags.length - 3 }}
        </span>
      </div>
    </div>

    <!-- Quick Footer Actions -->
    <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between opacity-0 group-hover:opacity-100 transition-opacity">
      <span class="text-xs font-medium text-primary-600 hover:underline">Voir détails →</span>
      <div class="flex items-center space-x-1">
        <button
          @click.stop="$emit('downloadDocument', document)"
          class="p-1.5 text-gray-500 hover:text-primary-600 hover:bg-primary-50 rounded transition-colors"
          title="Télécharger"
        >
          📥
        </button>
        <button
          v-permission="'isPersonnel'"
          @click.stop="$emit('deleteDocument', document)"
          class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded transition-colors"
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
