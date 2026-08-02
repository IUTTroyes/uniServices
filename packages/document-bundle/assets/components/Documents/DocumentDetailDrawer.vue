<template>
  <Sidebar
    :visible="visible"
    position="right"
    class="w-full md:w-96 p-sidebar-md"
    @update:visible="$emit('update:visible', $event)"
  >
    <template #header>
      <div class="flex items-center space-x-3">
        <span class="text-2xl">{{ getFileIcon(document?.type || 'pdf') }}</span>
        <div class="min-w-0">
          <h3 class="font-bold text-gray-900 truncate">{{ document?.title }}</h3>
          <p class="text-xs text-gray-500">{{ getFileExtension(document?.type || 'pdf') }}</p>
        </div>
      </div>
    </template>

    <div v-if="document" class="space-y-6 pt-4">
      <!-- File Preview Box -->
      <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 text-center">
        <div :class="['text-5xl mb-2', getFileIconColor(document.type)]">
          {{ getFileIcon(document.type) }}
        </div>
        <p class="font-semibold text-gray-800 text-sm truncate">{{ document.title }}</p>
        <p class="text-xs text-gray-500 mt-1">{{ formatFileSize(document.size) }}</p>
      </div>

      <!-- Quick Actions -->
      <div class="flex space-x-2">
        <button
          @click="$emit('download', document)"
          class="flex-1 bg-primary-600 hover:bg-primary-700 text-white font-medium py-2 px-3 rounded-lg text-sm transition-colors flex items-center justify-center space-x-2 shadow-sm"
        >
          <span>📥</span>
          <span>Télécharger</span>
        </button>

        <button
          @click="$emit('toggleFavorite', document.id)"
          :class="[
            'px-3 py-2 border rounded-lg text-sm transition-colors flex items-center justify-center',
            document.isFavorite
              ? 'bg-yellow-50 text-yellow-700 border-yellow-300 hover:bg-yellow-100'
              : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
          ]"
          :title="document.isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris'"
        >
          <span>{{ document.isFavorite ? '⭐' : '☆' }}</span>
        </button>

        <ButtonDelete
          @confirm="$emit('delete', document)"
        />
      </div>

      <!-- Metadata List -->
      <div class="space-y-3 pt-2 text-sm border-t border-gray-100">
        <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Informations</h4>

        <div class="flex justify-between py-1 border-b border-gray-50">
          <span class="text-gray-500">Auteur</span>
          <span class="font-medium text-gray-800">{{ document.author }}</span>
        </div>

        <div class="flex justify-between py-1 border-b border-gray-50">
          <span class="text-gray-500">Dernière modification</span>
          <span class="font-medium text-gray-800">{{ formatDate(document.lastModified) }}</span>
        </div>

        <div class="flex justify-between py-1 border-b border-gray-50">
          <span class="text-gray-500">Version</span>
          <span class="font-medium text-gray-800">{{ document.version }}</span>
        </div>

        <div class="flex justify-between py-1 border-b border-gray-50">
          <span class="text-gray-500">Taille</span>
          <span class="font-medium text-gray-800">{{ formatFileSize(document.size) }}</span>
        </div>

        <div v-if="categoryName" class="flex justify-between py-1 border-b border-gray-50">
          <span class="text-gray-500">Catégorie</span>
          <span class="font-medium text-primary-700 bg-primary-50 px-2 py-0.5 rounded text-xs">{{ categoryName }}</span>
        </div>
      </div>

      <!-- Description -->
      <div v-if="document.description" class="pt-2 border-t border-gray-100">
        <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Description</h4>
        <p class="text-sm text-gray-700 bg-gray-50 p-3 rounded-lg border border-gray-200">
          {{ document.description }}
        </p>
      </div>

      <!-- Tags -->
      <div v-if="document.tags.length > 0" class="pt-2 border-t border-gray-100">
        <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Étiquettes</h4>
        <div class="flex flex-wrap gap-1.5">
          <span
            v-for="tag in document.tags"
            :key="tag"
            class="px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200"
          >
            #{{ tag }}
          </span>
        </div>
      </div>
    </div>
  </Sidebar>
</template>

<script setup lang="ts">
import Sidebar from 'primevue/sidebar';
import type { Document } from '@types';
import { ButtonDelete } from '@components';
import { getFileIcon, getFileIconColor, formatFileSize, formatDate, getFileExtension } from '@/service/utils/fileUtils';

interface Props {
  visible: boolean;
  document: Document | null;
  categoryName?: string;
}

defineProps<Props>();

defineEmits<{
  'update:visible': [value: boolean];
  download: [document: Document];
  delete: [document: Document];
  toggleFavorite: [documentId: string];
}>();
</script>
