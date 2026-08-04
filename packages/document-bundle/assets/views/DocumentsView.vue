<template>
  <div class="min-h-screen flex">
    <Toast />
    <ConfirmDialog />

    <!-- Sidebar -->
    <Sidebar
        :categories="categories"
        :selected-category="selectedCategory"
        :show-favorites="showFavorites"
        :total-documents="totalDocuments"
        :favorite-count="favoriteCount"
        @selectCategory="selectCategory"
        @selectFavorites="selectFavorites"
        @search="handleSearch"
        @openUploadModal="showUploadModal = true"
        class="me-3"
    />

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <!-- Loading State with Shared Skeletons -->
      <div v-if="loading" class="flex-1 overflow-y-auto p-4 space-y-4">
        <div class="flex justify-between items-center mb-6">
          <div class="h-8 w-48 bg-gray-200 rounded animate-pulse"></div>
          <div class="h-8 w-32 bg-gray-200 rounded animate-pulse"></div>
        </div>
        <div v-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
          <CardSkeleton v-for="i in 8" :key="i" />
        </div>
        <div v-else class="space-y-3">
          <ListSkeleton v-for="i in 6" :key="i" />
        </div>
      </div>

      <!-- Content -->
      <main v-else class="flex-1 overflow-y-auto p-4">
        <DocumentGrid
            v-if="viewMode === 'grid'"
            :documents="filteredDocuments"
            :title="getTitle()"
            :sort-field="sortField"
            :sort-order="sortOrder"
            :pagination-info="paginationInfo"
            :view-mode="viewMode"
            :empty-message="getEmptyMessage()"
            @selectDocument="openDetailDrawer"
            @downloadDocument="handleDownloadDocument"
            @deleteDocument="confirmDeleteDocument"
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
            @selectDocument="openDetailDrawer"
            @downloadDocument="handleDownloadDocument"
            @deleteDocument="confirmDeleteDocument"
            @toggleFavorite="toggleFavorite"
            @sort="handleSort"
            @pageChange="handlePageChange"
            @changeView="handleViewModeChange"
        />
      </main>
    </div>

    <!-- Detail Drawer -->
    <DocumentDetailDrawer
      v-model:visible="showDetailDrawer"
      :document="selectedDocument"
      :category-name="selectedDocumentCategoryName"
      @download="handleDownloadDocument"
      @delete="confirmDeleteDocument"
      @toggleFavorite="toggleFavorite"
    />

    <!-- Upload Modal -->
    <DocumentUploadModal
      v-model:visible="showUploadModal"
      :categories="categories"
      @submit="handleCreateDocument"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

import Sidebar from '@/components/Documents/Sidebar.vue';
import DocumentGrid from '@/components/Documents/DocumentGrid.vue';
import DocumentList from '@/components/Documents/DocumentList.vue';
import DocumentUploadModal from '@/components/Documents/DocumentUploadModal.vue';
import DocumentDetailDrawer from '@/components/Documents/DocumentDetailDrawer.vue';
import { documentService } from '@/service/documentService';
import { CardSkeleton, ListSkeleton } from '@components';
import { useSecurity } from '@stores';
import type { Category, Document, SortField, SortOrder, PaginationInfo, ViewMode } from '@types';

const toast = useToast();
const confirm = useConfirm();

// State
const loading = ref(true);
const showUploadModal = ref(false);
const showDetailDrawer = ref(false);
const selectedDocument = ref<Document | null>(null);

const categories = ref<Category[]>([]);
const documentsList = ref<Document[]>([]);
const selectedCategory = ref<string | null>(null);
const showFavorites = ref(false);
const searchQuery = ref('');
const sortField = ref<SortField>('lastModified');
const sortOrder = ref<SortOrder>('desc');
const currentPage = ref(1);
const itemsPerPage = ref(20);
const viewMode = ref<ViewMode>('grid');

// Computed
const totalDocuments = computed(() => documentsList.value.length);
const favoriteCount = computed(() => documentsList.value.filter(d => d.isFavorite).length);

const selectedDocumentCategoryName = computed(() => {
  if (!selectedDocument.value?.categoryId) return undefined;
  const findCat = (cats: Category[]): Category | undefined => {
    for (const c of cats) {
      if (c.id === selectedDocument.value?.categoryId) return c;
      if (c.children) {
        const res = findCat(c.children);
        if (res) return res;
      }
    }
    return undefined;
  };
  return findCat(categories.value)?.name;
});

