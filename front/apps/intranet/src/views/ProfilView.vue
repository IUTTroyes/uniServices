<script setup>
import { ProfilPersonnelComponent, ProfilEtudiantComponent } from '@components';
import {useUsersStore} from "@stores";
import {computed} from "vue";

const store = useUsersStore();
const isPersonnel = computed(() => store.userType === 'personnels');
const isEtudiant = computed(() => store.userType === 'etudiants');
</script>

<template>
  <ProfilPersonnelComponent v-if="isPersonnel" />
  <ProfilEtudiantComponent v-if="isEtudiant" />
</template>

<style>
</style>
