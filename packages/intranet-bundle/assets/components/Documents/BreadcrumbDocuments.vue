<template>
  <nav class="flex items-center space-x-2 text-sm text-gray-500 mb-4">
    <button
      @click="$emit('navigate', null)"
      class="hover:text-gray-700 transition-colors"
    >
      📁 Accueil
    </button>
    
    <template v-for="(item, index) in breadcrumbItems" :key="item.id">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
      </svg>
      
      <button
        v-if="index < breadcrumbItems.length - 1"
        @click="$emit('navigate', item.id)"
        class="hover:text-gray-700 transition-colors"
      >
        {{ item.icon }} {{ item.name }}
      </button>
      
      <span v-else class="text-gray-900 font-medium">
        {{ item.icon }} {{ item.name }}
      </span>
    </template>
  </nav>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { Category } from '@/types';

interface Props {
  categories: Category[];
  currentCategory: string | null;
  showFavorites: boolean;
}

const props = defineProps<Props>();

defineEmits<{
  navigate: [categoryId: string | null];
}>();

const breadcrumbItems = computed(() => {
  if (props.showFavorites) {
    return [{ id: 'favorites', name: 'Favoris', icon: '⭐' }];
  }
  
  if (!props.currentCategory) {
    return [];
  }
  
  const items = [];
  const findCategoryPath = (categories: Category[], targetId: string, path: Category[] = []): Category[] | null => {
    for (const category of categories) {
      const newPath = [...path, category];
      if (category.id === targetId) {
        return newPath;
      }
      if (category.children) {
        const result = findCategoryPath(category.children, targetId, newPath);
        if (result) return result;
      }
    }
    return null;
  };
  
  const path = findCategoryPath(props.categories, props.currentCategory);
  return path || [];
});
</script>