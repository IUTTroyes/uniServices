<script setup>
import { ProfilPersonnel, ProfilEtudiant, HeaderComponent } from '@components';
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
  <HeaderComponent
      icon="pi pi-id-card"
      titre="Profil"
      description="Consultez votre profil et les informations associées"
  />
  <div class="card">
    <ProfilPersonnel v-if="isPersonnel" />
    <ProfilEtudiant v-if="isEtudiant" :etudiant-sco="store.scolariteActif" :etudiant-photo="store.userPhoto" />
  </div>
</template>

<style>
</style>
