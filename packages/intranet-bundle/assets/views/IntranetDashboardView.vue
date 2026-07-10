<script setup>
import {HeaderComponent, PermissionGuard} from "@components";
import { useUsersStore } from "@stores";
import DashboardPersonnel from "@/components/Personnel/Dashboard.vue";
import DashboardEtudiant from "@/components/Etudiant/Dashboard.vue";
import {useRoute} from 'vue-router';

const userStore = useUsersStore();
const route = useRoute();
</script>

<template>
  <div>
    <HeaderComponent
        icon="pi pi-home"
        titre="Dashboard"
        description="Retrouvez vos informations clés et vos accès rapides"
    />
    <PermissionGuard permission="isPersonnel">
      <DashboardPersonnel v-if="userStore.isPersonnel"/>
    </PermissionGuard>
    <PermissionGuard permission="isEtudiant">
      <DashboardEtudiant v-if="userStore.isEtudiant" />
    </PermissionGuard>
  </div>
</template>
