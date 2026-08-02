<template>
  <div
    @click="$emit('selectDocument', document)"
    class="card p-3 hover:shadow-md border border-gray-200 hover:border-primary-300 transition-all duration-200 group cursor-pointer bg-white rounded-lg"
  >
    <div class="flex items-center space-x-4">
      <div :class="['text-2xl p-2 rounded-lg bg-gray-50 flex items-center justify-center', getFileIconColor(document.type)]">
        {{ getFileIcon(document.type) }}
      </div>

      <div class="flex-1 min-w-0">
        <div class="flex items-center justify-between">
          <h3 class="font-medium text-gray-900 truncate group-hover:text-primary-600 transition-colors">
            {{ document.title }}
          </h3>
          <div class="flex items-center space-x-2">
            <button
              @click.stop="$emit('downloadDocument', document)"
              class="opacity-0 group-hover:opacity-100 transition-opacity p-1 text-gray-500 hover:text-primary-600 hover:bg-gray-100 rounded"
              title="Télécharger"
            >
              📥
            </button>
            <button
              @click.stop="$emit('deleteDocument', document)"
              class="opacity-0 group-hover:opacity-100 transition-opacity p-1 text-gray-500 hover:text-red-600 hover:bg-gray-100 rounded"
              title="Supprimer"
            >
              🗑️
            </button>
            <button
              @click.stop="$emit('toggleFavorite', document.id)"
              class="p-1 hover:bg-gray-100 rounded transition-colors"
              :title="document.isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris'"
            >
              <span :class="document.isFavorite ? 'text-yellow-500' : 'text-gray-300 group-hover:text-gray-400'">
                {{ document.isFavorite ? '⭐' : '☆' }}
              </span>
            </button>
          </div>
        </div>

        <div class="flex items-center space-x-4 text-xs text-gray-500 mt-1">
          <span class="font-semibold uppercase text-gray-700">{{ getFileExtension(document.type) }}</span>
          <span>{{ formatFileSize(document.size) }}</span>
          <span>👤 {{ document.author }}</span>
          <span>📅 {{ formatDate(document.lastModified) }}</span>
          <span class="bg-gray-100 text-gray-700 px-1.5 py-0.5 rounded font-mono">{{ document.version }}</span>
        </div>
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
