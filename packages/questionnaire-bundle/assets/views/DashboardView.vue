<template>
  <div class="min-h-screen space-y-6">
    <HeaderComponent icon="pi pi-chart-bar" titre="Portail Évaluation & Qualité"
      description="Pilotez, répondez et analysez les enquêtes d'enseignement" />

    <div class="space-y-6">
      <DashboardAdmin v-if="isQualiteUser" />
      <DashboardTeacher v-else-if="isTeacherUser" />
      <DashboardStudent v-else-if="isStudentUser" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, computed } from 'vue';
import DashboardStudent from '../components/Dashboard/DashboardStudent.vue';
import DashboardTeacher from '../components/Dashboard/DashboardTeacher.vue';
import DashboardAdmin from '../components/Dashboard/DashboardAdmin.vue';
import { useSurveyStore } from '@/stores/survey';
import { useResponseStore } from '@/stores/responses';
import { HeaderComponent } from '@components';
import { hasPermission } from '@utils/permissions';

const surveyStore = useSurveyStore();
const responseStore = useResponseStore();

const isQualiteUser = computed(() => hasPermission('isQualite'));
const isTeacherUser = computed(() => hasPermission('isPersonnel'));
const isStudentUser = computed(() => hasPermission('isEtudiant'));

// Initialization
onMounted(async () => {
  // Load data from state stores for child components
  await surveyStore.loadQuestionnaires();
  responseStore.loadFromLocalStorage();
});
</script>
