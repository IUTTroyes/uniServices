<script setup>
import {HeaderComponent} from '@components';
import { useEtablissementStore } from '@stores'

const etablissementStore = useEtablissementStore()
const etablissement = etablissementStore.etablissement

// todo: récupérer l'étudiant via /scolarite
</script>

<template>
  <HeaderComponent
      icon="pi pi-graduation-cap"
      titre="Scolarité"
      :description="`Consultez les détails de l'ensemble de votre scolarité à ${etablissement?.libelle}`"
  />
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
