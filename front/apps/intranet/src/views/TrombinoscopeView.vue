<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';

import { getEtudiantsService, getPersonnelsService } from '@requests/';
import { useFilters } from '@/composables/useFilters';
import FilterSidebar from '@/components/Trombinoscope/FilterSidebar.vue';
import StatisticsPanel from '@/components/Trombinoscope/StatisticsPanel.vue';
import PersonCard from '@/components/Trombinoscope/PersonCard.vue';
import {useUsersStore} from '@stores';

const students = ref([]);   // tableau d’étudiants réel
const staff = ref([]);      // tableau de personnels réel
const loading = ref(false);
const error = ref<string | null>(null);

const usersStore = useUsersStore();
const departementId = usersStore.departementDefaut.id;

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
  console.log('currentFilteredData')
  console.log(filters.value.mode === 'students' ? filteredStudents.value : filteredStaff.value)
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
  console.log('onMounted')
  await fetchData();
});

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown);
});

const mapEtudiantToStudentCard = (e: any) => {
  return {
    id: e.id,
    prenom: e.prenom,
    nom: e.nom,
    mailUniv: e.mailUniv,
    photo: photoEtudiantFromName(e.photoName),
  //  semester: e.semestreCourant?.numero || e.semester,
    groups: {
      CM: e.groupes?.CM || [],
      TD: e.groupes?.TD || [],
      TP: e.groupes?.TP || [],
    },
  };
}

const mapPersonnelToStaffCard = (p: any) => {
  return {
    id: p.id,
    prenom: p.prenom,
    nom: p.nom,
    mailUniv: p.mailUniv,
    photo: photoPersonnelFromName(p.photoName),
    statut: p.statut,
  //  department: p.departement?.code || p.department || '',
  };
}

function photoEtudiantFromName(name?: string) {
  // fallback identique à ce qui est fait ailleurs dans le projet
  return name ? `/common-images/photos_etudiants/${name}` : '/common-images/photos_etudiants/noimage.png';
}

function photoPersonnelFromName(name?: string) {
  return name ? `/common-images/photos_personnels/${name}` : '/common-images/photos_personnels/noimage.png';
}

async function fetchData() {
  loading.value = true;
  error.value = null;
  try {
    // À adapter si vous avez des filtres serveur (département, année, etc.)
    const [etudiantsResp, personnels] = await Promise.all([
      getEtudiantsService({ 'departement': departementId, 'anneeSortie': 0 }),
      getPersonnelsService({ 'departement': departementId }),
    ]);

    // getEtudiantsService retourne typiquement une réponse Hydra
    const etudiants = etudiantsResp.member || [];

    students.value = await etudiants.map(mapEtudiantToStudentCard);
    staff.value = await (personnels || []).map(mapPersonnelToStaffCard);
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
