<script setup>
import {ref, onMounted} from 'vue';
import {HeaderComponent, GlobalLoader} from '@components';
import { useUsersStore } from '@stores';
import {getEtudiantScolaritesService} from '@requests';

const scolarites = ref([]);
const scolariteActive = ref(null);
const isLoadingScolarites = ref(true);
const userStore = useUsersStore();

onMounted(async () => {
  try {
    isLoadingScolarites.value = true;

    const params = {
      etudiant: userStore.userId
    }
    scolarites.value = await getEtudiantScolaritesService(params, '/all');

    // récupérer la scolarite qui a acitf = true
    scolariteActive.value = scolarites.value.map((scolarite) => {
      if (scolarite.actif) {
        return scolarite;
      }
    }).filter(Boolean)[0];
  } catch (error) {
    console.error('Erreur lors de la récupération des scolarités :', error);
  } finally {
    isLoadingScolarites.value = false;
    console.log(scolariteActive.value);
  }
});
</script>

<template>
  <HeaderComponent
      icon="pi pi-graduation-cap"
      titre="Scolarité"
      description="Consultez les détails de l'ensemble de votre scolarité"
  />
  <div class="card mb-6">
    <header class="card-header flex justify-between items-center w-full mb-6">
      <div>
        <p class="top-card-header">
          Cette année
        </p>
        <div class="flex flex-col items-start">
          <p class="uppercase text-xs font-bold mb-0! text-muted-color">
            Semestre
          </p>
          <h2 class="mt-0!">
            OK
          </h2>
        </div>
      </div>
    </header>
    <div class="card-body">
      <GlobalLoader v-if="isLoadingScolarites" />

    </div>
  </div>
  <div class="card">
    <header class="card-header flex justify-between items-center w-full mb-6">
      <div>
        <p class="top-card-header">
          Historique
        </p>
        <div class="flex flex-col items-start">
          <p class="uppercase text-xs font-bold mb-0! text-muted-color">
            Semestre
          </p>
          <h2 class="mt-0!">
            OK
          </h2>
        </div>
      </div>
    </header>
  </div>
</template>
