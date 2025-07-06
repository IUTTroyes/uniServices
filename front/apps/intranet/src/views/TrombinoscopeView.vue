<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { mockStudents, mockStaff } from '@/data/mockData';
import { useFilters } from '@/composables/useFilters';
import FilterSidebar from '@/components/Trombinoscope/FilterSidebar.vue';
import StatisticsPanel from '@/components/Trombinoscope/StatisticsPanel.vue';
import PersonCard from '@/components/Trombinoscope/PersonCard.vue';

// Initialize filters composable
const {
  filters,
  filteredStudents,
  filteredStaff,
  updateFilters,
  resetFilters,
  exportData,
} = useFilters(mockStudents, mockStaff);

// Computed properties for current filtered data
const currentFilteredData = computed(() => {
  return filters.value.mode === 'students' ? filteredStudents.value : filteredStaff.value;
});

// Handle keyboard shortcuts
const handleKeydown = (event: KeyboardEvent) => {
  if (event.ctrlKey || event.metaKey) {
    switch (event.key) {
      case 'f':
        event.preventDefault();
        // Focus search input
        const searchInput = document.querySelector('input[type="text"]') as HTMLInputElement;
        if (searchInput) {
          searchInput.focus();
        }
        break;
      case 'r':
        event.preventDefault();
        resetFilters();
        break;
      case 'e':
        event.preventDefault();
        exportData();
        break;
    }
  }
};

// Add keyboard event listener
onMounted(() => {
  document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
  <div class="min-h-screen flex">
    <!-- Sidebar -->
    <FilterSidebar
        :filters="filters"
        :students="mockStudents"
        :staff="mockStaff"
        @update-filters="updateFilters"
        @reset-filters="resetFilters"
        @export-data="exportData"
        class="me-3"
    />

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <!-- Statistics Panel -->
      <StatisticsPanel
          :students="mockStudents"
          :staff="mockStaff"
          :filtered-students="filteredStudents"
          :filtered-staff="filteredStaff"
          :filters="filters"
      />

      <!-- Content Area -->
      <div class="flex-1 overflow-y-auto mt-3">
        <!-- Empty State -->
        <div v-if="currentFilteredData.length === 0" class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun résultat</h3>
          <p class="text-gray-500 mb-4">
            Aucun {{ filters.mode === 'students' ? 'étudiant' : 'personnel' }} ne correspond à vos critères de recherche.
          </p>
          <button
              @click="resetFilters"
              class="btn-primary"
          >
            Réinitialiser les filtres
          </button>
        </div>

        <!-- Grid View -->
        <div
            v-else-if="filters.viewMode === 'grid'"
            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6"
        >
          <PersonCard
              v-for="person in currentFilteredData"
              :key="person.id"
              :person="person"
              :mode="filters.mode"
              :view-mode="filters.viewMode"
          />
        </div>

        <!-- List View -->
        <div
            v-else
            class="space-y-4"
        >
          <PersonCard
              v-for="person in currentFilteredData"
              :key="person.id"
              :person="person"
              :mode="filters.mode"
              :view-mode="filters.viewMode"
          />
        </div>
      </div>

      <!-- Footer with shortcuts -->
      <div class="bg-white border-t border-gray-200 px-6 py-3">
        <div class="flex items-center justify-between text-sm text-gray-500">
          <div>
            Raccourcis clavier : Ctrl+F (recherche), Ctrl+R (réinitialiser), Ctrl+E (exporter)
          </div>
          <div>
            {{ currentFilteredData.length }} résultat{{ currentFilteredData.length > 1 ? 's' : '' }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
