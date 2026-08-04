<template>
  <div class="h-full bg-white border-r border-gray-200 flex flex-col w-64">
    <!-- Action Header -->
    <div class="p-4 border-b border-gray-200 space-y-3">
      <button
        v-permission="'isPersonnel'"
        @click="$emit('openUploadModal')"
        class="w-full bg-primary-600 hover:bg-primary-700 text-white font-medium py-2 px-4 rounded-lg shadow-sm hover:shadow transition-all flex items-center justify-center space-x-2 text-sm"
      >
        <span class="text-lg">+</span>
        <span>Nouveau document</span>
      </button>

      <SearchBar
          v-model="searchQuery"
          @search="handleSearch"
      />
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-4 py-4 space-y-2 overflow-y-auto">
      <!-- All Documents -->
      <button
        @click="$emit('selectCategory', null)"
        :class="[
          'w-full flex items-center space-x-3 px-3 py-2 text-sm font-medium rounded-md transition-colors',
          selectedCategory === null && !showFavorites
            ? 'bg-primary-50 text-primary-700 border border-primary-200'
            : 'text-gray-700 hover:bg-gray-50'
        ]"
      >
        <span class="text-lg">📁</span>
        <span>Tous les documents</span>
        <span class="ml-auto text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full">
          {{ totalDocuments }}
        </span>
      </button>

      <!-- Favorites -->
      <button
        @click="$emit('selectFavorites')"
        :class="[
          'w-full flex items-center space-x-3 px-3 py-2 text-sm font-medium rounded-md transition-colors',
          showFavorites
            ? 'bg-yellow-50 text-yellow-700 border border-yellow-200'
            : 'text-gray-700 hover:bg-gray-50'
        ]"
      >
        <span class="text-lg">⭐</span>
        <span>Favoris</span>
        <span class="ml-auto text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full">
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
