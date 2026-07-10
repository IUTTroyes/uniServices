<template>
  <div class="min-h-screen">
    <HeaderComponent
      icon="pi pi-chart-bar"
      titre="Portail Évaluation & Qualité"
      description="Pilotez, répondez et analysez les enquêtes d'enseignement"
    />

      <!-- Role Selector Tabs -->
      <div class="bg-gray-200/80 dark:bg-gray-800/80 backdrop-blur-md p-1 rounded-xl flex gap-1 self-start shadow-inner border border-gray-300/30 dark:border-gray-700/30">
        <button
          v-for="role in roles"
          :key="role.id"
          @click="activeRole = role.id"
          :class="[
            'flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 border-0 cursor-pointer bg-transparent',
            activeRole === role.id
              ? 'bg-white dark:bg-gray-700 text-primary-600 dark:text-white shadow-md scale-102 font-semibold'
              : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'
          ]"
        >
          <component :is="role.icon" class="w-4 h-4" />
          {{ role.label }}
        </button>
      </div>

    <!-- Active View Area -->
    <Transition name="fade" mode="out-in">
      <div :key="activeRole">
        <DashboardStudent v-if="activeRole === 'student'" />
        <DashboardTeacher v-else-if="activeRole === 'teacher'" />
        <DashboardAdmin v-else-if="activeRole === 'admin'" />
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import {
  AcademicCapIcon,
  UserGroupIcon,
  HomeIcon
} from '@heroicons/vue/24/outline';
import DashboardStudent from '../components/Dashboard/DashboardStudent.vue';
import DashboardTeacher from '../components/Dashboard/DashboardTeacher.vue';
import DashboardAdmin from '../components/Dashboard/DashboardAdmin.vue';
import { useSurveyStore } from '@/stores/survey';
import { useResponseStore } from '@/stores/responses';
import { HeaderComponent } from '@components';

const route = useRoute();
const surveyStore = useSurveyStore();
const responseStore = useResponseStore();

// Tab / Role Switcher configuration
const activeRole = ref('student'); // 'student' | 'teacher' | 'admin'

const roles = [
  { id: 'student', label: 'Étudiant', icon: AcademicCapIcon },
  { id: 'teacher', label: 'Enseignant', icon: HomeIcon },
  { id: 'admin', label: 'Responsable Qualité', icon: UserGroupIcon }
];

// Initialization
onMounted(async () => {
  // Select active view based on routing context
  if (route.path.includes('qualite') || route.name === 'questionnaire_qualite-enquetes') {
    activeRole.value = 'admin';
  }

  // Load data from state stores for child components
  await surveyStore.loadQuestionnaires();
  responseStore.loadFromLocalStorage();
});
</script>

<style scoped>
@reference "../assets/tailwind.css";

/* Animations transitions */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}

.fade-enter-from {
  opacity: 0;
  transform: translateY(10px);
}

.fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

.scale-102 {
  transform: scale(1.02);
}
</style>
