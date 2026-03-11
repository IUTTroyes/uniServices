<script setup>
import NewAnneeUniv from "@/views/Super-Administration/NewAnneeUniv.vue";
import ListAnneeUniv from "@/views/Super-Administration/ListAnneeUniv.vue";
import AnneeUnivDiplomes from "@/views/Super-Administration/AnneeUnivDiplomes.vue";
import EditAnneeUniv from "@/views/Super-Administration/EditAnneeUniv.vue";
</script>

<template>
  <div class="card">
    <div class="card-body flex flex-col gap-10">
      <router-link to="/super-administration/annee-universitaire" class="p-button p-component p-button-primary">
        <span class="p-button-icon pi pi-plus"></span>
        <span class="p-button-label">Nouvelle année universitaire</span>
      </router-link>
      <router-link to="/super-administration/annees-universitaires" class="p-button p-component p-button-secondary">
        <span class="p-button-icon pi pi-list"></span>
        <span class="p-button-label">Liste des années universitaires</span>
      </router-link>
    </div>
  </div>

  <RouterView/>
</template>

<style scoped>

</style>

