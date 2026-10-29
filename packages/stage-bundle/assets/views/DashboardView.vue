<script setup>
import { computed } from 'vue';
import { hasPermission } from '@utils/permissions';
import ResponsableDashboardView from './Responsable/ResponsableDashboardView.vue';
import EnseignantDashboardView from './Enseignant/EnseignantDashboardView.vue';
import EtudiantDashboardView from './Etudiant/EtudiantDashboardView.vue';

const isPersonnel = computed(() => hasPermission('isPersonnel'));
const isEtudiant = computed(() => hasPermission('isEtudiant'));
const isCoordinator = computed(() => isPersonnel.value && hasPermission([
  'ROLE_STAGE_MANAGER', 'isChefDepartement', 'SUPER_ADMIN',
]));
</script>

<template>
  <div class="relative min-h-screen">
    <ResponsableDashboardView v-if="isCoordinator" />
    <EnseignantDashboardView v-else-if="isPersonnel" />
    <EtudiantDashboardView v-else-if="isEtudiant" />
  </div>
</template>
