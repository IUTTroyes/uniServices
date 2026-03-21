<script setup>
import { ProfilPersonnel, ProfilEtudiant } from '@components';
import {useUsersStore} from "@stores";
import {computed, onMounted} from "vue";

const store = useUsersStore();
const isPersonnel = computed(() => store.userType === 'personnels');
const isEtudiant = computed(() => store.userType === 'etudiants');

onMounted(async () => {
  // Assurons-nous que les données utilisateur sont chargées
  if (!store.isLoaded) {
    await store.getUser();
  }
})
</script>

<template>
  <div class="card">
    <ProfilPersonnel v-if="isPersonnel" />
    <ProfilEtudiant v-if="isEtudiant" :etudiant-sco="store.scolariteActif" :etudiant-photo="store.userPhoto" />
  </div>
</template>

<style>
</style>
