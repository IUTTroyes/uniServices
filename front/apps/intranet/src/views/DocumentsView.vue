<template>
  <div class="min-h-screen  flex">
    <!-- Sidebar -->
    <Sidebar
        :categories="categories"
        :selected-category="selectedCategory"
        :show-favorites="showFavorites"
        :total-documents="totalDocuments"
        :favorite-count="favoriteDocuments.length"
        @selectCategory="selectCategory"
        @selectFavorites="selectFavorites"
        @search="handleSearch"
        class="me-3"
    />

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <!-- Content -->
      <main class="flex-1 overflow-y-auto">
        <DocumentGrid
            v-if="viewMode === 'grid'"
            :documents="filteredDocuments"
            :title="getTitle()"
            :sort-field="sortField"
            :sort-order="sortOrder"
            :pagination-info="paginationInfo"
            :view-mode="viewMode"
            :empty-message="getEmptyMessage()"
            @toggleFavorite="toggleFavorite"
            @sort="handleSort"
            @pageChange="handlePageChange"
            @changeView="handleViewModeChange"
        />

        <DocumentList
            v-else
            :documents="filteredDocuments"
            :title="getTitle()"
            :sort-field="sortField"
            :sort-order="sortOrder"
            :pagination-info="paginationInfo"
            :view-mode="viewMode"
            :empty-message="getEmptyMessage()"
            @toggleFavorite="toggleFavorite"
            @sort="handleSort"
            @pageChange="handlePageChange"
            @changeView="handleViewModeChange"
        />
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import Sidebar from '@/components/Documents/Sidebar.vue';
import DocumentGrid from '@/components/Documents/DocumentGrid.vue';
import DocumentList from '@/components/Documents/DocumentList.vue';
import { getAllCategories, documents, getFavoriteDocuments, searchDocuments, getDocumentsByCategory } from '@/data/mockData';
import type { Category, Document, SortField, SortOrder, PaginationInfo, ViewMode } from '@/types';

// State
const categories = ref<Category[]>([]);
const selectedCategory = ref<string | null>(null);
const showFavorites = ref(false);
const searchQuery = ref('');
const sortField = ref<SortField>('lastModified');
const sortOrder = ref<SortOrder>('desc');
const currentPage = ref(1);
const itemsPerPage = ref(20);
const viewMode = ref<ViewMode>('grid');

// Computed
const totalDocuments = computed(() => documents.length);
const favoriteDocuments = computed(() => getFavoriteDocuments());

const currentDocuments = computed(() => {
  if (showFavorites.value) {
    return favoriteDocuments.value;
  }

  if (selectedCategory.value) {
    return getDocumentsByCategory(selectedCategory.value);
  }

  return documents;
});

const searchedDocuments = computed(() => {
  if (!searchQuery.value) {
    return currentDocuments.value;
  }

  return searchDocuments(searchQuery.value, {
    category: selectedCategory.value
  });
});

const sortedDocuments = computed(() => {
  const docs = [...searchedDocuments.value];

  docs.sort((a, b) => {
    let aValue: any;
    let bValue: any;

    switch (sortField.value) {
      case 'title':
        aValue = a.title.toLowerCase();
        bValue = b.title.toLowerCase();
        break;
      case 'lastModified':
        aValue = a.lastModified.getTime();
        bValue = b.lastModified.getTime();
        break;
      case 'size':
        aValue = a.size;
        bValue = b.size;
        break;
      case 'type':
        aValue = a.type;
        bValue = b.type;
        break;
      default:
        return 0;
    }

    if (sortOrder.value === 'asc') {
      return aValue < bValue ? -1 : aValue > bValue ? 1 : 0;
    } else {
      return aValue > bValue ? -1 : aValue < bValue ? 1 : 0;
    }
  });

  return docs;
});

const filteredDocuments = computed(() => sortedDocuments.value);

const paginationInfo = computed((): PaginationInfo => {
  const totalItems = filteredDocuments.value.length;
  const totalPages = Math.ceil(totalItems / itemsPerPage.value);

  return {
    currentPage: currentPage.value,
    totalPages,
    totalItems,
    itemsPerPage: itemsPerPage.value
  };
});

// Methods
const selectCategory = (categoryId: string | null) => {
  selectedCategory.value = categoryId;
  showFavorites.value = false;
  currentPage.value = 1;
};

const selectFavorites = () => {
  showFavorites.value = true;
  selectedCategory.value = null;
  currentPage.value = 1;
};

const handleSearch = (query: string) => {
  searchQuery.value = query;
  currentPage.value = 1;
};

const handleSort = ({ field, order }: { field: SortField; order: SortOrder }) => {
  sortField.value = field;
  sortOrder.value = order;
  currentPage.value = 1;
};

const handlePageChange = (page: number) => {
  currentPage.value = page;
};

const handleViewModeChange = (mode: ViewMode) => {
  viewMode.value = mode;
};

const toggleFavorite = (documentId: string) => {
  const document = documents.find(d => d.id === documentId);
  if (document) {
    document.isFavorite = !document.isFavorite;
  }
};

const getTitle = () => {
  if (showFavorites.value) {
    return 'Documents favoris';
  }

  if (selectedCategory.value) {
    const findCategory = (cats: Category[]): Category | null => {
      for (const cat of cats) {
        if (cat.id === selectedCategory.value) {
          return cat;
        }
        if (cat.children) {
          const found = findCategory(cat.children);
          if (found) return found;
        }
      }
      return null;
    };

    const category = findCategory(categories.value);
    return category ? category.name : 'Catégorie inconnue';
  }

  return 'Tous les documents';
};

const getEmptyMessage = () => {
  if (searchQuery.value) {
    return `Aucun document ne correspond à "${searchQuery.value}"`;
  }

  if (showFavorites.value) {
    return 'Aucun document n\'est marqué comme favori';
  }

  return 'Aucun document dans cette catégorie';
};

// Lifecycle
onMounted(() => {
  categories.value = getAllCategories();
});
</script>
