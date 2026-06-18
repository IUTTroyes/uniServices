<script setup>
import {PermissionGuard} from "@components";
import { useUsersStore } from "@stores";
import DashboardPersonnel from "@/components/Personnel/Dashboard.vue";
import DashboardEtudiant from "@/components/Etudiant/Dashboard.vue";

const userStore = useUsersStore();
</script>

<template>
  <div>
  <PermissionGuard permission="isPersonnel">
    <DashboardPersonnel v-if="userStore.isPersonnel"/>
  </PermissionGuard>
  <PermissionGuard permission="isEtudiant">
    <DashboardEtudiant v-if="userStore.isEtudiant" />
  </PermissionGuard>
</div>
</template>

<style scoped>
.bg-primary-light {
  background-color: var(--p-tag-primary-background);
  border: 1px solid var(--p-tag-primary-background);
  color: var(--primary-color);
}

.card-actus {
  border: solid 1px var(--p-tag-primary-background);
  box-shadow: none;
}
</style>
