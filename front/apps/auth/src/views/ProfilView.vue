<script setup>
import { ProfilPersonnelComponent, ProfilEtudiantComponent, TopbarComponent } from '@components';
import {useUsersStore} from "@stores";
import {computed} from "vue";

const props = defineProps({
  logoUrl: {
    type: String,
    default: '/assets/logo.png',
  },
  appName: {
    type: String,
    default: 'App',
  },
  user: {
    type: Object,
    default: () => ({}),
  }
});

const store = useUsersStore();
const isPersonnel = computed(() => store.userType === 'personnels');
const isEtudiant = computed(() => store.userType === 'etudiants');

console.log('store.user', store.departements);
</script>

<template>
  <TopbarComponent :app-name :logo-url/>
  <div class="layout-main-container mt-16">
    <main class="layout-main">
      <ProfilPersonnelComponent v-if="isPersonnel" />
      <ProfilEtudiantComponent v-if="isEtudiant" />
    </main>
  </div>
</template>

<style>
</style>
