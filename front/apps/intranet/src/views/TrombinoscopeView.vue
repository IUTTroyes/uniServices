<script setup lang="ts">
import {computed, onMounted, onUnmounted, ref} from 'vue';
import { getEtudiantsService, getPersonnelsService } from '@requests';
import { useFilters } from '@/composables/useFilters';
import FilterSidebar from '@/components/Trombinoscope/FilterSidebar.vue';
import StatisticsPanel from '@/components/Trombinoscope/StatisticsPanel.vue';
import PersonCard from '@/components/Trombinoscope/PersonCard.vue';
import {useUsersStore} from '@stores';
import type {Etudiant, Personnel} from "@types";

const students = ref<Etudiant[]>([]);
const staff = ref<Personnel[]>([]);      // tableau de personnels réel
const loading = ref(false);
const error = ref<string | null>(null);

const usersStore = useUsersStore();
const departementId = usersStore.departementDefaut?.id;

// Initialize filters composable
const {
  filters,
  filteredStudents,
  filteredStaff,
  updateFilters,
  resetFilters,
  exportData,
} = useFilters(students, staff);


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
onMounted(async () => {
  document.addEventListener('keydown', handleKeydown);
  await fetchData();

});

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown);
});

const mapEtudiantToStudentCard = (e: Etudiant) => {
  return {
    id: e.id,
    prenom: e.prenom,
    nom: e.nom,
    mailUniv: e.mailUniv,
    scolarites: e.scolarites || [],
    scolarite: (e.scolarites || []).find((s) => s?.actif === true) || null,
    photo: getPhotoUrl(e.photoName, 'etudiants'),
    groups: {
      CM: e.groupes?.CM || [],
      TD: e.groupes?.TD || [],
      TP: e.groupes?.TP || [],
    },
  };
}

const mapPersonnelToStaffCard = (p: Personnel) => {
  return {
    id: p.id,
    prenom: p.prenom,
    nom: p.nom,
    mailUniv: p.mailUniv,
    photo: getPhotoUrl(p.photoName, 'personnels'),
    statut: p.statut,
    //  department: p.departement?.code || p.department || '',
  };
}

function getPhotoUrl(name: string | undefined, type: 'etudiants' | 'personnels'): string {
  const folder = `photos_${type}`;
  return name ? `/common-images/${folder}/${name}` : `/common-images/${folder}/noimage.png`;
}

async function fetchData() {
  loading.value = true;
  error.value = null;

  if (!departementId) {
    error.value = 'Aucun département par défaut défini.';
    loading.value = false;
    return;
  }

  try {
    const [etudiantsResp, personnels] = await Promise.all([
      getEtudiantsService({'departement': departementId, 'anneeSortie': 0}),
      getPersonnelsService({'departement': departementId}),
    ]);

    const etudiants = etudiantsResp || [];
    students.value = etudiants.map(mapEtudiantToStudentCard);
    staff.value = (personnels || []).map(mapPersonnelToStaffCard);
  } catch (e: any) {
    error.value = e?.message || 'Erreur lors de la récupération des données.';
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div class="min-h-screen flex">
    <!-- Sidebar -->
    <FilterSidebar
        :filters="filters"
        :students="students"
        :staff="staff"
        @update-filters="updateFilters"
        @reset-filters="resetFilters"
        @export-data="exportData"
        class="me-3"
    />

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <!-- Statistics Panel -->
      <StatisticsPanel
          :students="students"
          :staff="staff"
          :filtered-students="filteredStudents"
          :filtered-staff="filteredStaff"
          :filters="filters"
      />

      <!-- Content Area -->
      <div class="flex-1 overflow-y-auto mt-3">
        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center items-center py-12">
          <div class="text-gray-500">Chargement...</div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-red-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
          </svg>
          <h3 class="text-lg font-medium text-red-600 mb-2">Erreur</h3>
          <p class="text-gray-500 mb-4">{{ error }}</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="currentFilteredData.length === 0" class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun résultat</h3>
          <p class="text-gray-500 mb-4">
            Aucun {{ filters.mode === 'students' ? 'étudiant' : 'personnel' }} ne correspond à vos critères de
            recherche.
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
