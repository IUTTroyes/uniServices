<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">
          {{ title }}
        </h2>
        <p class="text-gray-500 mt-1">
          {{ paginatedDocuments.length }} documents
          <span v-if="paginationInfo.totalItems > paginationInfo.itemsPerPage">
            ({{ paginationInfo.totalItems }} au total)
          </span>
        </p>
      </div>
      
      <div class="flex items-center space-x-3">
        <!-- View Mode Toggle -->
        <ViewModeToggle
          :view-mode="viewMode"
          @changeView="$emit('changeView', $event)"
        />
        
        <!-- Sort Dropdown -->
        <SortDropdown
          :sort-field="sortField"
          :sort-order="sortOrder"
          @sort="$emit('sort', $event)"
        />
      </div>
    </div>

    <!-- Documents -->
    <div v-if="paginatedDocuments.length > 0">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <DocumentCard
          v-for="document in paginatedDocuments"
          :key="document.id"
          :document="document"
          @toggleFavorite="$emit('toggleFavorite', document.id)"
        />
      </div>
      
      <!-- Pagination -->
      <Pagination
        v-if="paginationInfo.totalPages > 1"
        :pagination-info="paginationInfo"
        @pageChange="$emit('pageChange', $event)"
      />
    </div>
    
    <!-- Empty State -->
    <div v-else class="text-center py-12">
      <div class="text-6xl mb-4">📭</div>
      <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun document trouvé</h3>
      <p class="text-gray-500">
        {{ emptyMessage }}
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import DocumentCard from './DocumentCard.vue';
import SortDropdown from './SortDropdown.vue';
import Pagination from './Pagination.vue';
import ViewModeToggle from './ViewModeToggle.vue';
import type { Document, SortField, SortOrder, PaginationInfo, ViewMode } from '@/types';

interface Props {
  documents: Document[];
  title: string;
  sortField: SortField;
  sortOrder: SortOrder;
  paginationInfo: PaginationInfo;
  viewMode: ViewMode;
  emptyMessage?: string;
}

const props = defineProps<Props>();

defineEmits<{
  toggleFavorite: [documentId: string];
  sort: [{ field: SortField; order: SortOrder }];
  pageChange: [page: number];
  changeView: [mode: ViewMode];
}>();

const paginatedDocuments = computed(() => {
  const start = (props.paginationInfo.currentPage - 1) * props.paginationInfo.itemsPerPage;
  const end = start + props.paginationInfo.itemsPerPage;
  return props.documents.slice(start, end);
});
</script>