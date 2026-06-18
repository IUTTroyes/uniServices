<script setup>
import {PermissionGuard} from "@components";
import { useUsersStore } from "@stores";
import DashboardPersonnel from "@/components/Personnel/Dashboard.vue";
import DashboardWidgetConfiguration from '@/components/Personnel/dashboard/DashboardWidgetConfiguration.vue';
import DashboardEtudiant from "@/components/Etudiant/Dashboard.vue";
import {useRoute} from 'vue-router';

const userStore = useUsersStore();
const route = useRoute();
</script>

<template>
  <div>
  <PermissionGuard permission="isPersonnel">
    <DashboardWidgetConfiguration v-if="userStore.isPersonnel && route.name === 'DashboardWidgetsConfig'"/>
    <DashboardPersonnel v-else-if="userStore.isPersonnel"/>
  </PermissionGuard>
  <PermissionGuard permission="isEtudiant">
    <DashboardEtudiant v-if="userStore.isEtudiant" />
  </PermissionGuard>
</div>
</template>
