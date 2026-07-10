<script setup>
import {PersonnelsListe, HeaderComponent} from '@components'

</script>

<template>
  <HeaderComponent
      icon="pi pi-users"
      titre="Liste des personnels"
      description="Consultez la liste de tous les personnels du département"
  />
  <div class="card">
    <PersonnelsListe></PersonnelsListe>
  </div>
</template>

<style scoped>

</style>
