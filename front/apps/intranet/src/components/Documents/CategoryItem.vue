<template>
  <div>
    <button
      @click="selectCategory"
      :class="[
        'w-full flex items-center space-x-2 px-3 py-2 text-sm font-medium rounded-md transition-colors',
        selectedCategory === category.id 
          ? 'bg-primary-50 text-primary-700 border border-primary-200' 
          : 'text-gray-700 hover:bg-gray-50'
      ]"
    >
      <div class="flex items-center space-x-2 flex-1">
        <button
          v-if="category.children && category.children.length > 0"
          @click.stop="toggleExpanded"
          class="p-1 hover:bg-gray-100 rounded"
        >
          <svg
            :class="[
              'w-3 h-3 transition-transform',
              expanded ? 'rotate-90' : ''
            ]"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 5l7 7-7 7"
            />
          </svg>
        </button>
        <div v-else class="w-5"></div>
        
        <span class="text-lg">{{ category.icon }}</span>
        <span class="flex-1 text-left">{{ category.name }}</span>
        
        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full">
          {{ category.documentCount }}
        </span>
      </div>
    </button>

    <div
      v-if="expanded && category.children"
      class="ml-4 mt-1 space-y-1"
    >
      <CategoryItem
        v-for="child in category.children"
        :key="child.id"
        :category="child"
        :selected-category="selectedCategory"
        @select="$emit('select', $event)"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import type { Category } from '@/types';

interface Props {
  category: Category;
  selectedCategory: string | null;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  select: [categoryId: string];
}>();

const expanded = ref(false);

const toggleExpanded = () => {
  expanded.value = !expanded.value;
};

const selectCategory = () => {
  emit('select', props.category.id);
};
</script>