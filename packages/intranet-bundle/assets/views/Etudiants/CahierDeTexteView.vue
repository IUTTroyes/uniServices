<script setup>
import {HeaderComponent} from '@components';

// todo: récupérer l'étudiant via /scolarite
</script>

<template>
  <HeaderComponent
      icon="pi pi-book"
      titre="Cahier de texte"
      description="Suivez vos échéances et organisez votre travail"
  />
  <div class="card">
    <header class="card-header flex justify-between items-center w-full mb-6">
      <div>
        <p class="top-card-header">
          Suivi des rendus
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
