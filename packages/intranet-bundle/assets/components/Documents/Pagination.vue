<template>
  <div class="flex items-center justify-between mt-8">
    <div class="text-sm text-gray-700">
      Affichage de {{ startItem }} à {{ endItem }} sur {{ paginationInfo.totalItems }} documents
    </div>
    
    <div class="flex items-center space-x-2">
      <button
        @click="$emit('pageChange', paginationInfo.currentPage - 1)"
        :disabled="paginationInfo.currentPage <= 1"
        class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        Précédent
      </button>
      
      <div class="flex items-center space-x-1">
        <button
          v-for="page in visiblePages"
          :key="page"
          @click="$emit('pageChange', page)"
          :class="[
            'px-3 py-2 text-sm font-medium rounded-md',
            page === paginationInfo.currentPage
              ? 'bg-primary-600 text-white'
              : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'
          ]"
        >
          {{ page }}
        </button>
      </div>
      
      <button
        @click="$emit('pageChange', paginationInfo.currentPage + 1)"
        :disabled="paginationInfo.currentPage >= paginationInfo.totalPages"
        class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        Suivant
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { PaginationInfo } from '@/types';

interface Props {
  paginationInfo: PaginationInfo;
}

const props = defineProps<Props>();

defineEmits<{
  pageChange: [page: number];
}>();

const startItem = computed(() => {
  return (props.paginationInfo.currentPage - 1) * props.paginationInfo.itemsPerPage + 1;
});

const endItem = computed(() => {
  return Math.min(
    props.paginationInfo.currentPage * props.paginationInfo.itemsPerPage,
    props.paginationInfo.totalItems
  );
});

const visiblePages = computed(() => {
  const pages = [];
  const totalPages = props.paginationInfo.totalPages;
  const currentPage = props.paginationInfo.currentPage;
  
  if (totalPages > 0) {
    pages.push(1);
  }
  
  const startPage = Math.max(2, currentPage - 1);
  const endPage = Math.min(totalPages - 1, currentPage + 1);
  
  if (startPage > 2) {
    pages.push('...');
  }
  
  for (let i = startPage; i <= endPage; i++) {
    if (i !== 1 && i !== totalPages) {
      pages.push(i);
    }
  }
  
  if (endPage < totalPages - 1) {
    pages.push('...');
  }
  
  if (totalPages > 1) {
    pages.push(totalPages);
  }
  
  return pages;
});
</script>