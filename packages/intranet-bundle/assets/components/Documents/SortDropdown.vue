<template>
  <div class="relative">
    <button
      @click="isOpen = !isOpen"
      class="flex items-center space-x-2 px-3 py-2 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4"/>
      </svg>
      <span class="text-sm font-medium">{{ getSortLabel() }}</span>
      <svg
        :class="['w-4 h-4 transition-transform', isOpen ? 'rotate-180' : '']"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
      </svg>
    </button>

    <div
      v-if="isOpen"
      class="absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50"
    >
      <div class="py-1">
        <button
          v-for="option in sortOptions"
          :key="`${option.field}-${option.order}`"
          @click="selectSort(option.field, option.order)"
          :class="[
            'flex items-center justify-between w-full px-4 py-2 text-sm text-left hover:bg-gray-50',
            sortField === option.field && sortOrder === option.order
              ? 'bg-primary-50 text-primary-700'
              : 'text-gray-700'
          ]"
        >
          <span>{{ option.label }}</span>
          <span v-if="sortField === option.field && sortOrder === option.order" class="text-primary-500">
            ✓
          </span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import type { SortField, SortOrder } from '@/types';

interface Props {
  sortField: SortField;
  sortOrder: SortOrder;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  sort: [{ field: SortField; order: SortOrder }];
}>();

const isOpen = ref(false);

const sortOptions = [
  { field: 'title' as SortField, order: 'asc' as SortOrder, label: 'Titre (A→Z)' },
  { field: 'title' as SortField, order: 'desc' as SortOrder, label: 'Titre (Z→A)' },
  { field: 'lastModified' as SortField, order: 'desc' as SortOrder, label: 'Plus récent' },
  { field: 'lastModified' as SortField, order: 'asc' as SortOrder, label: 'Plus ancien' },
  { field: 'size' as SortField, order: 'desc' as SortOrder, label: 'Taille (↓)' },
  { field: 'size' as SortField, order: 'asc' as SortOrder, label: 'Taille (↑)' },
];

const selectSort = (field: SortField, order: SortOrder) => {
  emit('sort', { field, order });
  isOpen.value = false;
};

const getSortLabel = () => {
  const option = sortOptions.find(o => o.field === props.sortField && o.order === props.sortOrder);
  return option ? option.label : 'Trier par';
};

const closeDropdown = (event: MouseEvent) => {
  if (!(event.target as Element).closest('.relative')) {
    isOpen.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', closeDropdown);
});

onUnmounted(() => {
  document.removeEventListener('click', closeDropdown);
});
</script>