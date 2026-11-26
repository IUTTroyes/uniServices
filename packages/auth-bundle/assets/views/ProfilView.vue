<script setup>
import { ProfilEtudiant, TopbarComponent } from '@components';
import Button from "primevue/button";
import {useUsersStore} from "@stores";
import {computed, onMounted} from "vue";

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
const identite = computed(() => store.user ?? {});
onMounted(async () => { if (!store.isLoaded) await store.getUser(); });
</script>

<template>
  <TopbarComponent :app-name :logo-url/>
  <div class="layout-main-container">
    <main class="layout-main">
      <section v-if="isPersonnel" class="card" aria-labelledby="profil-titre">
        <h1 id="profil-titre" class="text-2xl font-bold mb-2">
          {{ identite.prenom }} <span class="uppercase">{{ identite.nom }}</span>
        </h1>
        <p v-if="identite.mailUniv || identite.mail" class="mb-4">{{ identite.mailUniv || identite.mail }}</p>
        <Button as="router-link" to="/auth/profil/acces" label="Comprendre mes accès" icon="pi pi-key" severity="secondary" text size="small" data-test="lien-acces" />
      </section>
      <Button v-if="isEtudiant" as="router-link" to="/auth/profil/acces" label="Comprendre mes accès" icon="pi pi-key" severity="secondary" text size="small" class="mb-3" data-test="lien-acces" />
      <ProfilEtudiant  v-if="isEtudiant" />
    </main>
  </div>
</template>

<style>
</style>