const currentDocuments = computed(() => {
  if (showFavorites.value) {
    return documentsList.value.filter(d => d.isFavorite);
  }

  if (selectedCategory.value) {
    // Collect all subcategory IDs including selectedCategory
    const categoryIds = new Set<string>([selectedCategory.value]);
    const collectChildIds = (cats: Category[]) => {
      for (const c of cats) {
        if (categoryIds.has(c.id)) {
          if (c.children) {
            c.children.forEach(child => {
              categoryIds.add(child.id);
              if (child.children) collectChildIds([child]);
            });
          }
        } else if (c.children) {
          collectChildIds(c.children);
        }
      }
    };
    collectChildIds(categories.value);
    return documentsList.value.filter(d => categoryIds.has(d.categoryId));
  }

  return documentsList.value;
});

const searchedDocuments = computed(() => {
  if (!searchQuery.value) {
    return currentDocuments.value;
  }

  const q = searchQuery.value.toLowerCase();
  return currentDocuments.value.filter(doc =>
    doc.title.toLowerCase().includes(q) ||
    doc.description?.toLowerCase().includes(q) ||
    doc.author.toLowerCase().includes(q) ||
    doc.tags.some(t => t.toLowerCase().includes(q))
  );
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
        aValue = a.lastModified instanceof Date ? a.lastModified.getTime() : new Date(a.lastModified).getTime();
        bValue = b.lastModified instanceof Date ? b.lastModified.getTime() : new Date(b.lastModified).getTime();
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
const loadData = async () => {
  loading.value = true;
  try {
    const security = useSecurity();
    const activePackages = security.activePackages || [];
    const currentDepartmentId = security.currentDepartment?.id ? String(security.currentDepartment.id) : undefined;

    const [fetchedCategories, fetchedDocs] = await Promise.all([
      documentService.fetchCategories({ activePackages, currentDepartmentId }),
      documentService.fetchDocuments()
    ]);
    categories.value = fetchedCategories;
    documentsList.value = fetchedDocs;
    documentService.updateCategoryCounts(categories.value, documentsList.value);
  } catch (e) {
    console.error('Error loading documents:', e);
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de charger les documents depuis l\'API', life: 4000 });
  } finally {
    loading.value = false;
  }
};

const openDetailDrawer = (doc: Document) => {
  selectedDocument.value = doc;
  showDetailDrawer.value = true;
};

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

const toggleFavorite = async (documentId: string) => {
  const doc = documentsList.value.find(d => d.id === documentId);
  if (doc) {
    try {
      const newStatus = await documentService.toggleFavorite(documentId, doc.isFavorite);
      doc.isFavorite = newStatus;
      toast.add({
        severity: 'info',
        summary: 'Favoris',
        detail: newStatus ? `"${doc.title}" ajouté aux favoris` : `"${doc.title}" retiré des favoris`,
        life: 2500
      });
    } catch (e) {
      toast.add({ severity: 'error', summary: 'Erreur', detail: 'Échec de la mise à jour des favoris', life: 3000 });
    }
  }
};

const handleDownloadDocument = (doc: Document) => {
  toast.add({
    severity: 'success',
    summary: 'Téléchargement',
    detail: `Préparation du téléchargement de "${doc.title}"`,
    life: 3000
  });
};

const confirmDeleteDocument = (doc: Document) => {
  confirm.require({
    message: `Voulez-vous vraiment supprimer définitivement "${doc.title}" ?`,
    header: 'Confirmation de suppression',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Oui, supprimer',
    rejectLabel: 'Annuler',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await documentService.deleteDocument(doc.id);
        documentsList.value = documentsList.value.filter(d => d.id !== doc.id);
        documentService.updateCategoryCounts(categories.value, documentsList.value);
        if (selectedDocument.value?.id === doc.id) {
          showDetailDrawer.value = false;
          selectedDocument.value = null;
        }
        toast.add({ severity: 'success', summary: 'Supprimé', detail: 'Document supprimé avec succès', life: 3000 });
      } catch (e) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de supprimer le document', life: 3000 });
      }
    }
  });
};

const handleCreateDocument = async (docData: { titre: string; description?: string; type: string; categoryId?: string; tags?: string[] }) => {
  try {
    const security = useSecurity();
    const currentDepartmentId = security.currentDepartment?.id ? String(security.currentDepartment.id) : undefined;
    const created = await documentService.createDocument({
      ...docData,
      departementId: currentDepartmentId
    });
    documentsList.value.unshift(created);
    documentService.updateCategoryCounts(categories.value, documentsList.value);
    toast.add({ severity: 'success', summary: 'Document créé', detail: `"${created.title}" a été ajouté`, life: 3000 });
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de créer le document sur l\'API', life: 3000 });
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
  loadData();
});
</script>
