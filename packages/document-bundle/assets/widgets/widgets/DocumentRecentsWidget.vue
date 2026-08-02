<script setup lang="ts">
import { computed } from 'vue';

interface DocumentItem {
  id: number | string;
  title: string;
  type: string;
  size: number;
  category: string;
  updatedAt: string;
  author: string;
}

const props = defineProps<{
  data?: {
    items?: DocumentItem[];
  };
}>();

const items = computed(() => props.data?.items || []);

const getFileIcon = (type: string) => {
  switch (type?.toLowerCase()) {
    case 'pdf': return '📕';
    case 'word': return '📘';
    case 'excel': return '📗';
    case 'image': return '🖼️';
    default: return '📄';
  }
};
</script>

<template>
  <div v-if="items.length > 0" class="space-y-3">
    <div
      v-for="item in items"
      :key="item.id"
      class="flex items-center justify-between p-2.5 rounded-xl bg-gray-50 dark:bg-slate-800/50 hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors"
    >
      <div class="flex items-center space-x-3 min-w-0">
        <span class="text-xl flex-shrink-0">{{ getFileIcon(item.type) }}</span>
        <div class="min-w-0">
          <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
            {{ item.title }}
          </p>
          <div class="flex items-center space-x-2 text-xs text-gray-500 dark:text-gray-400 mt-0.5">
            <span class="bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300 px-1.5 py-0.5 rounded font-medium text-[10px]">
              {{ item.category }}
            </span>
            <span>•</span>
            <span>{{ item.updatedAt }}</span>
          </div>
        </div>
      </div>

      <router-link
        to="/documents"
        class="text-xs font-bold text-blue-600 hover:text-blue-700 dark:text-blue-400 flex items-center space-x-1 flex-shrink-0 ml-2"
      >
        <span>Ouvrir</span>
        <span>→</span>
      </router-link>
    </div>
  </div>

  <div v-else class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
    Aucun document récent disponible.
  </div>
</template>
