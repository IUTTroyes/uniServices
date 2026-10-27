<template>
  <div class="bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded-2xl flex flex-col w-full">
    <!-- Action Header -->
    <div class="p-4 border-b border-surface-200 dark:border-surface-700 space-y-3">
      <Button
        v-permission="'isPersonnel'"
        label="Nouveau document"
        icon="pi pi-plus"
        class="w-full"
        @click="$emit('openUploadModal')"
      />

      <SearchBar
          v-model="searchQuery"
          @search="handleSearch"
      />
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-4 py-4 space-y-2 max-h-80 lg:max-h-none overflow-y-auto">
      <!-- All Documents -->
      <button
        @click="$emit('selectCategory', null)"
        :class="[
          'w-full flex items-center space-x-3 px-3 py-2 text-sm font-medium rounded-md transition-colors',
          selectedCategory === null && !showFavorites
            ? 'bg-primary-50 dark:bg-primary-950 text-primary-700 dark:text-primary-200 border border-primary-200 dark:border-primary-800'
            : 'text-surface-700 dark:text-surface-200 hover:bg-surface-100 dark:hover:bg-surface-800'
        ]"
      >
        <i class="pi pi-folder" aria-hidden="true" />
        <span>Tous les documents</span>
        <span class="ml-auto text-xs bg-surface-100 dark:bg-surface-800 text-muted-color px-2 py-1 rounded-full">
          {{ totalDocuments }}
        </span>
      </button>

      <!-- Favorites -->
      <button
        @click="$emit('selectFavorites')"
        :class="[
          'w-full flex items-center space-x-3 px-3 py-2 text-sm font-medium rounded-md transition-colors',
          showFavorites
            ? 'bg-primary-50 dark:bg-primary-950 text-primary-700 dark:text-primary-200 border border-primary-200 dark:border-primary-800'
            : 'text-surface-700 dark:text-surface-200 hover:bg-surface-100 dark:hover:bg-surface-800'
        ]"
      >
        <i class="pi pi-star" aria-hidden="true" />
        <span>Favoris</span>
        <span class="ml-auto text-xs bg-surface-100 dark:bg-surface-800 text-muted-color px-2 py-1 rounded-full">
          {{ favoriteCount }}
        </span>
      </button>

      <!-- Categories -->
      <div class="mt-6">
        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">
          Catégories
        </h3>
        <div class="space-y-1">
          <CategoryItem
            v-for="category in categories"
            :key="category.id"
            :category="category"
            :selected-category="selectedCategory"
            @select="$emit('selectCategory', $event)"
          />
        </div>
      </div>
    </nav>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import Button from 'primevue/button';
import CategoryItem from './CategoryItem.vue';
import SearchBar from './SearchBar.vue';
import type { Category } from '@types';

interface Props {
  categories: Category[];
  selectedCategory: string | null;
  showFavorites: boolean;
  totalDocuments: number;
  favoriteCount: number;
}

defineProps<Props>();

const emit = defineEmits<{
  selectCategory: [categoryId: string | null];
  selectFavorites: [];
  search: [query: string];
  openUploadModal: [];
}>();

const searchQuery = ref('');

const handleSearch = (query: string) => {
  emit('search', query);
};
</script>
