<template>
  <div class="min-h-screen space-y-6">
    <HeaderComponent
      icon="pi pi-chart-bar"
      color="blue"
      :show-back="false"
      titre="Portail Évaluation & Qualité"
      description="Pilotez les enquêtes de votre département, consultez vos évaluations d'enseignant et suivez la participation."
    >
      <template #actions>
        <!-- Sélecteur de vue pour les enseignants ayant également un rôle de pilotage qualité / chef de département -->
        <div v-if="canSwitchViews" class="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-800 p-1 rounded-xl border border-slate-200 dark:border-slate-700">
          <Button
            type="button"
            size="small"
            :severity="activeTab === 'admin' ? 'primary' : 'secondary'"
            :text="activeTab !== 'admin'"
            icon="pi pi-chart-pie"
            label="Pilotage Qualité"
            class="text-xs !py-1 !px-2.5 font-semibold"
            @click="activeTab = 'admin'"
          />
          <Button
            type="button"
            size="small"
            :severity="activeTab === 'teacher' ? 'primary' : 'secondary'"
            :text="activeTab !== 'teacher'"
            icon="pi pi-user"
            label="Mes Évaluations"
            class="text-xs !py-1 !px-2.5 font-semibold"
            @click="activeTab = 'teacher'"
          />
        </div>
      </template>
    </HeaderComponent>

    <div class="space-y-6">
      <DashboardAdmin v-if="currentView === 'admin'" />
      <DashboardTeacher v-else-if="currentView === 'teacher'" />
      <DashboardStudent v-else-if="currentView === 'student'" />
      <Message v-else severity="info">Aucun dashboard n’est disponible avec vos responsabilités actuelles. Vous pouvez consulter les questionnaires depuis le menu.</Message>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import DashboardStudent from '../components/Dashboard/DashboardStudent.vue';
import DashboardTeacher from '../components/Dashboard/DashboardTeacher.vue';
import DashboardAdmin from '../components/Dashboard/DashboardAdmin.vue';
import { useSurveyStore } from '@/stores/survey';
import { useUsersStore } from '@stores';
import { HeaderComponent } from '@components';
import Button from 'primevue/button';
import { hasPermission } from '@utils/permissions';

const userStore = useUsersStore();
const surveyStore = useSurveyStore();

const activeTab = ref<'admin' | 'teacher'>('admin');

const isQualiteUser = computed(() => hasPermission([
  'isQualite', 'ROLE_QUESTIONNAIRE_MANAGER', 'isChefDepartement',
  'isDirecteurEtudes', 'isDirection', 'ROLE_ADMIN', 'SUPER_ADMIN',
]));
const isTeacherUser = computed(() => hasPermission('isEnseignant'));
const isStudentUser = computed(() => hasPermission('isEtudiant'));

const canSwitchViews = computed(() => isQualiteUser.value && isTeacherUser.value);

const currentView = computed(() => {
  if (canSwitchViews.value) {
    return activeTab.value;
  }
  if (isQualiteUser.value) return 'admin';
  if (isTeacherUser.value) return 'teacher';
  if (isStudentUser.value) return 'student';
  return null;
});

// Initialization
onMounted(async () => {
  // Load data from state stores for child components
  await surveyStore.loadQuestionnaires();
});
</script>

